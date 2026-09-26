<?php

namespace AC\core\modules\webIo\models;

use AC\core\system\db\Query;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\model\BaseModel;
use AC\core\engines\WebIoEngine;
use stdClass;

/**
 * Class WebIoModel
 */
class WebIoModel extends BaseModel
{
  /***
   * @var WebIoEngine
   */
  protected $engine;

  public $name;
  public $id;
  public $ip;
  public $port;
  public $password;
  public $use;
  public $pre_start_time;
  public $count_ports;
  public $outputs;

  private static $_state;
  private static $_type_state = array();

  protected $baseGetFunction = 'getWebIoById';

  public function __construct($id = null)
  {
    parent::__construct($id);
    $this->setPreStart();
    $this->setOutputs();
  }

  public function setPreStart()
  {
    $pre_start            = $this->pre_start_time;
    $this->pre_start_time = new stdClass();
    foreach (WebIoTypeSatesModel::getTypeSates('alias') as $alias => $item) {
      $this->pre_start_time->{$alias} = (object)array('alias' => $alias, 'value' => isset($pre_start->{$alias}) ? $pre_start->{$alias} : 0);
    }
  }

  public function setOutputs()
  {
    $this->outputs = WebIoOutputsModel::getByWebIoId($this->id);
  }

  /**
   * Получить типы для webIo
   * todo Сделать проверку по площадкам чтобы с определенной площадки забирать используемые значения
   * @return array
   */
  public static function getWebIoTypes()
  {
    return array(
      'light'   => array('id' => 1, 'title' => lang('webIo_light', 'webIo')),
      'heating' => array('id' => 2, 'title' => lang('webIo_heating', 'webIo')),
      'net'     => array('id' => 3, 'title' => lang('webIo_net', 'webIo')),
    );
  }

  public static function issetWebIoStateByAreaIdWeekdayTime($area_id, $date, $type, $time)
  {
    $weekday = CalendarHelper::getWeekdayByUnixtime(strtotime($date));
    $state   = self::getStateWebIo($area_id, $weekday);

    return isset($state[$type]) && in_array($time . ':00', $state[$type]);
  }

  public static function getStateWebIo($area_id, $weekday)
  {
    if (!isset(self::$_state)) {
      $webIo        = new WebIoModel();
      self::$_state = $webIo->engine->getAllState();
    }

    return isset(self::$_state[$area_id . '_' . $weekday]) ? self::$_state[$area_id . '_' . $weekday] : null;
  }

  public function remove()
  {
    if (parent::remove()) {
      return WebIoOutputsModel::deleteByWebIoId($this->id);
    }

    return false;
  }

  public function checkUse()
  {
    return (bool)count(Query::sqlQuery('select id from ' . Query::tableName('webio') . ' where `use` = 1', array()));
  }

}