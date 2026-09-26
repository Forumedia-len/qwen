<?php

namespace AC\core\modules\areas\models;
use AC\core\engines\SportsEngine;
use AC\core\modules\areas\tables\AreasSportsTable;

class AreasSportsModel extends AreasSportsTable
{
  public $checkError;

  /**
   * @var SportsEngine
   */
  public $engine;


  public function __construct($id = null)
  {
    parent::__construct();
    $this->engine = new SportsEngine();
    $this->setTypeData($id);
  }

  private function setTypeData($id)
  {
    if ($id !== null && $sport_data = $this->engine->getSportById($id)) {
      foreach ($sport_data as $key => $data) {
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

}