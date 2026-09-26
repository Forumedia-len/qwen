<?php

namespace AC\core;

use AC\app\services\DataService;
use AC\core\engines\Engines;
use AC\core\modules\config\models\ConfigClubStateModel;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\ConfigHelper;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\StringHelper;
use AC\core\system\helpers\TranslateHelper;
use AC\core\system\object\BaseObject;
use AC\core\system\view\View;
use AC\core\engines\UsersEngine;

use Service;

class ReservationsVisualizationCommon
{

  /** Показывать кнопку удаления абонимента клиенту
   *
   * @var bool
   */
  public static $removeTicket = true;

  /** показывать имя и фамилию на фронтенде
   *
   * @var bool
   */
  public static $viewClientName = false;

  /**
   * @var View $view
   */
  public static $view;

  public static $limitDaysReservation = [];

  /** Генератор календаря
   *
   * @param Engines      $engine
   * @param              $mysql_date_selected - дата
   * @param              $type_id             - тип площадки
   * @param              $sport_id            - тип спорта
   * @param              $page                - номер страницы
   * @param bool         $admin               - админка
   * @param bool         $is_bar
   *
   * @return false|string
   */
  public static function renderCalendar(
    Engines $engine,
    $mysql_date_selected,
    $type_id,
    $sport_id,
    $page,
    bool $admin = false,
    bool $is_bar = false,
  ) {
    self::setView();
    if (!$is_bar) {
      $engine->setMaxReservationUnixTime($type_id, $sport_id);
    }
    $unix_date = strtotime($mysql_date_selected);
    $month     = date('m', $unix_date);
    $year      = date('Y', $unix_date);
    $months    = [];
    $month_max = date('Y-m');
    $DiffMonth = 0;
    if (!$is_bar) {
      $month_max = date('Y-m', $engine->getMaxReservationUnixtime());
    }
    $chk_days = 0;
    if (!$is_bar && !$admin && PERIOD_SHOW_CALENDAR) {
      $cntDays = ConfigHelper::parseStringToVariables(PERIOD_SHOW_CALENDAR, 'PERIOD_SHOW_CALENDAR');
      if (!empty($cntDays["{$type_id}_{$sport_id}"])) {
        $chk_days  = $cntDays["{$type_id}_{$sport_id}"];
        $month_max = date('Y-m', strtotime("now + {$chk_days} days"));
      }
    }

    if ($admin == true) {
      $month_max      = date('Y-m', strtotime($month_max . '-01 + 12 month'));
      $minDate        = $engine->getMinReservationDate($type_id, $sport_id);
      $adminDiffMonth = (((int)date('Y') - (int)date('Y', strtotime($minDate))) * 12) + (date('m') - (int)date('m', strtotime($minDate))) + 2;
      if ($adminDiffMonth < 24) {
        $adminDiffMonth = 24;
      }
    }//админу - показывем 3 доп. месяца вперед
    //начальный месяц относительно текущего
    $month_difference = $admin == true ? -($adminDiffMonth ?? 12) : 0;
    $current_month    = date('Y-m-01 ');
    do {
      $d       = strtotime($current_month . ($month_difference >= 0 ? ' + ' : ' ') . $month_difference . ' month');
      $actions = [];
      if (date('Y-m', $d) == date('Y-m', $unix_date)) {
        if (date('Y-m', $d) != date('Y-m')) {
          $actions[] = (object)[
            'action' => 'prev',
            'date'   => date('Y-m-01', strtotime(date('Y-m-01', $unix_date) . ' - 1 month')),
          ];
        }
        $tmp_date = strtotime($year . '-' . $month . '-01' . ' +1 month');
        if ($engine->checkDateAvaliableByUnixtime($tmp_date)
          || ConfigHelper::checkDateInInterval(date('Y-m-d'), $chk_days, date('Y-m-d', $tmp_date))) {
          $actions[] = (object)[
            'action' => 'next',
            'date'   => date('Y-m-01', strtotime(date('Y-m-01', $unix_date) . ' + 1 month')),
          ];
        }
      }
      $months[] = (object)[
        'title'   => TranslateHelper::translateMonth((int)date('m', $d)),
        'year'    => date('Y', $d),
        'month'   => date('m', $d),
        'date'    => date('Y-m', $d) . '-' . (date('Y-m', $d) == date('Y-m') ? date('d') : '01'),
        'active'  => (date('Y-m', $d) == date('Y-m', $unix_date)) ? true : false,
        'actions' => $actions,
      ];
      $d        = date('Y-m', $d);
      $month_difference++;
    } while ($month_max != $d);
    //НАВИГАЦИЯ ПО ДНЯМ ТЕКУЩЕГО МЕСЯЦА
    $date = 1 - date('w', mktime(0, 0, 0, $month, 0, $year));
    $last = date('t', mktime(0, 0, 0, $month, 1, $year));

    //праздники
    $holidays      = $engine->holidays->getHolidaysByMonth($year, $month);
    $areaWorkdays  = $is_bar ? null : self::getAreaWorkdays($engine, $type_id, $sport_id);
    $weeks         = [];
    while ($date <= $last) {
      $days = [];
      for ($i = 0; $i < 7; $i++, $date++) {
        $class          = '';
        $date_formatted = sprintf('%02d', $date);
        if (0 < $date && $date <= $last) {
          $sel_date = $year . '-' . $month . '-' . $date;
          $isAreaWorkday = self::isAreaWorkday($areaWorkdays, $sel_date);
          if (!$isAreaWorkday) {
            $class = $admin ? 'unAvaliable' : 'locked';
          } elseif ((int)isset ($holidays[$date_formatted])) {
            $class = 'holiday';
          } elseif (ConfigHelper::checkDateInInterval(date('Y-m-d'), $chk_days, $sel_date)
            || $engine->checkDateAvaliableByUnixtime(strtotime($sel_date), $admin)) {
            if (date('Y', $unix_date) . '-' . date('m', $unix_date) . '-' . $date == date('Y-m-j', $unix_date)) {
              $class = $admin ? 'selected' : 'select_day';
            } elseif (date('Y', $unix_date) == date('Y')
              && date('m', $unix_date) == date('m')
              && $date == date('d')) {
              $class = $admin ? 'current' : 'today';
            } else {
              $class = $admin ? 'avaliable' : '';
            }
          } else {
            if ($admin && date('Y', $unix_date) . '-' . date('m', $unix_date) . '-' . $date == date(
                'Y-m-j',
                $unix_date
              )) {
              $class = 'selected';
            } else {
              $adminClass = 'unAvaliable';
//              if (USE_ADMIN_ALL_TIME) {
//                $date_call = mktime(0, 0, 0, $month, $date, $year);
//                if (date('Y-m') > date('Y-m', $date_call)) {
//                  $adminClass = 'unAvaliable';
//                }
//                if (date('Y-m') < date('Y-m', $date_call)) {
//                  $adminClass = 'avaliable';
//                }
//                if (date('Y-m') == date('Y-m', $date_call) && (date('d') > $date)) {
//                  $adminClass = 'unAvaliable';
//                }
//                if (date('Y-m') == date('Y-m', $date_call) && (date('d') <= $date)) {
//                  $adminClass = 'avaliable';
//                }
//              }
              $class = $admin ? $adminClass : 'locked';
            }
          }
          $days[] = (object)[
            'day'   => $date_formatted,
            'class' => $class,
          ];
        } else {
          $days[] = (object)[];
        }
      }
      $weeks[] = $days;
    }
    if (!$is_bar) {
      $rt        = $engine->areas->getAreasPages($type_id, $sport_id);
      $curr_area = current(current(current($rt)));
    }
    $calender = [
      'date'     => (object)[
        'current_day'      => date('d'),
        'current_week_day' => TranslateHelper::translateWeekday(CalendarHelper::getWeekdayByUnixtime(strtotime(date('Y-m-d')))),
        'current_year'     => date('Y'),
        'current_month'    => TranslateHelper::translateMonth(date('m')),
        'selected_date'    => $mysql_date_selected,
        'selected_year'    => date('Y', $unix_date),
        'selected_day'     => date('d', $unix_date),
        'selected_month'   => date('m', $unix_date),
      ],
      'type_id'  => $type_id,
      'area_id'  => (int)Service::request()->_('area_id', $curr_area['area_id']),
      'sport_id' => $sport_id,
      'page'     => $page,
      'months'   => $months,
      'weeks'    => $weeks,
      'week_id'  => (int)Service::request()->_('week', 0),
      'is_bar'   => $is_bar,
    ];

    return self::$view->render('_calendar', $calender);
  }

