<?php

namespace AC\app\config;

use AC\core\modules\webIo\models\WebIoModel;
use AC\core\modules\webIo\models\WebIoOutputsModel;
use AC\core\modules\webIo\models\WebIoTypeSatesModel;


class WebIOStateConfig
{
  public $webIo;
  public $outputs = array();
  public $types;

  private static $inst;

  private function __construct()
  {
    $this->webIo = WebIoModel::getAllWebIo(false, true);
    $this->types = WebIoTypeSatesModel::getTypeSates('id', false, false);
    $outputs     = WebIoOutputsModel::getAll(false);
    if (!empty($outputs)) {
      /** @var $output WebIoOutputsModel */
      foreach ($outputs as $output) {
        $output->pre_start_time = (int)($output->pre_start_time != 0
          ? $output->pre_start_time
          : ($this->webIo[$output->webio_id]->pre_start_time->{$this->types[$output->webio_type_id]->alias} ??
             $this->webIo[$output->webio_id]->pre_start_time->all)
        );

        $this->outputs[$output->area_id . '_' . $output->webio_type_id] = $output;
      }
    }
  }

  public static function instance()
  {
    if (empty(self::$inst)) {
      self::$inst = new WebIOStateConfig();
    }

    return self::$inst;
  }

  public static function reset()
  {
    self::$inst = new WebIOStateConfig();

    return self::$inst;
  }

  function getQueryData($webIoId = false)
  {
    return $webIoId ? $this->webIo[$webIoId] : $this->webIo;
  }

  function getOutput($area_id, $type)
  {
    return (isset($this->outputs[$area_id . '_' . $type]) ? $this->outputs[$area_id . '_' . $type] : false);
  }

  function getOutputs()
  {
    return $this->outputs;
  }

  function getAreas()
  {
    $areas_data = array();
    foreach ($this->outputs as $area => $output) {
      $areas_data[$output->webio_id][$output->port] = explode('_', $area);
    }

    return $areas_data;
  }

  function getAreaPorts($area_id)
  {
    $out = array();
    foreach ($this->types as $type) {
      if (isset($this->outputs[$area_id . '_' . $type->id])) {
        $out[] = $this->outputs[$area_id . '_' . $type->id];
      }
    }

    return $out;
  }
}
