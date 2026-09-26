<?php

namespace AC\core\modules\specPrices\engines;


use AC\core\modules\specPrices\entities\dto\SpecPriceDto;
use AC\core\system\helpers\NumberHelper;

useClass('engines\reservation.specprice');

//проводимые акции
class SpecPricesEngine extends \reservation_specprice
{
  
  public function getSpecPriceAsDto($sprice_id): ?SpecPriceDto
  {
    if ($sprice_id && $this->getSprice($sprice_id, $sprice)) {
      return SpecPriceDto::fromArray($sprice);
    }
    
    return null;
  }
  
  public function getSpecPricesAs($as = 'array'): array
  {
    $result = [];
    if ($this->getSprices($sprices)) {
      foreach ($sprices as $sprice) {
        $spriceDto                    = SpecPriceDto::fromArray($sprice);
        $result[$sprice['sprice_id']] = match ($as) {
          'dto'   => $spriceDto,
          'array' => $spriceDto->toArray(),
          default => $sprice,
        };
      }
    }
    
    return $result;
  }
  
  public function insert($model): bool
  {
    if ($id = $this->insertSprice(
      $model->sort,
      $model->code,
      $model->title,
      NumberHelper::float($model->rate),
      $model->for_all ?? 0,
      $model->type_sport,
    )) {
      $model->{$model->getPrimaryKey()} = $id;
      
      return true;
    }
    return false;
  }
  
  public function update($model): bool
  {
    return $this->changeSprice(
      $model->sprice_id,
      $model->sort,
      $model->code,
      $model->title,
      NumberHelper::float($model->rate),
      $model->for_all ?? 0,
      $model->type_sport,
    );
  }
  
  public function delete($model): bool
  {
    return $this->removeSprice($model->sprice_id);
  }
  
}