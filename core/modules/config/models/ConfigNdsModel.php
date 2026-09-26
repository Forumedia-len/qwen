<?php

namespace AC\core\modules\config\models;

use AC\core\engines\NdsEngine;
use AC\core\modules\config\tables\ConfigNdsTable;

class ConfigNdsModel extends ConfigNdsTable
{
  protected $baseGetFunction        = 'getNdsById';
  protected $baseGetFunctionAllData = 'getAllNds';
  protected $primary_key            = 'nds_id';
  protected $baseEngine             = 'NdsEngine';
  /**
   * @var NdsEngine
   */
  protected $engine;

  /**
   * {@inheritDoc}
   */
  public function rules()
  {
    return array(
      array(array('rate', 'comment'), 'required'),
      array('rate', 'integer', array('length' => 3, 'min' => 0, 'max' => 99)),
      array('comment', 'string', array('max' => 250)),
      array('set_default', 'default', array('value' => 0)),
    );
  }

  public static function getDefaultId()
  {
    $model = new ConfigNdsModel();

    return $model->engine->getDefaultNdsId();
  }
}