  /** Генератор таблицы бронирования
   *
   * @param Engines   $engine     - общий класс резервации
   * @param string    $mysql_date - выбранное время
   * @param int|array $type_id    - тип площадки
   * @param int       $sport_id   - тип спорта
   * @param int       $page       - номер страницы
   * @param int       $limit      -
   * @param int|bool  $week       - недельный вывод
   * @param bool      $admin      - вывод для админа
   *
   * @param bool      $touch      - вывод для тача
   *
   * @param bool      $display    - отображение на дисплее
   *
   * @return bool|string
   */
  public static function renderAreasTypeColumns(
    $engine,
    $mysql_date,
    $type_id,
    $sport_id,
    $page,
    $limit,
    $week = 0,
    $admin = false,
    $touch = false,
    $display = false,
  ) {
    $content = [];
    self::setView(['']);
    $isArray  = is_array($type_id);
    $type_ids = $isArray ? $type_id : [0 => ["type_id" => $type_id, "sport_id" => $sport_id]];

    $areaPage = USE_PAGE_BREAK_FROM_BASE_AREA ? (int)$page : null;
    $maxPeriods = self::getMaxPeriodsArray($engine, $type_ids, $limit, $mysql_date, $areaPage);
    foreach ($type_ids as $tp) {
      $type_id        = $tp['type_id'];
      $sport_id       = $tp['sport_id'];
      $unix_time_date = strtotime($mysql_date);
      $engine->setMaxReservationUnixTime($type_id, $sport_id);
      $client = $engine->clients->current_client_data;
      //можно ли этому клиенту делать заказ, в зависимости от ограничений по количеству дней
      self::$limitDaysReservation = [];
      $unix_current_date          = $unix_time_date;
      for ($i = 1; $i <= ($week ? 7 : 1); $i++) {
        self::$limitDaysReservation[date('Y-m-d', $unix_current_date)] = true;
        if (!$admin && $engine->clients->checkAuthorization() && $client['limit_day'] > 0) {
          $unix_limit_day                                                = strtotime(date('Y-m-d') . '+' . ($client['limit_day'] - 1) . ' day');
          self::$limitDaysReservation[date('Y-m-d', $unix_current_date)] = $unix_current_date <= $unix_limit_day;
        }
        $unix_current_date = mktime(
          date('H', $unix_time_date),
          date('i', $unix_time_date),
          date('s', $unix_time_date),
          date('m', $unix_time_date),
          (date('d', $unix_time_date) + $i),
          date('Y', $unix_time_date)
        );
      }

      $areas = (!$week)
        ? self::getAreasColumn($engine, $type_id, $limit, $mysql_date, $sport_id, $admin, $touch, $display, $maxPeriods, $areaPage)
        : self::getAreasWeek($engine, $type_id, $limit, $mysql_date, $sport_id, $touch, $areaPage);

      if ($areas == false) {
        if ($display) {
          continue;
        } else {
          return false;
        }
      }

      $contentTemp = [
        'sports_tabs'    => self::getSportsTabs($engine, $type_id, $sport_id),
        'pages_tabs'     => self::getPagesTabs($engine, $type_id, $sport_id, $touch, lang('Places')),
        'weeks_tabs'     => (!$admin) ? self::getWeeksTabs() : false,
        'sport_id'       => $sport_id,
        'type_id'        => $type_id,
        'week'           => $week,
        'page'           => $page,
        'date'           => $mysql_date,
        'client'         => $client,
        'client_bar'     => (!$admin && $engine->clients->checkAuthorization()) || isset($_SESSION['reservation_comment']),
        'client_id'      => (!$admin && $engine->clients->checkAuthorization()) ? $client['client_id'] : null,
        'on_reservation' => self::$limitDaysReservation,
        'areas'          => $areas,
        'courtType'      => self::getTypeCourtByTypeId($type_id, $engine),
        'title_S_T'      => $engine->areas->getTitleByTypeAndSport($type_id, $sport_id, 'title_site_url'),
      ];
      if (!$isArray) {
        $content = $contentTemp;
      } else {
        $content[] = $contentTemp;
      }
    }

    return self::$view->render('_table', $content);
  }

  public static function checkLimitDayReservation($date, $limitDays): bool
  {
    return isset($limitDays[$date]) && $limitDays[$date];
  }

  /** Используется для формирования массива времени по указанным площадкам и спортам для построения красивого табло
   *
   * @param Engines $engine
   * @param         $types
   * @param         $limit
   * @param         $mysql_date
   *
   * @return array|mixed
   */
  protected static function getMaxPeriodsArray(
    $engine,
    $types,
    $limit,
    $mysql_date,
    $areaPage = null,
  ) {
    $area_with_max_periods_period_count  = 0;
    $area_with_max_periods_periods_array = [];
    foreach ($types as $type) {
      $areas_data = [];
      if ($engine->getPeriodsByAreasTypeDate(
        $type['type_id'],
        $limit,
        $mysql_date,
        $areas_data,
        $error_code,
        $type['sport_id'],
        $areaPage
      )) {
        //Ищем максимальное количество временных периодов по площадкам
        foreach ($areas_data as $area_id => $area_data) {
          if (isset($area_data[2])) {
            $period_count = count($area_data[2]);
            if ($period_count > $area_with_max_periods_period_count) {
              $area_with_max_periods_period_count  = $period_count;
              $area_with_max_periods_periods_array = $area_data[2];
            }
          }
        }
      }
    }

    return $area_with_max_periods_periods_array;
  }

