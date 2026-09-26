<?php

namespace AC\core\modules\reservations\controllers;


use AC\core\modules\reservations\models\OrdersModelMcArenaReservation;
use Service;

class McArenaControllerReservation extends CloseControllerReservation
{
  public $default_template = 'mc_arena';
  public $base_model = 'OrdersModelMcArenaReservation';

  /**
   * @var OrdersModelMcArenaReservation
   */
  public $model;

  public function proceedOrder()
  {
    if($this->admin || (!(defined('CORONA_MC_ARENA') && CORONA_MC_ARENA && MC_ARENA) || Service::request()->_('user_right', false)))
    {
      return parent::proceedOrder();
    }

    return $this->view->getErrorMessage();
  }

}