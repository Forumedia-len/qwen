<?php

namespace AC\core\modules\reservations\controllers;

use AC\core\modules\reservations\config\DoubleGameConfig;
use AC\core\modules\reservations\models\DoubleOrdersModelOpenReservation;

class DoubleOpenControllerReservation extends OpenControllerReservation
{
  public $base_model = 'DoubleOrdersModelOpenReservation';

  /**
   * @var DoubleOrdersModelOpenReservation
   */
  public $model;

  public function getContentShowOrder(&$error_code)
  {
    $content                    = parent::getContentShowOrder($error_code);
    if ($content) {
      $content['numberOfPeriods'] = '';
      /** @var DoubleGameConfig $doubleGame */
      $doubleGame = config('DoubleGame');
      $typeId     = $this->model->type_id;
      $sportId    = $this->model->sport_id;
      $areaId     = $this->model->area_id;
      if ($doubleGame->useMaximumPeriodValue($typeId, $sportId, $areaId)) {
        $checkMaxNumberPeriods = $this->model->checkMaxNumberPeriodsForDoublePlay();
        $numberPeriodsStart    = $doubleGame->getNumberOfPeriods($typeId, $sportId, $areaId);
        $numberPeriodsEnd      = $checkMaxNumberPeriods
          ? $doubleGame->getMaxNumberOfPeriods($typeId, $sportId, $areaId)
          : $numberPeriodsStart;
        $content['numberOfPeriods'] = $this->view->render('number_period', [
          'period'                 => $this->model->areas->period,
          'checkMaxNumberPeriods'  => $checkMaxNumberPeriods,
          'numberPeriodsStart'     => $numberPeriodsStart,
          'numberPeriodsEnd'       => $numberPeriodsEnd,
          'numberPeriodsDefault'   => $numberPeriodsStart,
        ]);
      }
    }

    return $content;
  }

}