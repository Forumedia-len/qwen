<?php

namespace AC\core\engines;

use AC\app\locators\Service;
use AC\core\system\db\Query;
use AC\core\system\helpers\TranslateHelper;


class TableRules
{
  public  $rules;
  private $cash = [];

  /** Извлекаем таблицу
   * TableRules constructor.
   *
   * @param null $model
   */
  public function __construct($model = null)
  {
    $this->rules = Query::sqlQuery('SELECT * FROM ' . Query::tableName('club_reservation_rules'));
    if (empty($this->rules)) {
      $this->rules = [];
    }
  }

  /** выбираем строки по ограничениям false - не учитывает параметр,null - учитывает пустое поле
   *
   * @param  $area_id
   * @param  $area_type_id
   * @param  $sport_id
   * @param  $club_state
   * @param  $device
   *
   * @return array
   */

  public function getRules($area_id = false, $area_type_id = false, $sport_id = false, $club_state = false, $device = false)
  {
    $ret_rules = [];
    foreach ($this->rules as $rule) {
      if (($area_id === false || $rule['area_id'] == $area_id) &&
        ($area_type_id === false || $rule['area_type_id'] == $area_type_id) &&
        ($sport_id === false || $rule['sport_id'] == $sport_id) &&
        ($club_state === false || $rule['club_state'] == $club_state) &&
        ($device === false || $rule['device'] === $device)) {
        $ret_rules[] = $rule;
      }
    }

    return $ret_rules;
  }

  protected function getArrayPriority($area_id, $area_type_id, $sport_id, $club_state, $device)
  {
    return [
      [$area_id, null, null, $club_state, $device],
      [$area_id, null, null, $club_state, null],
      [$area_id, null, null, null, $device],
      [$area_id, null, null, null, null],
      [null, $area_type_id, $sport_id, $club_state, $device],
      [null, $area_type_id, $sport_id, $club_state, null],
      [null, $area_type_id, $sport_id, null, $device],
      [null, $area_type_id, $sport_id, null, null],
      [null, $area_type_id, null, $club_state, $device],
      [null, $area_type_id, null, $club_state, null],
      [null, $area_type_id, null, null, $device],
      [null, $area_type_id, null, null, null],
      [null, null, $sport_id, $club_state, $device],
      [null, null, $sport_id, $club_state, null],
      [null, null, $sport_id, null, $device],
      [null, null, $sport_id, null, null],
      [null, null, null, $club_state, $device],
      [null, null, null, $club_state, null],
      [null, null, null, null, $device]
    ];
  }

   protected function getMess($rule_id, $mess) {
        $str = lang('rule_id_' . $rule_id, 'message_error');
        if ($str == 'Rule id ' . $rule_id) {
            return $mess;
        } else {
            return $str;
        }
    }

