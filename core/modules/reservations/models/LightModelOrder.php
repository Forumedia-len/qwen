<?php

namespace AC\core\modules\reservations\models;

use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\helpers\TranslateHelper;

use AC\core\modules\areas\models\AreasLightsModel;
use AC\core\modules\webio\models\WebIoTypeSatesModel;
use Service;

/**
 * Class LightModelOrder
 *  todo доработать снятие денег при включении света
 */
class LightModelOrder extends OrdersModelReservation
{
  public $ticket_id;
  public $stateLHN;

  /** Подготовить управление светом после загрузки данных заказа. */
  protected function initializeData(): void
  {
    parent::initializeData();
    $this->stateLHN  = array(
      'light'   => (object)array(
        'type'  => 1,
        'title' => 'Licht'
      ),
      'heating' => (object)array(
        'type'  => 2,
        'title' => 'Heizung'
      ),
      'net'     => (object)array(
        'type'  => 3,
        'title' => 'Netz'
      )
    );
    $this->ticket_id = Service::request()->validated('ticket_id', 'request', [['integer', ['min' => 1]]]);
  }

  public function prefixTitle()
  {
    return ' - '. lang('Light control', 'order');
  }

  public function getOrder(&$error_code)
  {
    if ($this->checkData($error_code)) {
      $get_unix_time = strtotime($this->date);
      $_times        = array();
      foreach ($this->times as $time) {
        $_times[] = (object)array(
          'title' => $time . ' - ' . TimeHelper::convertMinutes2MySQLTime(TimeHelper::convertMySQLTimeToMinutes($time) + $this->areas->period),
          'time'  => $time
        );
      }
      $this->checkStateLHN();

      $content = array(
        'model'     => $this,
        'area_data' => $this->areas,
        'date'      => (object)array(
          'date'    => date('d.m.Y', $get_unix_time),
          'weekday' => TranslateHelper::translateWeekday(CalendarHelper::getWeekdayByUnixtime($get_unix_time)),
          'times'   => $_times
        ),
      );

      return $content;
    } else {
      return false;
    }
  }

  protected function checkStateLHN()
  {
    $unix_time = strtotime($this->date . ' ' . $this->time);
    $weekday   = CalendarHelper::getWeekdayByUnixtime($unix_time);
    $this->engine->getReservationData($this->area_id, $this->date . ' ' . $this->time, $reservation_data);
    foreach ($this->stateLHN as $key => $state) {
      if ($this->ticket_id !== null) {
        $_state = $this->engine->tickets->checkLightTicket($state->type, $this->area_id, $this->date,
          date('H:i:s', $unix_time), $weekday);
      } else {
        $_state = $reservation_data[$key . '_state'];
      }
      if ($this->areas->{$key . '_on'} == 1) {
        $this->stateLHN[$key]->state  = 1;
        $this->stateLHN[$key]->hidden = (int)AreasLightsModel::checkAreasDefaultState($this->area_id, $weekday,
            date('H:i:s', $unix_time), $state->type) || $_state == 1;
      } else {
        $this->stateLHN[$key]->state = 0;
      }
    }
  }

  /** Занести свет в бронирование и включить если игра уже идет
   *
   * @param bool|array $states
   * @param            $error
   *
   * @return bool|void
   */
  public function switchStateLHN(&$error, $states = false)
  {
    if (!$states) {
      $states = $this->stateLHN;
    }
    $unix_time          = strtotime($this->date . ' ' . $this->time);
    $weekday            = CalendarHelper::getWeekdayByUnixtime($unix_time);
    $electricity_action = array();
    $_st                = array();
    foreach ($states as $key => $state) {
      $price = $this->areas->{$key . '_price'};
      if ($this->{$key . '_state'} !== null && $this->{$key . '_state'} == 1) {
        $st = 0;
        if ($this->ticket_id !== null) {
          if ($this->engine->tickets->insertLightHeatingTicket($this->ticket_id,
            $state->type, date('Y-m-d', $unix_time), date('H:i', $unix_time), $weekday)) {
            $st = 1;
          }
        } else {
          $set = true;
          if($this->open) {
            $this->engine->getReservationData($this->area_id, $this->date . ' ' . $this->time, $reservation_data);
            if($this->{$key . '_state'} != $reservation_data[$key . '_state']) {
              if($reservation_data['main_reservation_id'] === null) {
                $price += $reservation_data[$key . '_price'];
              } else {
                $this->engine->getReservationDataById($reservation_data['main_reservation_id'], $main_reservation_data);
                $price += $main_reservation_data[$key . '_price'];
              $this->engine->changeReservationLightHeatingState($this->area_id, $state->type, $main_reservation_data['start'], $main_reservation_data[$key . '_state'], $price);
                $price = false;
              }
            } else {
              $set = false;
            }
          }
          if ($set && $this->engine->changeReservationLightHeatingState($this->area_id, $state->type,
            date('Y-m-d H:i:s', $unix_time), 1, $price)) {
            $st = 1;
          }
        }
        $_st[$key] = $st;
        if ($st == 1 && date('Y-m-d') == date('Y-m-d', $unix_time) && date('H:i') >= date('H:i', $unix_time)) {
          $electricity_action[$this->area_id][$state->type] = 1;
        }
      }
    }
    if (count($electricity_action) > 0) {
      $this->engine->webIo->areasElectricityProcess($electricity_action, $error);
    }

    if(count($_st)> 0) {
      return true;
    } else {
      return false;
    }
  }

  public function proceed(&$error_code)
  {
    if ($this->checkData($error_code)) {
      if ($this->switchStateLHN($error_code)) {
        $this->engine->webIo->sendFTPCurrentIcal($this->engine, $this->area_id, $this->getDate());   
        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

  /** Проверяет карту состояний и выбирает флаги света, отопления и сетки для текущего периода. */
  protected function setStateWebIo()
  {
    foreach (array_keys(WebIoTypeSatesModel::getTypeSates('alias', true, false, true)) as $alias) {
      $states = Service::request()->validated($alias . '_state', 'request',
        [['time', ['allowArray' => true, 'arrayOnly' => true]]], []);
      $this->{$alias . '_state'} = $states[$this->time] ?? 0;
    }
  }

}
