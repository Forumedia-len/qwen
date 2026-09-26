<?php

namespace AC\core\modules\areas\models;

use AC\core\engines\AreasEngine;
use AC\core\modules\areas\tables\AreasTypesTable;


class AreasTypesModel extends AreasTypesTable
{
  public $checkError;

  /**
   * @var AreasEngine
   */
  public $engine;


  public function __construct($type_id = null)
  {
    parent::__construct();
    $this->engine = new AreasEngine($this->engine);
    $this->setTypeData($type_id);
  }

  private function setTypeData($type_id)
  {
    if ($type_id !== null && $this->engine->getTypeData($type_id, $type_data)) {
      foreach ($type_data as $key => $data) {
        $this->{$key} = $data;
      }
      $this->checkError = false;
    } else {
      $this->checkError = true;
    }
  }

  public function selectAll(&$error_code)
  {
    if ($this->engine->getAreasTypesData($types_court)) {
      $_types_court = array();
      foreach ($types_court as $type_court) {
        $_types_court[] = (object)$type_court;
      }

      return $_types_court;
    }
    $error_code = lang('not data', 'message_error');

    return false;
  }

  public function getTypeData($type_id)
  {
    $this->engine->getTypeData($type_id, $type_data);

    return $type_data;
  }
}