  /** Задаем параметры, а получаем самое приоритетное ограничение, которое применимо
   *
   * @param null   $area_id
   * @param null   $area_type_id
   * @param null   $sport_id
   * @param null   $club_state
   * @param string $device
   *
   * @return mixed
   */
  public function rulePriority($area_id, $area_type_id, $sport_id, $club_state, $device)
  {
    if (isset($this->cash[$area_id][$area_type_id][$sport_id][$club_state][$device])) {
      return $this->cash[$area_id][$area_type_id][$sport_id][$club_state][$device];
    }
    $array_priority = [
      [$area_id, null, null, $club_state, $device],
      [$area_id, null, null, $club_state, null],
      [$area_id, null, null, null, $device],
      [$area_id, null, null, null, null],
      [null, $area_type_id, $sport_id, $club_state, $device],
      [null, $area_type_id, $sport_id, $club_state, null],
      [null, $area_type_id, $sport_id, null, $device],
      [null, $area_type_id, $sport_id, null, null],
      [null, $area_type_id, null, $club_state, $device],
      [null, $area_type_id, null, $club_state, null],
      [null, $area_type_id, null, null, $device],
      [null, $area_type_id, null, null, null],
      [null, null, $sport_id, $club_state, $device],
      [null, null, $sport_id, $club_state, null],
      [null, null, $sport_id, null, $device],
      [null, null, $sport_id, null, null],
      [null, null, null, $club_state, $device],
      [null, null, null, $club_state, null],
      [null, null, null, null, $device]
    ];
    foreach ($array_priority as $key => $item) {
      $rules = $this->getRules(
        (is_numeric($item[0]) || is_string($item[0])) ? (string)$item[0] : $item[0],
        (is_numeric($item[1]) || is_string($item[1])) ? (string)$item[1] : $item[1],
        (is_numeric($item[2]) || is_string($item[2])) ? (string)$item[2] : $item[2],
        (is_numeric($item[3]) || is_string($item[3])) ? (string)$item[3] : $item[3],
        $item[4]
      );
      if (isset($rules[0])) {
        if (($rules[0]['reservation_limit'] == 0) && ($key < 4)) {
          $rules[0]['error_reservation_limit'] = lang('You are not entitled to book this place', 'message_error');
          $rules[0]['error_reservation_limit'] = $this->getMess($rules[0]["rule_id"],$rules[0]['error_reservation_limit']);
        }
        if (($rules[0]['reservation_limit'] == 0) && ($key > 3)) {
          $rules[0]['error_reservation_limit'] = lang('You are not entitled to book this type of space', 'message_error');
          $rules[0]['error_reservation_limit'] = $this->getMess($rules[0]["rule_id"],$rules[0]['error_reservation_limit']);
        }
        if ($rules[0]['reservation_limit'] != 0) {
          $rules[0]['error_reservation_limit'] = lang('You have reached your limit of bookings!', 'message_error');
          $rules[0]['error_reservation_limit'] = $this->getMess($rules[0]["rule_id"],$rules[0]['error_reservation_limit']);
        }
        if ($key < 4) {
          $rules[0]['error_max_forward_reservation_days_count'] = lang('You are not entitled to book this place', 'message_error');
          $rules[0]['error_show_on']                            = lang('You are not entitled to book this place', 'message_error');
          $rules[0]['error_max_forward_reservation_days_count'] = $this->getMess($rules[0]["rule_id"],$rules[0]['error_max_forward_reservation_days_count']);
          $rules[0]['error_show_on'] = $this->getMess($rules[0]["rule_id"],$rules[0]['error_show_on']);
        }
        if ($key > 3) {
          $rules[0]['error_max_forward_reservation_days_count'] = lang('You are not entitled to book this type of space', 'message_error');
          $rules[0]['error_show_on']                            = lang('You are not entitled to book this type of space', 'message_error');
          $rules[0]['error_max_forward_reservation_days_count'] = $this->getMess($rules[0]["rule_id"],$rules[0]['error_max_forward_reservation_days_count']);
          $rules[0]['error_show_on'] = $this->getMess($rules[0]["rule_id"],$rules[0]['error_show_on']);
        }
        $this->cash[$area_id][$area_type_id][$sport_id][$club_state][$device] = $rules[0];

        return $rules[0];
      }
    }
    $this->cash[$area_id][$area_type_id][$sport_id][$club_state][$device] = false;

    return false;
  }

  public function getVisibleNamesByRule($rule, $flg_reg, $device)
  {
    if (!empty($rule['show_names'])) {
      $showNames = Service::cast('separator')->get(strtolower($rule['show_names'] ?? ''), ['separator' => ',']);
      $str = (($flg_reg) ? 'reg' : 'unreg') . '_' . $device;

      return in_array($str, $showNames);
    } else {
      return ($flg_reg && SHOW_NAMES_FOR_AUTHORIZED_USERS);
    }
  }

  /**
   * @param $rule
   * @param $flg_reg
   * @param $device
   * по умолчанию показывает все названия блокировок
   *
   * @return bool
   */
  public function getVisibleTitleBlockByRule($rule, $flg_reg, $device)
  {
    if ((isset($rule)) && (isset($rule['show_title_block']))) {
      $str = ($flg_reg) ? 'reg' : 'unreg';
      $str .= '_' . $device;

      return (strpos(strtolower($rule['show_title_block']), $str) !== false) ? true : false;
    } else {
      return Service::auth()->checkAuth() ? SHOW_BLOCK_NAMES_FOR_AUTHORIZED_USERS : SHOW_BLOCK_NAMES_FOR_USERS;
    }
  }

  public function getVisibleColumnByRule($rule, $flg_reg, $device)
  {
    if ((isset($rule)) && (isset($rule['show_area_column']))) {
      $str = ($flg_reg) ? 'reg' : 'unreg';
      $str .= '_' . $device;

      return (strpos(strtolower($rule['show_area_column']), $str) !== false) ? true : false;
    } else {
      return true;
    }
  }

  public function correctColumnLimitForRule($engine, $sport_id, $type_id, $device_type, $flg_reg, &$limit)
  {
    if (!isset($limit)) {
      return;
    }
    [$startPos, $colItem] = $limit;
    $club_state = $engine->clients->current_client_data['club_state'] ?? 1;
    $data       = [];
    $engine->areas->getAreasDataByType($type_id, $data, $sport_id);
    $key   = $count = 0;
    $start = false;
    foreach ($data as $pos => $area) {
      if ($rule = $this->rulePriority($area['area_id'], $type_id, $sport_id, $club_state, $device_type)) {
        if ($this->getVisibleColumnByRule($rule, $flg_reg, $device_type)) {

          if ($key == $limit[0]) {
            $startPos = $pos;
            $start    = true;
            $count    = 0;
          }
          if ($start && $count < $limit[1]) {
            ++$count;
            $pos2 = $pos;
          }
          ++$key;
        }
      }
    }
    if ($pos2) {
      $colItem = $pos2 - $startPos + 1;
    }
    $limit = [$startPos, $colItem];
  }