  /** Генератор площадок как колонки для закрытых кортов
   *
   * @param Engines      $engine
   * @param              $type_id
   * @param              $limit
   * @param              $mysql_date
   * @param              $sport_id
   *
   * @param bool         $admin
   *
   * @param bool         $touch
   * @param bool         $display
   *
   * @return array|bool
   */
  protected static function getAreasColumn(
    $engine,
    $type_id,
    $limit,
    $mysql_date,
    $sport_id,
    $admin = false,
    $touch = false,
    $display = false,
    $maxPeriods = false,
    $areaPage = null,
  ) {
    $flg_show_day = ($admin || $engine->checkDateAvaliableByUnixtime(strtotime($mysql_date), $admin));
    $engine->setMaxReservationUnixTime($type_id, $sport_id);
    $device_type = 'pc';
    if ($touch) {
      $device_type = 'touch';
    }
    $arr_rename = [];
    if ($display) {
      $device_type = 'display';
      if (DISPLAY_RENAME) {
        $arr_title = explode(';', DISPLAY_RENAME);
        foreach ($arr_title as $str_title) {
          $temp                       = explode(':', $str_title);
          $arr_rename[trim($temp[0])] = trim($temp[1]);
        }
      }
    }
    if (!$admin && $areaPage === null) {
      $engine->rules->correctColumnLimitForRule(
        $engine,
        $sport_id,
        $type_id,
        $device_type,
        !empty($engine->clients->current_client_data),
        $limit
      );
    }
    $areas_table_temp = $engine->areas->all();
    foreach ($areas_table_temp as $item) {
      $areas_table[$item->area_id] = $item;
    }
    $areasData = DataService::areas();
    if ($engine->getPeriodsByAreasTypeDate(
      $type_id,
      $limit,
      $mysql_date,
      $areas_data,
      $error_code,
      $sport_id,
      $areaPage
    )) {
      $unix_time_date = strtotime($mysql_date);
      $weekday        = CalendarHelper::getWeekdayByUnixtime($unix_time_date);
      $season         = $engine->areas->getPeriodByDate(date('Y-m-d', strtotime($mysql_date)));
      $client         = $engine->clients->current_client_data;
      $isOpenType     = config('reservations')->isOpenType((int)$type_id);
      // Логины всех клиентов нужны только тач/стрит с SHOW_LOGIN_INSTEAD_NAME_ON_STREET;
      // иначе — лишний SELECT по всей таблице clients на каждый просмотр расписания (сайт ПК).
      $needClientsLogin = $touch && $isOpenType && defined('SHOW_LOGIN_INSTEAD_NAME_ON_STREET') && SHOW_LOGIN_INSTEAD_NAME_ON_STREET;
      $clients_login    = $needClientsLogin ? $engine->clients->getLoginByClientIds() : [];
      // Наценки по type/sport одни на всю таблицу; раньше getExtraByTypeAndSport() вызывался на каждый
      // временной слот (десятки SQL и тяжёлая сборка rate[] за один просмотр расписания).
      $scheduleExtra                = $engine->extra->getExtraByTypeAndSport($type_id, $sport_id);
      $clubRateMarkId               = ConfigClubStateModel::getMark('club_rate')->id;
      $noClubRateMarkId             = ConfigClubStateModel::getMark('no_club_rate')->id;
      $clientsInaccessibilityEngine = (!$admin && !empty($client))
        ? module('clients')->useModel()->getEngine()
        : null;
      $areas                        = [];
      //Ищем максимальное количество временных периодов по площадкам
      if ($maxPeriods == false) {
        $area_with_max_periods_period_count  = 0;
        $area_with_max_periods_area_id       = 0;
        $area_with_max_periods_periods_array = [];
        foreach ($areas_data as $area_id => $area_data) {
          if (isset($area_data[2])) {
            $period_count = count($area_data[2]);
            if ($period_count > $area_with_max_periods_period_count) {
              $area_with_max_periods_period_count  = $period_count;
              $area_with_max_periods_area_id       = $area_id;
              $area_with_max_periods_periods_array = $area_data[2];
            }
          }
        }
      } else {
        $area_with_max_periods_periods_array = $maxPeriods;
      }

      $count_game_period = $currentReservationId = $numberOfPeriods = $count_game_period2 = 0;

      foreach ($areas_data as $area_id => $area_data) {
        $rule                 = $engine->rules->rulePriority(
          $area_id,
          $type_id,
          $sport_id,
          isset($engine->clients->current_client_data['club_state']) ? $engine->clients->current_client_data['club_state'] : null,
          $device_type
        );
        $interval             = ($unix_time_date - strtotime(date('Y-m-d'))) / 24 / 3600;
        $flg_rule_count       = (!empty($rule) && isset($rule['max_forward_reservation_days_count']))
          ? ($interval < $rule['max_forward_reservation_days_count']) : true;
        $flg_area_link        = (!empty($rule) && isset($rule['show_on'])) ? (strtolower($rule['show_on']) == $device_type) : true;
        $flg_show_names       = $engine->rules->getVisibleNamesByRule($rule, (!empty($client)) ? true : false, $device_type);
        $flg_show_title_block = $engine->rules->getVisibleTitleBlockByRule($rule, (!empty($client)) ? true : false, $device_type);
        if (!$admin) {
          $flg_show_column = $engine->rules->getVisibleColumnByRule($rule, (!empty($client)) ? true : false, $device_type);
          if (!$flg_show_column) {
            continue;
          }
        }
        $area           = [
          'id'          => $area_id,
          'title'       => htmlspecialchars(!empty($arr_rename[$area_data[0]]) ? $arr_rename[$area_data[0]] : $area_data[0], ENT_QUOTES),
          'short_title' => htmlspecialchars($area_data['short_title'], ENT_QUOTES),
          'light_on'    => (int)$area_data[4],
          'heating_on'  => (int)$area_data[5],
          'net_on'      => (int)$area_data[6],
        ];
        $periods        = [];
        $reservation_on = true;
        if ($isOpenType && !$admin && !$display) {
          /** @todo  переделать на таблицу с правилами */
          switch (OPEN_RESERVATION_ON_DEVICE) {
            case 'touch' :
              $reservation_on = $touch ? true : $area_data['online_reservation'] == 1;
              break;
            default :
              break;
          }
        }
        if (isset($area_data[2])) {
          foreach ($area_data[2] as $period_start => $period) {
            $flg_time_rule = $admin || $engine->rules->checkTimeRule($rule, $period_start, $mysql_date, $error_message);

            $showOrder = true;
            $pr        = explode(':', $period_start);
            if (RESERVATION_ONLY_POSSIBLE_FROM_FULL_HOUR && $pr[1] != RESERVATION_ONLY_POSSIBLE_FROM_FULL_HOUR) {
              $showOrder = false;
            }
            $find_in_friend = false;
            if (isset($period[2]['street_friends'])) {
              foreach ($period[2]['street_friends'] as $friend) {
                if ($friend && $friend['id'] == $client['client_id']) {
                  $find_in_friend = true;
                }
              }
            }
            $thisCurrentClientGame = (!empty($client['client_id'])
              && (isset($period[2]) && ((isset($period[2]['main_client_id']) && $client['client_id'] == $period[2]['main_client_id'])
                  || (isset($period[2]['client'][0]) && $client['client_id'] == $period[2]['client'][0])
                  || $find_in_friend
                  /* || (isset($period[2]['reservation_id']) && isset($area_data[2][$period[0]][2]['main_reservation_id'])
                     && $area_data[2][$period[0]][2]['main_reservation_id'] == $period[2]['reservation_id'])*/)
                /*|| (isset($area_data[2][$period[0]][2]['client']['client_id']) && $area_data[2][$period[0]][2]['client']['client_id'] == $client['client_id'])*/));
            // проверка забронировать период
            $reservation_on = (isset($client['super']) && $client['super']) || ($engine->checkingForReservationsBasedOnTimeConditions(
                  $mysql_date,
                  $period_start,
                  $type_id,
                  $error_message,
                  $touch,
                  $sport_id
                ) && $engine->getLastReserv(
                  $mysql_date,
                  $period_start,
                  $type_id,
                  $area_id,
                  $touch
                ) && $engine->checkThePossibilityOfReservationByClubRule($rule, [$period_start], $mysql_date));
            //берем цены для периода
            //день недели

            $periodPrice = $isOpenType && (!isset($client['club_state']) || $client['club_state'] !== 1)
              ? 0
              : $areasData[$area_id]->getPrice($season, $weekday, $period_start);

            if (config('holidays')->useHolidayAsSanday() && !$isOpenType) {
              $weekday = $engine->pricing->getPriceWeekday($mysql_date, $period_start);
              $engine->pricing->getPeriodPrice(
                $area_id,
                $engine->clients->current_client_data['client_id'] ?? null,
                $mysql_date,
                $period_start,
                $periodPrice,
                $extra_price
              );
            }
            //берем наценки (объект $scheduleExtra — один раз на страницу, см. выше)
            $extra = $scheduleExtra;
            $sale  = $periodPrice + ($extra->rate[$clubRateMarkId]->extra->use_time ? $extra->rate[$clubRateMarkId]->extra->weekPrices[$weekday][$period_start . ':00'] : $extra->club_rate);
            if ($engine->clients->checkAuthorization() == true && !$engine->clients->isBar()) {
              $club_state = $client['club_state'];
            } else {
              $club_state = $noClubRateMarkId;
            }
            $periodPrice    = $periodPrice + ($extra->rate[$club_state]->extra->use_time
                ? $extra->rate[$club_state]->extra->weekPrices[$weekday][$period_start . ':00'] : $extra->rate[$club_state]->rate);
            $periodPrice    = NumberHelper::valute($periodPrice);
            $sale           = NumberHelper::valute($sale);
            $_action        = false;
            $type_game      = isset($period[2]['type_reservation']) ? $period[2]['type_reservation'] : 1;
            $street_friends = isset($period[2]['street_friends']) ? $period[2]['street_friends'] : [];
            $second_player  = null;

            if (!empty($period[2]['reservation_id']) && $period[2]['main_reservation_id'] !== $currentReservationId && $period[2]['reservation_id'] != $currentReservationId) {
              $currentReservationId = $period[2]['reservation_id'];
              $count_game_period    = 0;
              $numberOfPeriods      = $period[2]['numberOfPeriods'] ?? 0;
            }
            $name = isset($period[2]['client']) ? $period[2]['client'][1] . ' ' . $period[2]['client'][2] : '';
            if ($display) {
              $name = isset($period[2]['client']) ? $period[2]['client'][1][0] . '. ' . $period[2]['client'][2] : '';
            }
            if ($type_game == 1 &&
              !empty($period[2]['reservation_id']) &&
              $isOpenType &&
              $areas_table[$area_id]->period == '15') {
              $count_game_period2 %= 4;
              ++$count_game_period2;
              switch ($count_game_period2) {
                case 1:
                case 2:
                  break;
                default:
                  $name = "";
              }

            }
            // Если игра одиночная и второй игрок - гость, то выводим его из друзей.
            if ($type_game == 1 && !empty($period[2]['client']) && empty($period[2]['client']['client_id']) && !empty($street_friends[2])) {
              $name = $street_friends[2]['name'];
            }

            //echo $name;
            //при игре с друзьями выбираем расположение друзей в бронировании в зависимости от количества интервалов

            if ($type_game > 1) {
              $count_game_period %= ($numberOfPeriods ?: 1);
              ++$count_game_period;
              if ($numberOfPeriods == 2) {
                switch ($count_game_period) {
                  case 1:
                    $second_player = (object)$street_friends[2];
                    break;
                  case 2:
                    $name          = $street_friends[3]['name'];
                    $second_player = (object)$street_friends[4];
                    break;
                }
                //$second_player = (object)($period[2]['main_client_id'] == NULL ? $street_friends[3] : $street_friends[4]);
              }
              if ($numberOfPeriods == 3) {
                switch ($count_game_period) {
                  case 1:
                    $second_player = (object)$street_friends[3];
                    break;
                  case 2:
                    $name = $street_friends[2]['name'];
                    if ($street_friends[2]['id'] != 'guest' && !empty($street_friends[2]['id'])) {
                      $period[2]['client'][0] = $street_friends[2]['id'];
                    }
                    break;
                  case 3:
                    $name = $street_friends[4]['name'];
                    if ($street_friends[4]['id'] != 'guest' && !empty($street_friends[4]['id'])) {
                      $period[2]['client'][0] = $street_friends[4]['id'];
                    }
                }
              }
              if ($numberOfPeriods > 3) {
                switch ($count_game_period) {
                  case 1:
                    break;
                  case 2:
                    $name = $street_friends[2]['name'];
                    if ($street_friends[2]['id'] != 'guest' && !empty($street_friends[2]['id'])) {
                      $period[2]['client'][0] = $street_friends[2]['id'];
                    }
                    break;
                  case 3:
                    $name = $street_friends[3]['name'];
                    if ($street_friends[3]['id'] != 'guest' && !empty($street_friends[3]['id'])) {
                      $period[2]['client'][0] = $street_friends[3]['id'];
                    }
                    break;
                  case 4:
                    $name = $street_friends[4]['name'];
                    if ($street_friends[4]['id'] != 'guest' && !empty($street_friends[4]['id'])) {
                      $period[2]['client'][0] = $street_friends[4]['id'];
                    }
                    break;
                  default:
                    $name = "";
                }
              }
              if ($touch && $isOpenType && SHOW_LOGIN_INSTEAD_NAME_ON_STREET && $second_player->id !== null) {
                $second_player->name = $clients_login[$second_player->id]->login;
              }
            }

            if ($display) {
            }
            $gone_action                   = false;
            $admin_change_gone_reservation = false;
            $color                         = 'gray';
            $button_confirmation           = null;
            $memo                          = StringHelper::shield($period[2]['memo'] ?? '');
            $reservation_on                = self::checkLimitDayReservation($mysql_date,
                self::$limitDaysReservation) && $reservation_on && $flg_show_day;
            /**  period_state - статус периода, в виде битов (8) = [0 = прошел][1 = заблокирован][2 = одиночный заказ][3 = абонемент][4 = свет][5 = отопление][6 = сеть][7 = статус заказа]
             */
            switch (1) {
              case (int)$period[1][1]: // blocked == 1
                $class       = $admin ? 'period_blocked' : 'blocked';
                $class_touch = 'cell-item-blocked';
                $_client     = (object)[
                  'visible'        => $flg_show_title_block,
                  //$thisCurrentClientGame ? true : ($thisCurrentClientGame && $touch && $isOpenType ? true : self::$viewClientName),
                  'id'             => null,
                  'main_client_id' => isset($period[2]) && isset($period[2]['main_client_id']) ? $period[2]['main_client_id'] : null,
                  'name'           => !$admin
                    ? lang('blocked by the operator', 'schedule', [], htmlspecialchars($period[2]['block'] ?: ' ', ENT_QUOTES))
                    : htmlspecialchars($period[2]['block'], ENT_QUOTES),
                ];
                $state       = false;
                break;
              case (int)$period[1][0] || !$flg_time_rule: //gone == 1
                $class       = $admin ? 'period_unavaliable' : 'unAvailable';
                $class_touch = 'cell-item-gone';
                if ($period[1][2] == 1 && isset($period[2])
                  && ($period[2]['client'][0] !== null || $admin)
                  && $period[2]['main_client_id'] == null
                  && $admin
                ) {
                  $_action = $reservation_on ? 'removeOrder' : false;
                } elseif (self::$removeTicket
                  && $period[1][3] == 1
                  && $admin
                ) {
                  $_action = $reservation_on ? ($admin ? 'removeTicketProceed' : 'removeTicket') : false;
                } else {
                  if ($admin && $engine->checkAdminPossibilityOrderPastTime($mysql_date)) {
                    $gone_action = true;
                  }
                }
                // может ли админ изменять прошедшие бронирования
                if ((!empty($period[2]['reservation_id']) && defined('ADMIN_EDIT_REQUEST_GONE_TIME') && ADMIN_EDIT_REQUEST_GONE_TIME)) {
                  $admin_change_gone_reservation = true;
                }

                $_client = (!$admin && !$display)
                  ? (SHOW_PAST_RESERVATION ? (object)[
                    'visible' => (bool)$period[1][2],
                    'name'    => '',
                  ] : false)
                  : (object)[
                    'visible'        => $thisCurrentClientGame ? true : self::$viewClientName,
                    'id'             => isset($period[2]) && isset($period[2]['client']) ? $period[2]['client'][0] : null,
                    'main_client_id' => isset($period[2]) && isset($period[2]['main_client_id']) ? $period[2]['main_client_id'] : null,
                    'name'           => StringHelper::shield($name, doubleEncode: false),
                    'action'         => $_action,
                    'second_player'  => $second_player,
                  ];
                $state
                         = /*(isset($period[2]) && isset($period[2]['client']) && $period[2]['client'][0] == null)
                  ? false
                  : */
                  [
                    'light'   => (object)[
                      'visible' => (int)$period[1][4],
                    ],
                    'heating' => (object)[
                      'visible' => (int)$period[1][5],
                    ],
                    'net'     => (object)[
                      'visible' => (int)$period[1][6],
                    ],
                  ];
                break;
              case (int)$period[1][2]:
              case (int)$period[1][3]:// ordered = 1 или ticket = 1
                $class_touch = 'cell-item';
                if (isset($period[1][7]) && $period[1][7] == 0 && $period[1][2] == 1) {
                  $class = $admin
                    ? 'period_ordered'
                    : (!$isOpenType ?
                      ((isset($period[2]['client'][0]) && isset($client['client_id']) && $period[2]['client'][0] !== null && $client['client_id'] == $period[2]['client'][0])
                        ? 'own' : 'ordered')
                      : 'half');
                  $color = (!$isOpenType ?
                    ((isset($client['client_id']) && $period[2]['client'][0] !== null && $client['client_id'] == $period[2]['client'][0]) ? 'green'
                      : 'red')
                    : 'orange');
                  if ($isOpenType
                    && $touch
                    && defined('CONFIRMATION_OF_RESERVATIONS_VIA_TOUCH_FOR_OPEN')
                    && CONFIRMATION_OF_RESERVATIONS_VIA_TOUCH_FOR_OPEN
                    && $unix_time_date == strtotime(date('Y-m-d'))
                    && $period[2]['client']['client_id'] == $client['client_id']
                    && $period[2]['main_client_id'] == null
                  ) {
                    if ($engine->checkTimeConfirmationOfReservations($period_start, CONFIRMATION_OF_RESERVATIONS_VIA_TOUCH_FOR_OPEN)) {
                      $button_confirmation = (object)[
                        'title_button_confirmation' => lang('title_button_confirmation', 'button_confirmation'),
                        'title_button_remove'       => lang('title_button_remove', 'button_confirmation'),
                      ];
                    }
                  }
                } elseif (isset($period[2]) && isset($period[2]['client']) && ((isset($client['client_id']) && $period[2]['client'][0] !== null && $client['client_id'] == $period[2]['client'][0])
                    || (isset($period[1][7]) && $period[1][7] == 1 && $period[2]['main_client_id'] !== null && $client['client_id'] == $period[2]['main_client_id'])
                    || ($isOpenType && isset($period[1][7]) && $period[1][7] == 1 && $period[2]['main_client_id'] == null && $client['client_id'] !== null && $area_data[2][$period[0]][2]['client'][0] == $client['client_id'])
                    || $thisCurrentClientGame
                  )) {
                  $class = $admin ? 'period_ordered' : 'own';
                  $color = 'green';
                } else {
                  $class = $admin ? 'period_ordered' : 'ordered';
                  $color = 'red';
                }
                // проверка по доступности площадки по типу для данного клиента
                if ($admin || isset($client['area_type']) && ($client['area_type'] == $type_id || $client['area_type'] == 0)
                  && ($clientsInaccessibilityEngine && $clientsInaccessibilityEngine->checkClientAllowedPlayAreaForInaccessibility($client,
                      $mysql_date, $type_id,
                      $sport_id))) {
                  if ($period[1][2] == 1 && isset($period[2])
                    && ($period[2]['client'][0] !== null || $admin)
                    && $period[2]['main_client_id'] == null
                    && ((isset($client['client_id']) && $client['client_id'] == $period[2]['client'][0]) || $admin)
                  ) {
                    $_action = $reservation_on ? 'removeOrder' : false;
                  } elseif (self::$removeTicket
                    && $period[1][3] == 1 && isset($period[2])
                    && (($client['client_id'] == $period[2]['client'][0] && $client['abo_delete']) || $admin)
                  ) {
                    $_action = $reservation_on ? ($admin ? 'removeTicketProceed' : 'removeTicket') : false;
                  } elseif (isset($period[1][7]) && $period[1][7] == 0 && isset($period[2])
                    && isset($period[2]['main_client_id']) && $period[2]['main_client_id'] !== null
                    && ($client['client_id'] !== $period[2]['main_client_id'] || $admin)
                  ) {
                    $_action = config('DoubleGame')->doubleFriendsEnabled((int)$type_id, (int)$sport_id, (int)$area_id)
                      ? null : 'joinForm';//???
                  } elseif (isset($period[1][7]) && $period[1][7] == 1 && isset($period[2])
                    && $period[2]['main_client_id'] !== null
                    && (($client['client_id'] !== $period[2]['main_client_id']
                        && $client['client_id'] == $period[2]['client']['0'])
                      || $admin)
                  ) {
                    $_action = config('DoubleGame')->doubleFriendsEnabled((int)$type_id, (int)$sport_id, (int)$area_id)
                      ? null : 'unJoin';//???
                  }
                }

                $_client = (object)[
                  //'visible'        => $thisCurrentClientGame ? true : ($thisCurrentClientGame && $touch && $isOpenType ? true : self::$viewClientName),
                  'visible'        => $thisCurrentClientGame || $flg_show_names,
                  'id'             => isset($period[2]) ? $period[2]['client'][0] : null,
                  'main_client_id' => isset($period[2]) && isset($period[2]['main_client_id']) ? $period[2]['main_client_id'] : null,
                  'name'           => ($touch && $isOpenType && SHOW_LOGIN_INSTEAD_NAME_ON_STREET
                      ? ($period[2]['client'][0] ? $clients_login[$period[2]['client'][0]]->login : lang('Guest_player'))
                      : StringHelper::shield($name, doubleEncode: false))
                    . (defined('VIEW_COMMENT_SCHEDULE') && VIEW_COMMENT_SCHEDULE ? '<br>' . $memo : ''),
                  'action'         => $_action,
                  'second_player'  => $second_player,
                ];
                $state   = [
                  'light'   => (object)[
                    'visible' => (int)$period[1][4],
                  ],
                  'heating' => (object)[
                    'visible' => (int)$period[1][5],
                  ],
                  'net'     => (object)[
                    'visible' => (int)$period[1][6],
                  ],
                ];
                break;
              default:
                $class       = $admin ? 'period_avaliable' : ($reservation_on ? 'available' : 'unAvailable');
                $class_touch = $reservation_on ? 'cell-item' : 'cell-item-gone';
                if (!empty($client) && !$admin
                  && (
                    ($client['area_type'] != $type_id && $client['area_type'] != 0)
                    || ($clientsInaccessibilityEngine && !$clientsInaccessibilityEngine->checkClientAllowedPlayAreaForInaccessibility($client,
                        $mysql_date, $type_id,
                        $sport_id)))
                ) {
                  $class = $admin ? 'period_unAvaliable' : 'unAvailable';
                }
                $_client = false;
                $state   = [
                  'light'   => (object)[
                    'visible' => (int)$period[1][4],
                  ],
                  'heating' => (object)[
                    'visible' => (int)$period[1][5],
                  ],
                  'net'     => (object)[
                    'visible' => (int)$period[1][6],
                  ],
                ];
            }
            $customer      = isset($period[2]) && isset($period[2]['customer']) && $period[2]['customer'] != ''
              ? htmlspecialchars(
                $period[2]['customer'],
                ENT_QUOTES
              ) : false;
            $customer_data = [];

            if ($admin && $customer) {
              $user_obj = new UsersEngine();
              $user_obj->getUser($customer, $customer_data);
              if (empty($customer_data) && !empty($period[2]['customer_title'])) {
                $customer_data['code'] = $period[2]['customer_title'][0] ?? '';
                $customer_data['name'] = $period[2]['customer_title'][1] ?? '';
              }
            }

            $periods[] = (object)[
              'link_reserv'                   => $flg_area_link && $flg_rule_count,
              'street_friends'                => $street_friends,
              'start'                         => $period_start,
              'finish'                        => $period[0],
              'class'                         => ($flg_area_link && $flg_rule_count || $admin) ? $class : 'unAvailable',
              'class_touch'                   => ($flg_area_link && $flg_rule_count || $admin) ? ($class_touch) : 'cell-item-gone',
              'tmp_info'                      => $period['tmp'] ? lang('Reserved block order', 'message_error') : null,
              'color'                         => $color,
              'gone_action'                   => $gone_action,
              'show_order_action'             => $showOrder,
              'reservation_id'                => isset($period[2]) && isset($period[2]['reservation_id']) ? $period[2]['reservation_id'] : null,
              'main_reservation_id'           => isset($period[2]) && isset($period[2]['main_reservation_id']) ? $period[2]['main_reservation_id']
                : null,
              'type_reservation'              => isset($period[2]) && isset($period[2]['type_reservation']) ? $period[2]['type_reservation'] : null,
              'order_status'                  => (isset($period[2]['payment_state']) ? (int)$period[2]['payment_state'] : null),
              'ticket'                        => ($period[1][3] == 1 && isset($period[2]) && $period[2]['ticket_id'] !== null)
                ? $period[2]['ticket_id']
                : false,
              'client'                        => $_client,
              'state'                         => $state,
              'memo'                          => $memo ?: false,
              'customer'                      => $customer,
              'customer_data'                 => $customer_data,
              'price'                         => $periodPrice,
              'sale'                          => $sale,
              'stock'                         => ($period[1][2] == 1 || $period[1][3] == 1) && isset($period[2]) && isset($period[2]['stock']) && $period[2]['stock'] !== null
                ? htmlspecialchars(
                  $period[2]['stock'],
                  ENT_QUOTES
                ) : false,
              'price_a'                       => isset($period[2]) && isset($period[2]['price']) ? $period[2]['price'] : null,
              'encash'                        => isset($period[2]) && isset($period[2]['encash']) ? $period[2]['encash'] : null,
              'encash_title'                  => isset($period[2]) && isset($period[2]['encash']) ? $engine->getTitleEncash($period[2]['encash'])
                : null,
              'button_confirmation'           => $button_confirmation,
              'admin_change_gone_reservation' => $admin_change_gone_reservation,
            ];
          }
        }


        //-- START -- Заполнение недостающих ячеек в расписании, при площадках с разным расписанием --
        $new_periods = [];
        if (isset($area_data[2])) {
          $periods_count      = count($periods);
          $max_period_periods = $area_with_max_periods_periods_array;

          for ($i = 0; $i < $periods_count; $i++) {
            $area_period_unixtime = strtotime($periods[$i]->start);

            foreach ($max_period_periods as $period_key => $period_data) {
              $max_period_unixtime = strtotime($period_key);
              if ($area_period_unixtime > $max_period_unixtime) {
                $new_periods[] = (object)[
                  'start'               => $period_key,
                  'finish'              => $period_data[0],
                  'class'               => "unAvailable",
                  'class_touch'         => "cell-item-gone",
                  'color'               => "gray",
                  'gone_action'         => false,
                  'encash'              => null,
                  'reservation_id'      => null,
                  'main_reservation_id' => null,
                  'order_status'        => null,
                  'ticket'              => false,
                  'client'              => false,
                  'state'               => [],
                  'memo'                => false,
                  'customer'            => false,
                  'price'               => [],
                  'sale'                => false,
                  'stock'               => false,
                ];
                unset($max_period_periods[$period_key]);
              } elseif ($area_period_unixtime == $max_period_unixtime) {
                unset($max_period_periods[$period_key]);
              }
            }
            $new_periods[] = $periods[$i];

            if (($i == ($periods_count - 1)) && (count($max_period_periods) > 0)) {
              foreach ($max_period_periods as $period_key => $period_data) {
                $new_periods[] = (object)[
                  'start'               => $period_key,
                  'finish'              => $period_data[0],
                  'class'               => "unAvailable",
                  'class_touch'         => "cell-item-gone",
                  'color'               => "gray",
                  'gone_action'         => false,
                  'encash'              => null,
                  'reservation_id'      => null,
                  'main_reservation_id' => null,
                  'order_status'        => null,
                  'ticket'              => false,
                  'client'              => false,
                  'state'               => [],
                  'memo'                => false,
                  'customer'            => false,
                  'price'               => [],
                  'sale'                => false,
                  'stock'               => false,
                ];
              }
            }
          }
        }
        //-- END --
        $area['periods'] = $new_periods;
        $areas[]         = (object)$area;
      }

      return $areas;
    } else {
      return false;
    }
  }

