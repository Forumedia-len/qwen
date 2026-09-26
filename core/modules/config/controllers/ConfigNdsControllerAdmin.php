<?php

namespace AC\core\modules\config\controllers;

use AC\core\modules\config\models\ConfigNdsModel;

class ConfigNdsControllerAdmin extends ConfigControllerAdmin
{
  public    $default_action   = 'showList';
  protected $base_model       = 'ConfigNdsModel';
  public    $default_template = 'nds';

  /**
   * @var ConfigNdsModel
   */
  public $model;
  /**
   * @var ConfigNdsModel
   */
  protected $_model;

  public function showList()
  {
    return array(parent::showList(), $this->create());
  }

}