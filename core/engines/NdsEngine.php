<?php

namespace AC\core\engines;

use AC\core\modules\config\entities\dto\NdsDto;
use AC\core\system\db\Query;
use AC\core\modules\config\models\ConfigNdsModel;
use AC\core\system\helpers\DataHelper;

useClass('engines\reservation.nds');

//проводимые акции
class NdsEngine extends \reservation_nds
{
  private $query;
  private $tableName = 'config_nds';
  
  
  public function __construct()
  {
    $this->query = new Query();
  }
  
  /** Получить название таблицы
   * @return string
   */
  public function getTableName()
  {
    return Query::$prefix . $this->tableName;
  }

  /** Получить все строки из таблицы
   *
   * @param  $result
   * @param array $conditions
   * @param array $_order
   * @param array $params
   *
   * @return bool
   */
  public function getAllNds(&$result, array $conditions = [] ,array $_order = [], array $params = ['key' => 'nds_id', 'as' => 'dto', 'dtoClass' => NdsDto::class, 'addDefault' => false]): bool
  {
    $params = array_merge(['key' => 'nds_id', 'as' => 'dto', 'dtoClass' => NdsDto::class, 'addDefault' => false], $params);
    $result = [];
    
    if ($temp = Query::sqlQuery('select * from ' . $this->getTableName() . ' order by rate')) {
      $result = DataHelper::getDataAs($temp, $params);
      if (isset($params['addDefault']) && $params['addDefault']) {
        $result['default'] = $result[$this->getDefaultNdsId()];
      }
      return true;
    }
    
    return false;
  }
  
  /** Получить строку с ндс по его id
   *
   * @param $nds_id
   * @param $result
   *
   * @return bool
   */
  public function getNdsById($nds_id, &$result)
  {
    if ($result = Query::sqlQuery('select * from ' . $this->getTableName() . ' where nds_id=\'' . $nds_id . '\''/*, array(), true,
      array('style' => PDO::FETCH_CLASS, 'argument' => 'ConfigNdsModel')*/)) {
      $result = $result[0];
      
      return true;
    }
    
    return false;
  }

  /**
   * Получить ставку НДС по умолчанию.
   */
  public function getDefaultNdsRate(): int|false
  {
    $nds = Query::sqlQuery(
      'SELECT rate FROM ' . $this->getTableName() . ' WHERE set_default = \'1\' ORDER BY nds_id LIMIT 1',
      [],
      true,
      ['onlyOne' => true]
    );

    return is_array($nds) && array_key_exists('rate', $nds) ? (int)$nds['rate'] : false;
  }

  /** Обновить элемент базы
   *
   * @param $model ConfigNdsModel
   *
   * @return bool
   */
  public function update($model)
  {
    if (Query::sqlQuery('update ' . $this->getTableName() . ' set rate = "' . $model->rate . '", comment = "' . $model->comment . '" where nds_id = "' . $model->nds_id . '"',
      [], false)) {
      
      return true;
    }
    
    return false;
  }
  
  /** Вставить строку с ндс
   * @param $model ConfigNdsModel
   * @return bool
   */
  public function insert($model)
  {
    if (Query::sqlQuery('insert into ' . $this->getTableName() . ' set rate = "' . $model->rate . '", comment = "' . $model->comment . '"', [],
      false)) {
      
      return true;
    }
    
    return false;
  }
  
  public function checkedClient($nds_id)
  {
    //UPDATE `at_tennis_buchung2_clients` SET nds=(SELECT min(nds_id) FROM at_tennis_buchung2_config_nds WHERE set_default="1") WHERE nds IN('3','4','5')
    $sql = 'UPDATE ' . Query::$prefix . 'clients SET nds=(SELECT min(nds_id) FROM ' . $this->getTableName() . ' WHERE set_default="1") WHERE nds=' . $nds_id;
    if (Query::sqlQuery($sql)) {
      return true;
    }
    
    return false;
  }
  
}