  /** Генератор площадок по дням недели для закрытых кортов
   *
   * @param Engines      $engine
   * @param              $type_id
   * @param              $limit
   * @param              $mysql_date
   * @param              $sport_id
   * @param              $touch
   *
   * @return BaseObject|bool
   */
  protected static function getAreasWeek($engine, $type_id, $limit, $mysql_date, $sport_id, $touch = false, $areaPage = null)
  {
    $engine->setMaxReservationUnixTime($type_id, $sport_id);

    $device_type = 'pc';
    if ($touch) {
      $device_type = 'touch';
    }

    if ($areaPage === null) {
      $engine->rules->correctColumnLimitForRule(
        $engine,
        $sport_id,
        $type_id,
        $device_type,
        (!empty($engine->clients->current_client_data)) ? true : false,
        $limit
      );
    }

    if ($engine->getPeriodsByAreasTypeDateWeek(
        $type_id,
        $limit,
        $mysql_date,
        $areas_data,
        $weekdays,
        $error_code,
        $sport_id,
        $areaPage
      ) == true) {
      $client     = $engine->clients->current_client_data;
      $isOpenType = config('reservations')->isOpenType((int)$type_id);
      $_periods   = [];
      foreach ($areas_data as $start => $periods) {
        $_period = [];
        if (is_array($periods[1])) {
          foreach (array_keys($weekdays) as $weekday) {
            $period = $periods[1][$weekday] ?? null;
            $_areas = [];
            if (is_array($period)) {
              foreach ($period as $area_id => $area_data) {
                $rule                   = $engine->rules->rulePriority(
                  $area_id,
                  $type_id,
                  $sport_id,
                  $engine->clients->current_client_data['club_state'] ?? 1,
                  $device_type
                );
                $weekdayDate            = $area_data['weekday_date'] ?? null;
                $weekdayDateUnix        = $weekdayDate ? strtotime($weekdayDate) : false;
                $isWeekdayDateAvailable = $weekdayDateUnix ? $engine->checkDateAvaliableByUnixtime($weekdayDateUnix) : false;

                $interval       = ($weekdayDateUnix - strtotime(date('Y-m-d'))) / 24 / 3600;
                $flg_rule_count = (!empty($rule) && isset($rule['max_forward_reservation_days_count']))
                  ? ($interval < $rule['max_forward_reservation_days_count']) : true;
                $flg_area_link  = (!empty($rule) && isset($rule['show_on'])) ? (strtolower($rule['show_on']) == $device_type) : true;

                $flg_time_rule = $engine->rules->checkTimeRule(
                  $rule,
                  $start,
                  $weekdayDate,
                  $error_message
                );

                $reservation_on  = true;
                $flg_show_column = $engine->rules->getVisibleColumnByRule($rule, (!empty($client)) ? true : false, $device_type);
                if (!$flg_show_column) {
                  continue;
                }
                if ($isOpenType) {
                  switch (OPEN_RESERVATION_ON_DEVICE) {
                    case 'touch' :
                      $reservation_on = $touch ? true : $area_data['online_reservation'] == 1;
                      break;
                    default :
                      break;
                  }
                }

                $flg_show_day   = $engine->checkDateAvaliableByUnixtime(strtotime($area_data['weekday_date']), false);
                $reservation_on = $reservation_on && $flg_show_day;
                $_action        = false;
                switch (1) {
                  case (int)$engine->holidays->checkHoliday($area_data['weekday_date']): // holiday
                    $class   = 'holiday';
                    $_client = false;
                    break;
                  case !$isWeekdayDateAvailable: // дата заблокирована календарём (выходит за допустимый диапазон бронирования)
                    $class   = 'unAvailable';
                    $_client = false;
                    break;
                  case (int)$area_data[1][0] || !$flg_time_rule: // gone
                    $class   = 'unAvailable';
                    $_client = false;
                    break;
                  case (int)$area_data[1][1]: // blocked
                    $class   = 'blocked';
                    $_client = false;
                    break;
                  case (int)$area_data[1][2] || (int)$area_data[1][3]: // order or ticked
                    if (isset($area_data[3]['status']) && $area_data[3]['status'] == 0 && $area_data[1][2] == 1) {
                      $class = (!$isOpenType ?
                        ($area_data[3]['client'][0] !== null && $client['client_id'] == $area_data[3]['client'][0] ? 'own' : 'ordered')
                        : 'half');
                    } elseif ($client['client_id'] !== null && ($area_data[3]['client'][0] !== null && $client['client_id'] == $area_data[3]['client'][0]
                        || ($area_data[3]['status'] == 1 && $area_data[3]['main_client_id'] !== null && $client['client_id'] == $area_data[3]['main_client_id'])
                        || ($isOpenType && $area_data[3]['status'] == 1 && $area_data[3]['main_client_id'] == null && $areas_data[$periods[0]][1][$weekday][$area_id][3]['client'][0] == $client['client_id'])
                      )) {
                      $class = 'own';
                    } else {
                      $class = 'ordered';
                    }
                    /**
                     * todo доработать возможность делать присоеденения и
                     */
//                    if ($period[1][2] == 1 && $period[2]['client'][0] !== null && $client['client_id'] == $period[2]['client'][0] && $period[2]['main_client_id'] == null) {
//                      $_action = 'removeOrder';
//                    } elseif (self::$removeTicket && $period[1][3] == 1 && $client['client_id'] == $period[2]['client'][0]) {
//                      $_action = 'removeTicket';
//                    } elseif ($period[1][7] == 0 && $period[2]['main_client_id'] !== null && $client['client_id'] !== $period[2]['main_client_id']) {
//                      $_action = 'joinForm';
//                    } elseif ($period[1][7] == 1 && $period[2]['main_client_id'] !== null && $client['client_id'] !== $period[2]['main_client_id'] && $client['client_id'] == $period[2]['client']['0']) {
//                      $_action = 'unJoin';
//                      $class   = 'own';
//                    }

                    $_client = (object)[
                      'visible' => false,
                      'id'      => isset($period[2]['client']) ? $period[2]['client'][0] : null,
                      'name'    => isset($period[2]['client']) ? StringHelper::shield(
                        $period[2]['client'][1] . ' ' . $period[2]['client'][2],
                        doubleEncode: false
                      ) : '',
                      'action'  => $_action,
                    ];
                    break;
                  default :
                    $class   = $reservation_on ? '' : 'unAvailable';
                    $_client = false;
                }

                // Если дата недоступна по календарю — запрещаем ссылку бронирования.
                if (!$isWeekdayDateAvailable) {
                  $flg_area_link  = false;
                  $flg_rule_count = false;
                }
                $_areas[] = (object)[
                  'link_reserv'  => $flg_area_link && $flg_rule_count,
                  'weekday_date' => $area_data['weekday_date'],
                  'start'        => $start,
                  'id'           => $area_id,
                  'title'        => $area_data[0],
                  'class'        => ($flg_area_link && $flg_rule_count && (self::checkLimitDayReservation($area_data['weekday_date'],
                        self::$limitDaysReservation) || $class != '')) ? $class : 'unAvailable',
                  'client'       => $_client,
                ];
              }
            }
            $_period[] = (object)[
              'weekday' => $weekday,
              'areas'   => $_areas,
            ];
          }
//          }
        }
        $_periods[] = (object)[
          'start'  => $start,
          'finish' => $periods[0],
          'period' => $_period,
        ];
      }

      $areas = (object)[
        'weekdays' => $weekdays,
        'periods'  => $_periods,
      ];

      return $areas;
    } else {
      return false;
    }
  }

