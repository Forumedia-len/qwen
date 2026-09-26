<?php

use AC\core\system\db\Query;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\TranslateHelper;

$MESS = useFile('lang/de.php');

$out         = '';
$start_year  = Service::request()->_('start_year');
$start_month = Service::request()->_('start_month');
$start_day   = Service::request()->_('start_day');
$end_year    = Service::request()->_('end_year');
$end_month   = Service::request()->_('end_month');
$end_day     = Service::request()->_('end_day');
if ($type = Service::request()->_('type')) {
  [$type_id, $sport_id] = explode('_', $type);
}

if ($start_year !== null && $start_month !== null && $start_day !== null &&
  $end_year !== null && $end_month !== null && $end_day !== null) {
  $mysql_start_date = "$start_year-$start_month-$start_day";
  $start_unix_time  = strtotime("$start_year-$start_month-$start_day ");
  $mysql_end_date   = "$end_year-$end_month-$end_day";
  $end_unix_time    = strtotime("$end_year-$end_month-$end_day ");

  //в этих массивах будут хранится результаты запросов к БД
  $reserved          = []; //обычные резервирования
  $tickets           = []; //абонементы
  $holidays          = []; //праздники
  $blocks            = []; //простые блокировки
  $blocks_periodical = []; //периодические блокировки

  if (checkdate($start_month, $start_day, $start_year) && checkdate(
      $end_month,
      $end_day,
      $end_year
    ) && $end_unix_time >= $start_unix_time) {
    //обычные резервирования
    $q    = 'SELECT area_id, period FROM ' . Query::tableName('areas') . ' order by sort, area_id';
    $temp = Query::sqlQuery($q);
    foreach ($temp as $ar) {
      $area_period[$ar['area_id']] = $ar['period'];
    }

    //обычные резервирования
    $q = 'select
        if(r.client_id is null,md5(concat(r.client_name,r.client_surname)),c.client_id) as client_id,
        if(r.client_id is null,r.client_name,c.name) as name,
        if(r.client_id is null,r.client_surname,c.surname)  as surname,
        at.type_id,
        at.title as type,
        asp.title as sport,
        a.area_id,
        a.title as area,
        r.start, substring(r.finish,12,5) as finish,
        r.price,
        if(c.mode is null,2,c.mode - 1) as mode
      from ' . Query::tableName('reservations') . ' r
      left join ' . Query::tableName('areas') . ' a on a.area_id = r.area_id
      left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id
      left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id
      left join ' . Query::tableName('clients') . ' c on r.client_id = c.client_id
      where "' . $mysql_start_date . '" <= substring(r.start,1,10) and "' . $mysql_end_date . '" >= substring(r.start,1,10)' .
      ((isset($type_id) && $type_id != 0) ? ' and at.type_id = ' . $type_id : '') .
      (isset($sport_id) && $sport_id != 0 ? ' and asp.sport_id = ' . $sport_id : '');
    foreach (Query::sqlQuery($q) as $r) {
      $reserved[$r['type']][$r['sport']][$r['area']][substr($r['start'], 0, 10)][substr($r['start'], 11)] = true;
    }
    //билеты
    $q = 'select a.title as area, a.area_id,
        tp.start as period_start, tp.finish as period_finish,
        if(t.period_start is null, (select min(start) from ' . Query::tableName('tickets_periods') . ' where ticket_id=t.ticket_id), t.period_start) as min_start_period,
        t.time_start as time_start, t.time_finish as time_finish, t.weekdays, at.title as type, asp.title as sport,
        if(t.week_begin is null,week((select min(start) from ' . Query::tableName('tickets_periods') . ' where ticket_id=t.ticket_id), 3),t.week_begin) as week_begin,
         t.space, t.ticket_id, tp.period_id, a.period as time_period
      from ' . Query::tableName('tickets_periods') . ' tp
      left join ' . Query::tableName('tickets') . ' t on t.ticket_id = tp.ticket_id
      left join ' . Query::tableName('areas') . ' a on a.area_id = t.area_id
      left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id
      left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id
      left join ' . Query::tableName('clients') . ' c on t.client_id = c.client_id
      where ("' . $mysql_start_date . '" <= substring(tp.finish,1,10) OR "' . $mysql_end_date . '">=substring(tp.start,1,10) OR
      ("' . $mysql_start_date . '" <= substring(tp.start,1,10) AND "' . $mysql_end_date . '" >= substring(tp.finish,1,10)))'
      . Service::engines()->areas->sqlCheckGroupType('at')
      . ((isset($type_id) && $type_id != 0) ? ' and at.type_id = ' . $type_id : '')
      . (isset($sport_id) && $sport_id != 0 ? ' and asp.sport_id = ' . $sport_id : '');

    foreach (Query::sqlQuery($q) as $r) { //проход по абонементам
      $abo_start_unix   = strtotime($r['period_start']);
      $abo_finish_unix  = strtotime($r['period_finish']);
      $weekdays         = explode(',', $r['weekdays']);
      $current_day_unix = $abo_start_unix;
      $week             = $r['week_begin'] !== null ? $r['week_begin'] : date('W', strtotime($r['period_start']));
      $first_day        = CalendarHelper::getFirstDayTicket($r['min_start_period'], $weekdays);
      while ($current_day_unix <= $abo_finish_unix) {//проход по датам абонемента
        $current_week = date('W', $current_day_unix);
        $weekday      = date('N', $current_day_unix) - 1;
        if (in_array($weekday, $weekdays) && CalendarHelper::checkDayForTicket($current_day_unix, $first_day[$weekday],
            $r['space'])) { //если совпадает с днями недели абонемента
          $start_time  = strtotime($r['time_start']);
          $finish_time = strtotime($r['time_finish']);
          for ($time = $start_time; $time < $finish_time; $time += 60 * $r['time_period']) {//проход по времени абонемента
            $des_times = Service::engines()->tickets->getDisabledTime($mysql_start_date,
              $mysql_end_date, date('Y-m-d', $current_day_unix), $r['ticket_id']);
            if (!in_array(date('H:i', $time), $des_times)) {
              $tickets[$r['type']][$r['sport']][$r['area']][date('Y-m-d', $current_day_unix)][date('H:i:s', $time)] = true;
            }
          }
        }
        $current_day_unix = mktime(0, 0, 0, date('m', $current_day_unix), date('d', $current_day_unix) + 1, date('Y', $current_day_unix));
      }
    }

    //праздники
    $q = 'select h.date from ' . Query::tableName('holidays') . ' h
				where "' . $mysql_start_date . '" <= h.date AND "' . $mysql_end_date . '" >= h.date';
    foreach (Query::sqlQuery($q) as $r) { //проход по выходным
      $holidays[$r['date']] = true;
    }

    //блокировки обычные
    $q    = 'select
        at.title as type,
        asp.title as sport,
        a.title as area,
        b.start,
        b.finish
      from ' . Query::tableName('blocks') . ' b
      left join ' . Query::tableName('areas') . ' a on a.area_id = b.area_id
      left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id
      left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id
      where ("' . $mysql_start_date . '" <= substring(b.finish,1,10) OR "' . $mysql_end_date . '">=substring(b.start,1,10) OR
        ("' . $mysql_start_date . '" <= substring(b.start,1,10) AND "' . $mysql_end_date . '" >= substring(b.finish,1,10)))
        ' . ((isset($type_id) && $type_id != 0) ? ' and at.type_id = ' . $type_id : '') .
      (isset($sport_id) && $sport_id != 0 ? ' and asp.sport_id = ' . $sport_id : '');
    $temp = Query::sqlQuery($q);
    foreach ($temp as $r) { //проход по выходным
      $blocks[$r['type']][$r['sport']][$r['area']][] = [strtotime($r['start']), strtotime($r['finish'])];
    }

    //блокировки периодические
    $q = 'select
        at.title as type,
        asp.title as sport,
        a.title as area,
        b.date_start,
        b.date_finish,
        b.time_start,
        b.time_finish,
        b.weekday_0,b.weekday_1,b.weekday_2,b.weekday_3,b.weekday_4,b.weekday_5,b.weekday_6
      from ' . Query::tableName('blocks_periodical') . ' b
      left join ' . Query::tableName('areas') . ' a on a.area_id = b.area_id
      left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id
      left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id
      where ("' . $mysql_start_date . '" <= substring(b.date_finish,1,10) OR "' . $mysql_end_date . '">=substring(b.date_start,1,10) OR
        ("' . $mysql_start_date . '" <= substring(b.date_start,1,10) AND "' . $mysql_end_date . '" >= substring(b.date_finish,1,10)))
        ' . ((isset($type_id) && $type_id != 0) ? ' and at.type_id = ' . $type_id : '') .
      (isset($sport_id) && $sport_id != 0 ? ' and asp.sport_id = ' . $sport_id : '');

    $temp = Query::sqlQuery($q);
    foreach ($temp as $r) { //проход по выходным
      $weekdays = [];
      if ($r['weekday_0'] == 1) {
        $weekdays[] = 0;
      }
      if ($r['weekday_1'] == 1) {
        $weekdays[] = 1;
      }
      if ($r['weekday_2'] == 1) {
        $weekdays[] = 2;
      }
      if ($r['weekday_3'] == 1) {
        $weekdays[] = 3;
      }
      if ($r['weekday_4'] == 1) {
        $weekdays[] = 4;
      }
      if ($r['weekday_5'] == 1) {
        $weekdays[] = 5;
      }
      if ($r['weekday_6'] == 1) {
        $weekdays[] = 6;
      }

      $blocks_periodical[$r['type']][$r['sport']][$r['area']][] = [
        strtotime($r['date_start']),
        strtotime($r['date_finish']),
        strtotime($r['time_start']),
        strtotime($r['time_finish']),
        $weekdays,
      ];
    }

    //периоды времени работы площадок
    $periods = [];
    $q       = 'select
      atypes.title as type,
      asp.title as sport,
      a.title,
      a.area_id,
      atimes.weekday,
      atimes.start,
      atimes.finish
      from ' . Query::tableName('areas_timetables') . ' atimes
      left join ' . Query::tableName('areas') . ' a on atimes.area_id=a.area_id
      left join ' . Query::tableName('areas_types') . ' atypes on a.type_id=atypes.type_id
      left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id
      where a.area_id is not null '
      . Service::engines()->areas->sqlCheckGroupType('atypes')
      . ((isset($type_id) && $type_id != 0) ? ' and atypes.type_id = ' . $type_id : '') .
      (isset($sport_id) && $sport_id != 0 ? ' and asp.sport_id = ' . $sport_id : '');

    $temp = Query::sqlQuery($q);
    foreach ($temp as $r) {
      $periods[$r['type']][$r['sport']][$r['title']][$r['weekday']] = [$r['start'], $r['finish'], $r['area_id']];
    }

    ///////////////////////////////////////////////////
    //заголовок с датами и днями недели и месецами для HTML таблиц
    $table_month          = '<tr><td rowspan="3">&nbsp;</td>';
    $table_header_date    = '<tr>';
    $table_header_weekday = '<tr>';
    $current_day_unix     = $start_unix_time;
    $j                    = 1;
    while ($current_day_unix <= $end_unix_time) {
      $table_header_date    .= '<th>' . date('d', $current_day_unix) . '</th>';
      $weekday              = (date('w', $current_day_unix) ? date('w', $current_day_unix) - 1 : 6);
      $table_header_weekday .= '<th>' . TranslateHelper::translateWeekday($weekday, true) . '</th>';
      $old                  = $current_day_unix;
      $current_day_unix     = mktime(0, 0, 0, date('m', $current_day_unix), date('d', $current_day_unix) + 1, date('Y', $current_day_unix));
      if (date('m', $current_day_unix) == date('m', $old)) {
        $j++;
      } else {
        $table_month .= '<th colspan="' . $j . '"><span style="display: block;border-right: 1px solid #fff">' . lang(
            'month_' . date('n', $old)
          ) . '</span></th>';
        $j           = 1;
      }
    }
    if (date('t', $end_unix_time) !== date('d', $end_unix_time)) {
      $table_month .= '<th colspan="' . $j . '"><span style="display: block;border-right: 1px solid #fff">' . lang(
          'month_' . date('n', $old)
        ) . '</span></th>';
    }
    $table_header = $table_month . '</tr>' . $table_header_date . '</tr>' . $table_header_weekday . '</tr>';

    ///////////////////////////////////////////////////
    //формируем таблицы
    foreach ($periods as $type => $sports) { //проход по типам площадок
      foreach ($sports as $sport => $areas) { //проход по типам спорта
        foreach ($areas as $area => $weekdays) {                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            //проход по площадкам
          //заголовок таблицы
          $out .= '<h3>' . (count($periods) > 1 ? $type . ' - ' : '') . $sport . ' - ' . $area . ' - ' . $start_day . '/' . $start_month . '/' . $start_year . ' - ' . $end_day . '/' . $end_month . '/' . $end_year . '</h3>' . "\n" . '<table cellspacing="0" cellpadding="3" class="orders_table">' . $table_header;
          //определяем часы работы площадки во все дни
          $min_start  = 9999999999999999999999999;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          //самое раннее, когда площадка начинет работать
          $max_finish = 0;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  //самое позднее, когда площадка заканчивает работать
          foreach ($weekdays as $worktime) {
            [$start, $finish, $area_id] = $worktime;
            $tmp = strtotime($start);
            if ($tmp < $min_start) {
              $min_start = $tmp;
            }
            //баг с 00:00 часов
            $finish = ($finish == '24:00:00' ? '23:59:59' : $finish);
            $tmp    = strtotime($finish);
            if ($tmp > $max_finish) {
              $max_finish = $tmp;
            }
          }
          //рисуем таблицу
          $time = $min_start;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               //итерация $time происходит вконце цикла
          while ($time < $max_finish) { //проход по периодам работы площадки
            //начало строки и левый заголовок таблицы
            $out              .= "\t" . '<tr><th><div style="min-width: 80px">' . date('H:i', $time) . ' - ' . date('H:i',
                $time + ($area_period[$area_id] * 60)) . '</div></th>';
            $current_day_unix = $start_unix_time; //итерация $i происходит вконце цикла
            while ($current_day_unix <= $end_unix_time) { //проход по дням отчета
              $class = '';
              //проверяем работает ли в этот день площадка
              if (isset($weekdays[date('w', $current_day_unix)])) {
                $current_time = mktime(date('H', $time), date('i', $time), date('s', $time), date('m', $current_day_unix),
                  date('d', $current_day_unix),
                  date('Y', $current_day_unix));
                $current_day  = (date('w', $current_day_unix) ? date('w', $current_day_unix) - 1 : 6);

                $block_current_time = false;
                //проверяем обычные блокировки, если есть
                if (isset($blocks[$type][$sport][$area])) {
                  foreach ($blocks[$type][$sport][$area] as $block_data) {
                    if ($current_time >= $block_data[0] && $current_time < $block_data[1]) {
                      $block_current_time = true;
                    }
                  }
                  [$blocks_start_unix, $blocks_finish_unix] = $blocks[$type][$sport][$area][0];
                }

                //запоминаем периодические блокировки, если есть
                $pblocks_date_start_unix = $pblocks_date_finish_unix = $pblocks_time_start_unix = $pblocks_time_finish_unix = $pblocks_weekdays = 0;
                $periodic_blocked        = false;
                if (isset($blocks_periodical[$type][$sport][$area])) {
                  foreach ($blocks_periodical[$type][$sport][$area] as $block_data) {
                    [
                      $pblocks_date_start_unix,
                      $pblocks_date_finish_unix,
                      $pblocks_time_start_unix,
                      $pblocks_time_finish_unix,
                      $pblocks_weekdays,
                    ] = $block_data;

                    $current_day = (date('w', $current_day_unix) ? date('w', $current_day_unix) - 1 : 6);

                    if (
                      $current_day_unix >= $pblocks_date_start_unix &&
                      $current_day_unix <= $pblocks_date_finish_unix &&
                      $time >= $pblocks_time_start_unix &&
                      $time < $pblocks_time_finish_unix &&
                      in_array($current_day, $pblocks_weekdays)
                    ) {
                      $periodic_blocked = true;
                      break; // достаточно одной совпадающей блокировки
                    }
                  }
                }
                //проверяем работает ли в это время площадка в этот день
                [$start, $finish] = $weekdays[date('w', $current_day_unix)];
                $start_unix  = strtotime($start);
                $finish      = ($finish == '24:00:00' ? '23:59:59' : $finish);
                $finish_unix = strtotime($finish);
                if ($start_unix > $time || $finish_unix < $time) {
                  $class = ' class="period_unavaliable1"';
                  //проверяем есть ли праздник
                } elseif (isset($holidays[date('Y-m-d', $current_day_unix)])) {
                  $class = ' class="period_blocked"';
                } elseif ($block_current_time) {
                  $class = ' class="period_blocked"';
                } //проверяем есть ли периодическая блокировака
                elseif ($periodic_blocked) {
                  $class = ' class="period_blocked"';
                } //проверяем заказана ли площадка
                elseif (isset($reserved[$type][$sport][$area][date('Y-m-d', $current_day_unix)][date('H:i:s', $time)])) {
                  $class = ' class="period_ordered"';
                } //проверяем есть ли абонемент
                elseif (isset($tickets[$type][$sport][$area][date('Y-m-d', $current_day_unix)][date('H:i:s', $time)])) {
                  $class = ' class="period_ordered"';
                }
              } else {
                $class = ' class="period_unavaliable"';
              }

              $out              .= '<td' . $class . '>&nbsp;</td>'; //рисуем ячейку таблицы
              $current_day_unix = mktime(0, 0, 0, date('m', $current_day_unix), date('d', $current_day_unix) + 1,
                date('Y', $current_day_unix)); //увеличиваем дату на 1 день
            }
            $out  .= '</tr>' . "\n"; //закрываем строку таблицы
            $time = mktime(
              date('H', $time),
              date('i', $time) + $area_period[$area_id],
              date('s', $time),
              date('m', $time),
              date('d', $time),
              date('Y', $time)
            ); //увеличиваем дату на 1 час
          }
          $out .= '</table>'; //закрываем таблицу
        }
      }
    }
  } else {
    $out = lang('Incorrect date', 'message_error');
  }
}

$out = '<div id="main">
<div id="top"><h1>' . config('app')->getProjectTitle() . '<br>' . (isset($type_title) ? $type_title . '<br />' : '') . '<b>' . $start_day . '/' . $start_month . '/' . $start_year . ' - ' . $end_day . '/' . $end_month . '/' . $end_year . '</b></h1></div>
' . $out . '
<p>&nbsp;</p>
</div>
';

$_page['content'][0] = $out;
$_page['key']        = 'report_reservations_as_table';

