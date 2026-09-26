<?php

use AC\core\engines\Engines;

class reservations_reports
{

  /**
   * @var Engines
   */
  public $r;
  public $values_titles;

  function start()
  {
    $this->values_titles[0] = lang('Reservable period (in days)', 'reports');
    $this->values_titles[1] = lang('Cancellation latest before (in days)', 'reports');
    $this->r                = Service::engines();
    $action                 = Service::request()->_('action');
    switch ($action) {
      //показать отчет
      case 'showReport':
        return $this->showReport();
      default:
        return $this->mainPage();
    }
  }

  //первая страница
  function mainPage()
  {
    $out = "<h1>" . lang('Reports', 'reports') . "</h1>\n";

    if ($areas_types = $this->r->areas->getSportsByType(false)) {
      //строка таблица c выбором режима отчета (все заказы/только абонементы)
      $mode_select = "	<tr>\n";
      $mode_select .= '		<td class="light">' . lang('Type') . '</td>' . "\n";
      $mode_select .= '		<td class="light">' . "\n";
      $mode_select .= '			<table cellspacing="2" cellpadding="0">' . "\n";
      $mode_select .= '				<tr>' . "\n";
      $mode_select .= '					<td><input type="radio" name="mode" value="1" checked></td>' . "\n";
      $mode_select .= '					<td>' . lang('General', 'reports') . '</td>' . "\n";
      $mode_select .= '				</tr>' . "\n";
      $mode_select .= '				<tr>' . "\n";
      $mode_select .= '					<td><input type="radio" name="mode" value="2"></td>' . "\n";
      $mode_select .= '					<td>' . lang('Individual lessons only', 'reports') . '</td>' . "\n";
      $mode_select .= '				</tr>' . "\n";
      $mode_select .= '				<tr>' . "\n";
      $mode_select .= '					<td><input type="radio" name="mode" value="3"></td>' . "\n";
      $mode_select .= '					<td>' . lang('Abos only', 'reports') . '</td>' . "\n";
      $mode_select .= '				</tr>' . "\n";
      $mode_select .= '			</table>' . "\n";
      $mode_select .= '		</td>' . "\n";
      $mode_select .= "	</tr>\n";

      //выбор типа площадки (открытые/закрытые)
      $type_select = "	<tr>\n";
      $type_select .= '		<td class="dark">' . lang('Place') . ':</td>' . "\n";
      $type_select .= '		<td class="dark">';
      $type_select .= '			<table cellspacing="2" cellpadding="0">' . "\n";
      $type_select .= '				<tr>' . "\n";
      $type_select .= '					<td><input type="radio" name="type" value="0" checked></td>' . "\n";
      $type_select .= '					<td>' . lang('All') . '</td>' . "\n";
      $type_select .= '				</tr>' . "\n";
      foreach ($areas_types as $index => $a) {
        $type_select .= '				<tr>' . "\n";
        $type_select .= '					<td><input type="radio" name="type" value="' . $index . '"></td>' . "\n";
        $type_select .= '					<td>' . $a->title_site_url . '</td>' . "\n";
        $type_select .= '				</tr>' . "\n";
      }
      $type_select .= '			</table>' . "\n";
      $type_select .= "</td>\n";
      $type_select .= "	</tr>\n";

      $tb = getThemeBuilder();

      $out .= '<table align="center" border="0" cellspacing="8">' . "\n";
      $out .= '<tr style="display: flex;flex-wrap: wrap;">
      <td valign="top">' . "\n";

      //статистика за день
      $out .= '<form action="reservations_stats_day.php" method="get" target="_blank">' . "\n";
      $out .= '<table cellspacing="1" cellpadding="3" class="main" border="0">' . "\n";
      $out .= '	<tr><th colspan="3">' . lang('Day Report', 'reports') . '</th></tr>' . "\n";
      $out .= '	<tr><td class="light">' . lang('Date') . ':</td><td class="light">' . "\n";
      $out .= '		<table cellspacing="0" cellpadding="0" border="0"><tr>' . "\n";
      $min_date_reserv = $this->r->getMinDateReservation();
      $min_date_abo = $this->r->getMinDateAbo();

      $min_date = $min_date_reserv > $min_date_abo ? $min_date_abo : $min_date_reserv;
      //годы
      $select_years = '<select name="year">' . "\n";
      for ($i = date('Y',$min_date); $i <= date('Y') + 1; $i++) {
        $select_years .= '<option value="' . $i . '"' . ($i == date('Y') ? ' selected' : '') . '>' . $i . '</option>' . "\n";
      }
      $select_years .= '</select>' . "\n";
      $out          .= '			<td>' . $select_years . '</td>';

      //месяцы
      $select_monthes = '<select name="month">' . "\n";
      for ($i = 1; $i <= 12; $i++) {
        $select_monthes .= '<option value="' . sprintf(
            "%02d",
            $i
          ) . '"' . ($i == date('m') ? ' selected' : '') . '>' . sprintf("%02d", $i) . '</option>' . "\n";
      }
      $select_monthes .= '</select>' . "\n";
      $out            .= '			<td>' . $select_monthes . '</td>';

      //дни
      for ($i = 1; $i <= 31; $i++) {
        $days[sprintf("%02d", $i)] = sprintf("%02d", $i);
      }
      $out .= '			<td>' . $tb->select('day', $days, date('d')) . '</td>';
      $out .= "			</tr></table></td>\n";
      $out .= $type_select . $mode_select;
      $out .= "	</tr>\n";
      $out .= "	<tr>\n";
      $out .= '	<tr><td class="dark" colspan="2" align="center"><input type="submit" value="' . lang(
          'Execute'
        ) . '" class="button"/></td></tr>' . "\n";
      $out .= '</table></form>' . "\n";

      $out .= '</td><td valign="top">' . "\n";

      //отчет за месяц
      $out .= '<form action="reservations_stats_month.php" method="get" target="_blank">' . "\n";
      $out .= '<table cellspacing="1" cellpadding="3" class="main" border="0">' . "\n";
      $out .= '	<tr><th colspan="2">' . lang('Monthly report', 'reports') . '</th></tr>' . "\n";
      $out .= '	<tr><td class="light">' . lang('Year') . ':</td><td class="light"><table border="0"><tr><td>' . $select_years . '</td><td>' . lang(
          'Month'
        ) . '</td><td>' . $select_monthes . "</td></tr></table></td></tr>\n";
      $out .= $type_select . $mode_select;
      $out .= '	<tr><td class="dark" colspan="2" align="center"><input type="submit" value="' . lang(
          'Execute'
        ) . '" class="button"/></td></tr>' . "\n";
      $out .= "</table>\n";
      $out .= "</form>\n";

      $out .= '</td>' . "\n";

      if (isset($_POST['year']) && isset($_POST['month'])) {
        $c_date = $_POST['year'] . '-' . $_POST['month'] . '-01';
      } else {
        $c_date = '';
      }

      //годы
      $select_years = '<select name="year">' . "\n";
      for ($i = date('Y',$min_date); $i <= date('Y') + 1; $i++) {
        $select_years .= '<option value="' . $i . '"' . ($i == date(
            'Y',
            strtotime($c_date)
          ) ? ' selected' : '') . '>' . $i . '</option>' . "\n";
      }
      $select_years .= '</select>' . "\n";

      //месяцы
      $select_monthes = '<select name="month">' . "\n";
      for ($i = 1; $i <= 12; $i++) {
        $select_monthes .= '<option value="' . sprintf("%02d", $i) . '"' . ($i == date(
            'm',
            strtotime($c_date)
          ) ? ' selected' : '') . '>' . sprintf("%02d", $i) . '</option>' . "\n";
      }
      $select_monthes .= '</select>' . "\n";

      /////////////////////////////////
      //отчет за перио
      $out .= '<td valign="top">' . "\n";
      $out .= '<form action="reservations_stats_period.php" method="get" target="_blank">' . "\n";
      $out .= '<table cellspacing="1" cellpadding="3" class="main" border="0">' . "\n";
      $out .= '	<tr><th colspan="2">' . lang('Periodic report', 'reports') . '</th></tr>' . "\n";
      $out .= '	<tr><td class="light">' . lang('From') . ':</td><td class="light">' . "\n";
      $out .= '		<table cellspacing="0" cellpadding="0" border="0"><tr>' . "\n";
      //--------------- первая дата ----------------------------
      //годы
      $select_years = '<select name="start_year">' . "\n";
      for ($i = date('Y',$min_date); $i <= date('Y') + 1; $i++) {
        $select_years .= '<option value="' . $i . '"' . ($i == date('Y') ? ' selected' : '') . '>' . $i . '</option>' . "\n";
      }
      $select_years .= '</select>' . "\n";
      $out          .= '			<td>' . $select_years . '</td>';

      //месяцы
      $select_monthes = '<select name="start_month">' . "\n";
      for ($i = 1; $i <= 12; $i++) {
        $select_monthes .= '<option value="' . sprintf(
            "%02d",
            $i
          ) . '"' . ($i == date('m') ? ' selected' : '') . '>' . sprintf("%02d", $i) . '</option>' . "\n";
      }
      $select_monthes .= '</select>' . "\n";
      $out            .= '			<td>' . $select_monthes . '</td>';

      //дни
      for ($i = 1; $i <= 31; $i++) {
        $days[sprintf("%02d", $i)] = sprintf("%02d", $i);
      }
      $out .= '			<td>' . $tb->select('start_day', $days, date('d')) . '</td>';
      $out .= "			</tr></table></td>\n";

      //--------------- вторая дата ----------------------------
      $out .= '	<tr><td class="light">' . lang('Until') . ':</td><td class="light">' . "\n";
      $out .= '		<table cellspacing="0" cellpadding="0" border="0"><tr>' . "\n";

      //годы
      $select_years = '<select name="end_year">' . "\n";
      for ($i = date('Y',$min_date); $i <= date('Y') + 1; $i++) {
        $select_years .= '<option value="' . $i . '"' . ($i == date('Y') ? ' selected' : '') . '>' . $i . '</option>' . "\n";
      }
      $select_years .= '</select>' . "\n";
      $out          .= '			<td>' . $select_years . '</td>';

      //месяцы
      $select_monthes = '<select name="end_month">' . "\n";
      for ($i = 1; $i <= 12; $i++) {
        $select_monthes .= '<option value="' . sprintf(
            "%02d",
            $i
          ) . '"' . ($i == date('m') ? ' selected' : '') . '>' . sprintf("%02d", $i) . '</option>' . "\n";
      }
      $select_monthes .= '</select>' . "\n";
      $out            .= '			<td>' . $select_monthes . '</td>';

      //дни
      for ($i = 1; $i <= 31; $i++) {
        $days[sprintf("%02d", $i)] = sprintf("%02d", $i);
      }
      $out .= '			<td>' . $tb->select('end_day', $days, date('d')) . '</td>';
      $out .= "			</tr></table></td>\n";

      //выбор типа площадки (тенни/сквош/...)
      $out .= "	<tr>\n";
      $out .= '		<td class="dark">' . lang('Place') . ':</td>' . "\n";
      $out .= '		<td class="dark">';
      $out .= '			<table cellspacing="2" cellpadding="0">' . "\n";
      $out .= '				<tr>' . "\n";
      $out .= '					<td><input type="radio" name="type" value="0" checked></td>' . "\n";
      $out .= '					<td>' . lang('All') . '</td>' . "\n";
      $out .= '				</tr>' . "\n";
      foreach ($areas_types as $index => $a) {
        $out .= '				<tr>' . "\n";
        $out .= '					<td><input type="radio" name="type" value="' . $index . '"></td>' . "\n";
        $out .= '					<td>' . $a->title_site_url . '</td>' . "\n";
        $out .= '				</tr>' . "\n";
      }
      $out .= '			</table>' . "\n";


      $out .= "</td>\n";
      $out .= "	</tr>\n";

      $out .= '	<tr><td class="dark" colspan="2" align="center"><input type="submit" value="' . lang(
          'Execute'
        ) . '" class="button"/></td></tr>' . "\n";
      $out .= "</table>\n";
      $out .= "</form>\n";

      $out .= '</td>' . "\n";

      ///////////////////////////////
      //таблица заказов
      $out .= '<td valign="top">' . "\n";
      $out .= '<form action="reservations_orders_table.php" method="get" target="_blank">' . "\n";
      $out .= '<table cellspacing="1" cellpadding="3" class="main" border="0">' . "\n";
      $out .= '	<tr><th colspan="2">' . lang('Game schedule overview', 'reports') . '</th></tr>' . "\n";
      $out .= '	<tr><td class="light">' . lang('From') . ':</td><td class="light">' . "\n";
      $out .= '		<table cellspacing="0" cellpadding="0" border="0"><tr>' . "\n";
      //--------------- первая дата ----------------------------
      //годы
      $select_years = '<select name="start_year">' . "\n";
      for ($i = date('Y',$min_date); $i <= date('Y') + 1; $i++) {
        $select_years .= '<option value="' . $i . '"' . ($i == date('Y') ? ' selected' : '') . '>' . $i . '</option>' . "\n";
      }
      $select_years .= '</select>' . "\n";
      $out          .= '			<td>' . $select_years . '</td>';

      //месяцы
      $select_monthes = '<select name="start_month">' . "\n";
      for ($i = 1; $i <= 12; $i++) {
        $select_monthes .= '<option value="' . sprintf(
            "%02d",
            $i
          ) . '"' . ($i == date('m') ? ' selected' : '') . '>' . sprintf("%02d", $i) . '</option>' . "\n";
      }
      $select_monthes .= '</select>' . "\n";
      $out            .= '			<td>' . $select_monthes . '</td>';

      //дни
      for ($i = 1; $i <= 31; $i++) {
        $days[sprintf("%02d", $i)] = sprintf("%02d", $i);
      }
      $out .= '			<td>' . $tb->select('start_day', $days, date('d')) . '</td>';
      $out .= "			</tr></table></td>\n";

      //--------------- вторая дата ----------------------------
      $out .= '	<tr><td class="light">' . lang('Until') . ':</td><td class="light">' . "\n";
      $out .= '		<table cellspacing="0" cellpadding="0" border="0"><tr>' . "\n";

      //годы
      $select_years = '<select name="end_year">' . "\n";
      for ($i = date('Y',$min_date); $i <= date('Y') + 1; $i++) {
        $select_years .= '<option value="' . $i . '"' . ($i == date('Y') ? ' selected' : '') . '>' . $i . '</option>' . "\n";
      }
      $select_years .= '</select>' . "\n";
      $out          .= '			<td>' . $select_years . '</td>';

      //месяцы
      $select_monthes = '<select name="end_month">' . "\n";
      for ($i = 1; $i <= 12; $i++) {
        $select_monthes .= '<option value="' . sprintf(
            "%02d",
            $i
          ) . '"' . ($i == date('m') ? ' selected' : '') . '>' . sprintf("%02d", $i) . '</option>' . "\n";
      }
      $select_monthes .= '</select>' . "\n";
      $out            .= '			<td>' . $select_monthes . '</td>';

      //дни
      for ($i = 1; $i <= 31; $i++) {
        $days[sprintf("%02d", $i)] = sprintf("%02d", $i);
      }
      $out .= '			<td>' . $tb->select('end_day', $days, date('d')) . '</td>';
      $out .= "			</tr></table></td>\n";

      //выбор типа площадки (тенни/сквош/...)
      $out .= "	<tr>\n";
      $out .= '		<td class="dark">' . lang('Place') . ':</td>' . "\n";
      $out .= '		<td class="dark">';
      $out .= '			<table cellspacing="2" cellpadding="0">' . "\n";
      $out .= '				<tr>' . "\n";
      $out .= '					<td><input type="radio" name="type" value="0" checked></td>' . "\n";
      $out .= '					<td>' . lang('All') . '</td>' . "\n";
      $out .= '				</tr>' . "\n";
      foreach ($areas_types as $index => $a) {
        $out .= '				<tr>' . "\n";
        $out .= '					<td><input type="radio" name="type" value="' . $index . '"></td>' . "\n";
        $out .= '					<td>' . $a->title_site_url . '</td>' . "\n";
        $out .= '				</tr>' . "\n";
      }
      $out .= '			</table>' . "\n";

      $out .= "</td>\n";
      $out .= "	</tr>\n";

      $out .= '	<tr><td class="dark" colspan="2" align="center"><input type="submit" value="' . lang(
          'Execute'
        ) . '" class="button"/></td></tr>' . "\n";
      $out .= "</table>\n";
      $out .= "</form>\n";
      $out .= '</td>' . "\n";
      
      
      $out .= '<td valign="top">' . "\n";
      
      //отчет за месяц
      $out .= '<form action="reservations_stats_cash_user.php" method="get" target="_blank">' . "\n";
      $out .= '<table cellspacing="1" cellpadding="3" class="main" border="0">' . "\n";
      $out .= '	<tr><th colspan="2">Übersicht Guthaben der Kunden</th></tr>' . "\n";
      $out .= '	<tr><td class="light">Jahr:</td><td class="light"><table border="0"><tr><td>' . $select_years . '</td><td>Monat</td><td>' . $select_monthes . "</td></tr></table></td></tr>\n";
      $out .= '	<tr><td class="light">&nbsp;</td><td class="light"><input type="checkbox" name="excel" value="1"> Excel</td></tr>' . "\n";
      $out .= '	<tr><td class="dark" colspan="2" align="center"><input type="submit" value="Ausf&uuml;hren" class="button"/></td></tr>' . "\n";
      $out .= "</table>\n";
      $out .= "</form>\n";
      $out .= '</td>' . "\n";
      
      
      if (defined("SHOW_ADMIN_BAR") && SHOW_ADMIN_BAR) {
        $out .= '<td valign="top">' . "\n";
        $out .= '<form action="reservations_stats_day_bar.php" method="get" target="_blank">' . "\n";
        $out .= '<table cellspacing="1" cellpadding="3" class="main" border="0">' . "\n";
        $out .= ' <tr><th colspan="2">' . lang('Cash report', 'reports') . '</th></tr>' . "\n";
        $out .= ' <tr><td class="light">' . lang('Date') . ':</td><td class="light">';
        $out .= '   <table cellspacing="0" cellpadding="0" border="0"><tr>' . "\n";

        //годы
        $select_years = '<select name="year">' . "\n";
        for ($i = date('Y',$min_date); $i <= date('Y'); $i++) {
          $select_years .= '<option value="' . $i . '"' . ($i == date('Y') ? ' selected' : '') . '>' . $i . '</option>' . "\n";
        }
        $select_years .= '</select>' . "\n";
        $out          .= '      <td>' . $select_years . '</td>';

        //месяцы
        $select_monthes = '<select name="month">' . "\n";
        for ($i = 1; $i <= 12; $i++) {
          $select_monthes .= '<option value="' . sprintf(
              "%02d",
              $i
            ) . '"' . ($i == date('m') ? ' selected' : '') . '>' . sprintf("%02d", $i) . '</option>' . "\n";
        }
        $select_monthes .= '</select>' . "\n";
        $out            .= '      <td>' . $select_monthes . '</td>';

        //дни
        for ($i = 1; $i <= 31; $i++) {
          $days[sprintf("%02d", $i)] = sprintf("%02d", $i);
        }
        $out .= '     <td>' . $tb->select('day', $days, date('d')) . '</td>';
        $out .= "     </tr></table>";
        $out .= "</td></tr>\n";
        
        $out .= '	<tr><td class="dark" colspan="2">
							<input type="radio" name="interval" value="all" checked/> Gesamtbericht Ganztags<br />
							<br /><strong>Wochentags:</strong><br/>
							<input type="radio" name="interval" value="08:00|14:00"/> Schicht 1: 08:00 – 14:00<br />
							<input type="radio" name="interval" value="14:00|17:00"/> Schicht 2: 14:00 – 17:00<br />
							<input type="radio" name="interval" value="17:00|22:00"/> Schicht 3: 17:00 – 22:00<br />
							<br /><strong>Samstag:</strong><br/>
							<input type="radio" name="interval" value="08:00|14:00"/> Schicht 4: 08.00 – 14.00<br />
							<input type="radio" name="interval" value="14:00|22:00"/> Schicht 5: 14.00 – 22.00<br />
							<br /><strong>Sonntag:</strong><br/>
							<input type="radio" name="interval" value="08:00|16:00"/> Schicht 6: 08.00 – 16.00<br />
							<input type="radio" name="interval" value="16:00|22:00"/> Schicht 7: 16.00 – 22.00<br />
						</td></tr>' . "\n";
        
        $out .= ' <tr><td class="dark" colspan="2" align="center"><input type="submit" value="' . lang(
            'Execute'
          ) . '" class="button"/></td></tr>' . "\n";
        $out .= "</table>\n";
        $out .= "</form>\n";

        $out .= '</td>' . "\n";
      }
      //по дню регистрации бронирования
      $out .= '<td valign="top">' . "\n";
      $out .= '<form action="reservations_stats_period_by_data_reservation.php" method="get" target="_blank">' . "\n";
      $out .= '<table cellspacing="1" cellpadding="3" class="main" border="0">' . "\n";
      $out .= '	<tr><th colspan="2">Payone-Zahlungen zum Zahlungszeitpunkt</th></tr>' . "\n";
      $out .= '	<tr><td class="light">Von:</td><td class="light">' . "\n";
      $out .= '		<table cellspacing="0" cellpadding="0" border="0"><tr>' . "\n";
      //--------------- первая дата ----------------------------
      //годы
      $select_years = '<select name="start_year">' . "\n";
      for ($i = date('Y') - 1; $i <= date('Y'); $i++) {
        $select_years .= '<option value="' . $i . '"' . ($i == date('Y') ? ' selected' : '') . '>' . $i . '</option>' . "\n";
      }
      $select_years .= '</select>' . "\n";
      $out          .= '			<td>' . $select_years . '</td>';
      
      //месяцы
      $select_monthes = '<select name="start_month">' . "\n";
      for ($i = 1; $i <= 12; $i++) {
        $select_monthes .= '<option value="' . sprintf('%02d', $i) . '"' . ($i == date('m') ? ' selected' : '') . '>' . sprintf(
            '%02d',
            $i
          ) . '</option>' . "\n";
      }
      $select_monthes .= '</select>' . "\n";
      $out            .= '			<td>' . $select_monthes . '</td>';
      
      //дни
      for ($i = 1; $i <= 31; $i++) {
        $days[sprintf('%02d', $i)] = sprintf('%02d', $i);
      }
      $out .= '			<td>' . $tb->select('start_day', $days, date('d')) . '</td>';
      $out .= "			</tr></table></td>\n";
      
      //--------------- вторая дата ----------------------------
      $out .= '	<tr><td class="light">Bis:</td><td class="light">' . "\n";
      $out .= '		<table cellspacing="0" cellpadding="0" border="0"><tr>' . "\n";
      
      //годы
      $select_years = '<select name="end_year">' . "\n";
      for ($i = date('Y') - 1; $i <= date('Y'); $i++) {
        $select_years .= '<option value="' . $i . '"' . ($i == date('Y') ? ' selected' : '') . '>' . $i . '</option>' . "\n";
      }
      $select_years .= '</select>' . "\n";
      $out          .= '			<td>' . $select_years . '</td>';
      
      //месяцы
      $select_monthes = '<select name="end_month">' . "\n";
      for ($i = 1; $i <= 12; $i++) {
        $select_monthes .= '<option value="' . sprintf('%02d', $i) . '"' . ($i == date('m') ? ' selected' : '') . '>' . sprintf(
            '%02d',
            $i
          ) . '</option>' . "\n";
      }
      $select_monthes .= '</select>' . "\n";
      $out            .= '			<td>' . $select_monthes . '</td>';
      
      //дни
      for ($i = 1; $i <= 31; $i++) {
        $days[sprintf('%02d', $i)] = sprintf('%02d', $i);
      }
      $out .= '			<td>' . $tb->select('end_day', $days, date('d')) . '</td>';
      $out .= "			</tr></table></td>\n";
      ///////////////////////////////
      //export
      $out .= '<td valign="top">' . "\n";
      $out .= '<form action="reservations_stats_export.php" method="get" target="_blank">' . "\n";
      $out .= '<table cellspacing="1" cellpadding="3" class="main" border="0">' . "\n";
      $out .= '	<tr><th colspan="2">' . lang('Export', 'reports') . '</th></tr>' . "\n";
      $out .= '	<tr><td class="light">' . lang('From') . ':</td><td class="light">' . "\n";
      $out .= '		<table cellspacing="0" cellpadding="0" border="0"><tr>' . "\n";
      //--------------- первая дата ----------------------------
      //годы
      $select_years = '<select name="start_year">' . "\n";
      for ($i = date('Y',$min_date); $i <= date('Y'); $i++) {
        $select_years .= '<option value="' . $i . '"' . ($i == date('Y') ? ' selected' : '') . '>' . $i . '</option>' . "\n";
      }
      $select_years .= '</select>' . "\n";
      $out          .= '			<td>' . $select_years . '</td>';

      //месяцы
      $select_monthes = '<select name="start_month">' . "\n";
      for ($i = 1; $i <= 12; $i++) {
        $select_monthes .= '<option value="' . sprintf("%02d", $i) . '"' . ($i == date('m') ? ' selected' : '') . '>' . sprintf(
            "%02d",
            $i
          ) . '</option>' . "\n";
      }
      $select_monthes .= '</select>' . "\n";
      $out            .= '			<td>' . $select_monthes . '</td>';

      //дни
      for ($i = 1; $i <= 31; $i++) {
        $days[sprintf("%02d", $i)] = sprintf("%02d", $i);
      }
      $out .= '			<td>' . $tb->select('start_day', $days, date('d')) . '</td>';
      $out .= "			</tr></table></td>\n";

      //--------------- вторая дата ----------------------------
      $out .= '	<tr><td class="light">' . lang('Until') . ':</td><td class="light">' . "\n";
      $out .= '		<table cellspacing="0" cellpadding="0" border="0"><tr>' . "\n";

      //годы
      $select_years = '<select name="end_year">' . "\n";
      for ($i = date('Y',$min_date); $i <= date('Y'); $i++) {
        $select_years .= '<option value="' . $i . '"' . ($i == date('Y') ? ' selected' : '') . '>' . $i . '</option>' . "\n";
      }
      $select_years .= '</select>' . "\n";
      $out          .= '			<td>' . $select_years . '</td>';

      //месяцы
      $select_monthes = '<select name="end_month">' . "\n";
      for ($i = 1; $i <= 12; $i++) {
        $select_monthes .= '<option value="' . sprintf("%02d", $i) . '"' . ($i == date('m') ? ' selected' : '') . '>' . sprintf(
            "%02d",
            $i
          ) . '</option>' . "\n";
      }
      $select_monthes .= '</select>' . "\n";
      $out            .= '			<td>' . $select_monthes . '</td>';

      //дни
      for ($i = 1; $i <= 31; $i++) {
        $days[sprintf("%02d", $i)] = sprintf("%02d", $i);
      }
      $out .= '			<td>' . $tb->select('end_day', $days, date('d')) . '</td>';
      $out .= "			</tr></table></td>\n";
      $out .= $type_select;
      $out .= $mode_select;


//      //выбор типа площадки (тенни/сквош/...)
//      $out .= "	<tr>\n";
//      $out .= '		<td class="dark">Pl&auml;tze:</td>' . "\n";
//      $out .= '		<td class="dark">';
//      $out .= '<input type="radio" name="type_id" value="0" checked>Alle <br>';
//      foreach ($areas_types as $index => $a)
//        $out .= '<input type="radio" name="type_id" value="' . $a['type_id'] . '"> ' . $a['title'] . '<br>';
//      $out .= "</td>\n";
      $out .= "	</tr>\n";

      $out .= '	<tr>
	                <td class="dark" colspan="2" align="center">
	                  <input type="submit" name="action" value="' . lang('Execute') . '" class="button"/>
	                  <input type="submit" name="action" value="CSV" class="button"/></td>
	              </tr>' . "\n";
      $out .= "</table>\n";

      $out .= "</form>\n";

      $out .= '</td>' . "\n";

      //export
      ///////////////////////////////

      $out .= '</tr>';
      $out .= '<tr style="display: flex;flex-wrap: wrap;">';
      $out .= module('reports')->execContent();
      $out .= '</tr>';

      $out .= '</table>' . "\n";
    }

    return array($out);
  }

}

$a = new reservations_reports;
$_page['content'] = $a->start();
$_page['key'] = 'reservations_reports_common';
//$_page['js'][] = 'clients';