  /** получить массив видов рассписаний
   *
   * @return array
   */
  protected static function getWeeksTabs()
  {
    $_weeks = [
      '0' => (object)[
        'title' => (defined('DEFAULT_TAGESANSICHT_WOCHENANSICHT')
          && (DEFAULT_TAGESANSICHT_WOCHENANSICHT == 'WOCHENANSICHT')) ? lang('Weekly_view') : lang('Day_view'),
        'type'  => (defined('DEFAULT_TAGESANSICHT_WOCHENANSICHT')
          && (DEFAULT_TAGESANSICHT_WOCHENANSICHT == 'WOCHENANSICHT')) ? '1' : '0',
      ],
      '1' => (object)[
        'title' => (defined('DEFAULT_TAGESANSICHT_WOCHENANSICHT')
          && (DEFAULT_TAGESANSICHT_WOCHENANSICHT == 'WOCHENANSICHT')) ? lang('Day_view') : lang('Weekly_view'),
        'type'  => (defined('DEFAULT_TAGESANSICHT_WOCHENANSICHT')
          && (DEFAULT_TAGESANSICHT_WOCHENANSICHT == 'WOCHENANSICHT')) ? '0' : '1',
      ],
    ];

    return $_weeks;
  }

  /** Получить массив страниц для спорта и типа полощадки
   *
   * @param $engine Engines
   *
   * @param $type_id
   * @param $sport_id
   * @param $touch
   *
   * @return array
   */