  /**
   * @param        $rule
   * @param        $time
   * @param        $date
   * @param string $error_message
   *
   * @return bool @todo временная заплатка нужно было задать правила для определенных кортов , чтобы можно было на таче бронировать только на 2 часа
   *              вперед использован столбец time_limit (???????????????)
   */
  public function checkTimeRule($rule, $time, $date, &$error_message = '')
  {
    if (!empty($rule) && $rule['rule_type'] == 'reservationInterval') {
      if (date('Y-m-d') != $date) {
        return false;
      }
      $unix_time         = strtotime($date . ' ' . $time);
      $now               = strtotime('now');
      $real_interval     = abs($unix_time - $now);
      $time_interval_min = Service::engines()->getTimeIntervalMin($rule['area_type_id']);
      $time_interval_max = $time_interval_min + ($rule['rule_type'] == 'reservationInterval' && $rule['rule_value']
          ? $rule['rule_value'] * 60 : 0);
      if (($rule['rule_type'] == 'reservationInterval' && $rule['rule_value']
        && $real_interval > $time_interval_max)) {
        // можно забранировать только заданый промежуток от текущего времени - только открытые корты
        $hours   = 0;
        $minutes = $rule['rule_value'];
        if ($rule['rule_value'] > 60) {
          $hours   = floor($rule['rule_value'] / 60);
          $minutes = $rule['rule_value'] - ($hours * 60);
        }

        $error_message = lang('error about booking not earlier than a certain time', 'message_error', [
          'hour'   => ($hours > 0 ? $hours . ' ' . lang('Hours') . ' ' : ''),
          'minute' => ($minutes > 0
            ? $minutes . ' ' . lang('Minutes') . ' ' : '')
        ]);

        return false;
      } elseif ($time_interval_min
        && $real_interval < $time_interval_min) {
        // можно сделать бронирование только за время после указанного промежутка - только открытые корты
        $error_message = lang(
          'You can only book the time after hours from the current one.',
          'message_error',
          ['interval' => TranslateHelper::translatePeriodInMinute($time_interval_min / 60)]
        );

        return false;
      }
    }

    return true;
  }

  //проверяет дни недели,
  //true - если отсутствует колонка с днями недели или дата, как и в случае если мы попадаем датой в день недели из условия.
  //false - если не попадаем в дни недели из условия
  public function checkWeekdays($date, $day_week_rule)
  {
    $dw = ($date && $day_week_rule) ? (date('w', strtotime($date))) : false;
    return (!$dw || in_array($dw, explode(',', $day_week_rule)));
  }

  public function checkMinCountPeriod($area_id = null, $area_type_id = null, $sport_id = null, $club_state = null, $device = null, $date = null)
  {
    $period = 0;
    foreach ($this->rules as $rule) {
      if ($rule['rule_type'] == 'minCountPeriod' && $this->checkWeekdays($date, $rule['weekdays'])) {
        if ($this->checkRuleForCompliance($rule, $area_id, $area_type_id, $sport_id, $club_state, $device)) {
          $period = (int)$rule['rule_value'];
        }
      }
    }
    return $period;
  }

  public function maximumNumberOfConsecutivePeriods($area_id = null, $area_type_id = null, $sport_id = null, $club_state = null, $device = null)
  {
    $period = 0;
    foreach ($this->rules as $rule) {
      if ($rule['rule_type'] == 'maxCountConsecutivePeriod') {
        if ($this->checkRuleForCompliance($rule, $area_id, $area_type_id, $sport_id, $club_state, $device)) {
          $period = (int)$rule['rule_value'];
        }
      }
    }

    return $period;
  }

  protected function checkRuleForCompliance($rule, $area_id, $area_type_id, $sport_id, $club_state, $device)
  {
    $keyPriority = [
      'area_id',
      'area_type_id',
      'sport_id',
      'club_state',
      'device'
    ];
    foreach ($this->getArrayPriority($area_id, $area_type_id, $sport_id, $club_state, $device) as $priority) {
      $check = true;
      foreach ($keyPriority as $key => $alias) {
        if ($rule[$alias] !== $priority[$key]) {
          $check = false;
        }
      }
      if ($check) {
        return true;
      }
    }

    return false;
  }

}