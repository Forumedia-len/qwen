<?php

namespace AC\core\engines;

use AC\core\modules\stocks\entities\dto\StockDto;
use AC\core\system\db\Query;
use AC\core\system\helpers\ObjectHelper;
use PDO;

useClass('engines\reservation.stocks');

//проводимые акции
class StocksEngine extends \reservation_stocks
{
  
  public function getStockByGroup($group_id)
  {
    return Query::sqlQuery('select * from ' . Query::tableName($this->tableName()) . ' where `group` = \'' . $group_id . '\'', [], true,
      ['style' => PDO::FETCH_CLASS, 'argument' => get_class(ObjectHelper::createEntity('stocks'))]);
  }
  
  public function addGroup($group_id, $stocks = [])
  {
    if ($this->unsetGroup($group_id)) {
      return Query::sqlQuery('update ' . Query::tableName($this->tableName()) . ' set `group`=\'' . $group_id . '\'' . ' where stock_id in (' . implode(', ',
          $stocks) . ')', [], false);
    }
    
    return false;
  }
  
  public function unsetGroup($group_id)
  {
    return Query::sqlQuery('update ' . Query::tableName($this->tableName()) . ' set `group`=0  where `group` =\'' . $group_id . '\'', [], false);
  }
  
  public function getStockAsDto($stockId): ?StockDto
  {
    if ($stockId && $this->getStock($stockId, $stock)) {
      return StockDto::fromArray($stock);
    }
    
    return null;
  }

  public function getStocksAs($as = 'array'): array
  {
    $result = [];
    if($this->getStocks($stocks)) {
      foreach ($stocks as $stock) {
        $stockDto = StockDto::fromArray($stock);
        $result[$stock['stock_id']] = match ($as) {
          'dto' => $stockDto,
          'array' => $stockDto->toArray(),
          default => $stock
        };
      }
    }
    
    return $result;
  }
  
  public function insert($model): bool
  {
    return $this->insertStock(
      $model->sort,
      $model->code,
      $model->title,
      $model->rate,
      $model->for_all,
      $model->type_sport,
      $model->only_once,
      $model->dimension,
      $model->group
    );
  }
  
  public function update($model): bool
  {
    return $this->changeStock(
      $model->stock_id,
      $model->sort,
      $model->code,
      $model->title,
      $model->rate,
      $model->for_all,
      $model->type_sport,
      $model->only_once,
      $model->dimension,
      $model->group,
    );
  }
  
  public function delete($model): bool
  {
    return $this->removeStock($model->stock_id);
  }
}