  protected static function getPagesTabs($engine, $type_id, $sport_id, $touch = false, $defaultTitle = null)
  {
    if (USE_PAGE_BREAK_FROM_BASE_AREA) {
      $_pages = self::renderPagesTabFromBaseStructure($engine, $type_id, $sport_id, $touch);
    } else {
      $_pages = self::renderPagesTabFromDefaultStructure($engine, $type_id, $sport_id, $touch, $defaultTitle);
    }

    return $_pages;
  }

  /** Получить массив страниц для спорта и типа полощадки по дефолту
   *
   * @param $engine Engines
   *
   * @param $type_id
   * @param $sport_id
   * @param $touch
   *
   * @return array
   */
  protected static function renderPagesTabFromDefaultStructure($engine, $type_id, $sport_id, $touch = false, $defaultTitle = null)
  {
    $isOpenType = config('reservations')->isOpenType((int)$type_id);
    $perPage    = $touch ? ($isOpenType ? OPEN_STREET_PER_PAGE : TOUCH_PER_PAGE) : PER_PAGE; // количество столбцов

    $device_type = $touch ? 'touch' : 'pc';

    $_pages = [];
    $engine->areas->getAreasTitlesByType($type_id, $areas, $sport_id);
    $count   = 1;
    $i       = 1;
    $title   = [];
    $area_id = 1;
    preg_match('/([a-zA-Z-_]*)/', $areas[0]['title'], $area_title);
    $cnt_max = 0;
    foreach ($areas as $area) {
      $rule            = $engine->rules->rulePriority(
        $area['area_id'],
        $area['type_id'],
        $area['sport_id'],
        isset($engine->clients->current_client_data['club_state']) ? $engine->clients->current_client_data['club_state'] : 1,
        $device_type
      );
      $flg_show_column = $engine->rules->getVisibleColumnByRule($rule, !empty($engine->clients->current_client_data), $device_type);
      if (!$flg_show_column) {
        continue;
      }
      ++$cnt_max;
      if ($i == 1) {
        $area_id = $area['area_id'];
      }
      if ($i > $count * $perPage) {
        $name_title   = ($defaultTitle ?: $area_title[0]) . ' ';
        $number_title = ($title[0] == $title[count($title) - 1]) ? $title[0] : ($title[0] . ' - ' . $title[count($title) - 1]);

        $_pages[] = (object)[
          'name_title'   => $name_title,
          'number_title' => $number_title,
          'page'         => $count,
          'area_id'      => $area_id,
        ];
        $count++;
        $title = [];
      }
      if ($i % $perPage == 1) {
        $area_id = $area['area_id'];
      }
      $title[] = $area['short_title'];
      $i++;
    }
    $name_title   = ($defaultTitle ?: $area_title[0]) . ' ';
    $number_title = ($title[0] == $title[count($title) - 1]) ? $title[0] : ($title[0] . ' - ' . $title[count($title) - 1]);
    if ($cnt_max % $perPage == 1) {
      $name_title   = $area['title'];
      $number_title = "";
    }
    $_pages[] = (object)[
      'name_title'   => $name_title,
      'number_title' => $number_title,
      'page'         => $count,
      'area_id'      => $area_id,
    ];

    return $_pages;
  }

