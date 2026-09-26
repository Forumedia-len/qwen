<?php

namespace AC\core\modules\config\controllers;

use AC\core\modules\config\models\ConfigReservationModel;

class ConfigReservationControllerAdmin extends ConfigControllerAdmin
{

  protected $base_model       = 'ConfigReservationModel';
  public    $default_template = 'reservation';
  /**
   * @var ConfigReservationModel
   */
  public $model;
}