  /** Получить массив страниц для спорта и типа полощадки по страницам из базы
   *
   * @param $engine Engines
   *
   * @param $type_id
   * @param $sport_id
   * @param $touch
   *
   * @return array
   */
  protected static function renderPagesTabFromBaseStructure($engine, $type_id, $sport_id, $touch = false, $defaultTitle = null)
  {
    $device_type = 'pc';
    if ($touch) {
      $device_type = 'touch';
    }

    $_pages     = [];
    $_areas     = $engine->areas->getAreasPages();
    $pages_data = $_areas[$type_id . '_' . $sport_id];
    foreach ($pages_data as $page => $areas) {
      $flg_show_column = count($areas);
      foreach ($areas as $key => $area) {
        $rule = $engine->rules->rulePriority(
          $area['area_id'],
          $area['type_id'],
          $area['sport_id'],
          $engine->clients->current_client_data['club_state'],
          $device_type
        );
        if (!$engine->rules->getVisibleColumnByRule($rule, !empty($engine->clients->current_client_data), $device_type)) {
          $flg_show_column--;
          unset($areas[$key]);
        }
      }
      if (!$flg_show_column) {
        continue;
      }
      preg_match('/([a-zA-Z-_\"]*)/', $areas[0]['title'], $area_title);
      $_pages[] = (object)[
        'name_title'   => ($defaultTitle ?: str_replace('"', '&quot;', rtrim($area_title[0], '-'))) . ' ',
        'number_title' => self::getAreasPageNumberTitle($areas),
        'page'         => $page,
        'area_id'      => $areas[0]['area_id'],
        'count_areas'  => count($areas),
      ];
    }

    return $_pages;
  }

  /**
   * Возвращает номера площадок для заголовка вкладки.
   * Последовательные числовые значения сокращаются до диапазона,
   * остальные выводятся полным списком.
   */
  protected static function getAreasPageNumberTitle(array $areas): string
  {
    $titles = array_values(array_column($areas, 'short_title'));
    if (count($titles) < 2) {
      return (string)($titles[0] ?? '');
    }

    $isSequential = true;
    foreach ($titles as $index => $title) {
      if ($index === 0) {
        continue;
      }
      if (!ctype_digit((string)$titles[$index - 1])
        || !ctype_digit((string)$title)
        || (int)$title !== (int)$titles[$index - 1] + 1
      ) {
        $isSequential = false;
        break;
      }
    }

    return $isSequential
      ? $titles[0] . ' - ' . $titles[count($titles) - 1]
      : implode(', ', $titles);
  }

  /** Получить массив значений для прорисовки видов спорта
   *
   * @param $engine Engines
   *
   * @param $type_id
   * @param $sport_id
   *
   * @return array
   */

  protected static function getSportsTabs($engine, $type_id, $sport_id)
  {
    $sports = [];
    foreach ($engine->sports->getSportsTitlesByType($type_id) as $sport) {
      $sports[] = (object)[
        'active' => ($sport->sport_id == $sport_id),
        'title'  => $sport->title,
        'class'  => mb_strtolower($sport->alias),
        'id'     => $sport->sport_id,
      ];
    }

    return $sports;
  }

  protected static function getAreaWorkdays(Engines $engine, $type_id, $sport_id): ?array
  {
    if (!$engine->areas->getAreasDataByType($type_id, $areas, $sport_id)) {
      return null;
    }

    $workdays = [];
    foreach ($areas as $area) {
      foreach (explode(',', (string)$area['workdays']) as $weekday) {
        if ($weekday !== '') {
          $workdays[(int)$weekday] = true;
        }
      }
    }

    return $workdays;
  }

  protected static function isAreaWorkday(?array $areaWorkdays, string $date): bool
  {
    if ($areaWorkdays === null) {
      return true;
    }

    return isset($areaWorkdays[CalendarHelper::getWeekdayByUnixtime(strtotime($date))]);
  }

  /** Вернуть тип корта по его id
   *
   * @param $type_id
   *
   * @param $engine Engines
   *
   * @return bool|string
   */
  protected static function getTypeCourtByTypeId($type_id, $engine)
  {
    $engine->areas->getTypeData($type_id, $type);

    return $type['alias'];
  }

  public static function setView($params = [], $new = false)
  {
    if ($new || empty(self::$view)) {
      self::$view = new View($params);
    }
  }
}
