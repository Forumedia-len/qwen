<?php

namespace AC\core\modules\reports\controllers\admin;

use AC\core\engines\Engines;
use AC\core\system\controller\BaseController;
use AC\app\locators\Service;

class ReportsReservationsController extends BaseController
{
  public $default_action   = 'mainPage';
  public $default_template = 'reports';
  
  /**
   * @var Engines
   */
  protected $engines;
  
  public $values_titles;
  
  public function __construct($action = false, $runController = true, $segments = [])
  {
    $this->engines          = Service::engines();
    $this->values_titles[0] = lang('Reservable period (in days)', 'reports');
    $this->values_titles[1] = lang('Cancellation latest before (in days)', 'reports');
    
    parent::__construct($action, $runController, $segments);
  }
  
  public function start()
  {
    $action = Service::request()->_('action');
    return match ($action) {
      'showReport' => $this->showReport(),
      default      => $this->mainPage(),
    };
  }
  
  /**
   * Главная страница с формами отчетов
   */
  public function mainPage()
  {
    $areas_types = $this->engines->areas->getSportsByType(false);
    
    if (!$areas_types) {
      return '';
    }
    
    $min_date = min($this->engines->getMinDateReservation(), $this->engines->getMinDateAbo());
    
    $current_year  = date('Y');
    $current_month = date('m');
    $current_day   = date('d');
    
    // Генерируем общие селекты
    $type_select = $this->generateTypeSelect($areas_types);
    $mode_select = $this->generateModeSelect();
    
    $data = [
      'areas_types'    => $areas_types,
      'min_date'       => $min_date,
      'current_year'   => $current_year,
      'current_month'  => $current_month,
      'current_day'    => $current_day,
      'values_titles'  => $this->values_titles,
      'show_admin_bar' => defined("SHOW_ADMIN_BAR") && SHOW_ADMIN_BAR,
      'type_select'    => $type_select,
      'mode_select'    => $mode_select,
    ];
    
    return $this->render('main', $data);
  }
  
  /**
   * Показать отчет
   */
  public function showReport()
  {
    // TODO: Реализовать логику показа отчета
    return '';
  }
  
  /**
   * Генерация селекта годов
   */
  protected function generateYearSelect($name, $min_date, $max_year = null, $selected = null)
  {
    $max_year = $max_year ?? date('Y') + 1;
    $selected = $selected ?? date('Y');
    $min_year = date('Y', $min_date);
    
    $select = '<select name="' . $name . '">' . "\n";
    for ($i = $min_year; $i <= $max_year; $i++) {
      $select .= '<option value="' . $i . '"' . ($i == $selected ? ' selected' : '') . '>' . $i . '</option>' . "\n";
    }
    $select .= '</select>' . "\n";
    
    return $select;
  }
  
  /**
   * Генерация селекта месяцев
   */
  protected function generateMonthSelect($name, $selected = null)
  {
    $selected = $selected ?? date('m');
    $select   = '<select name="' . $name . '">' . "\n";
    for ($i = 1; $i <= 12; $i++) {
      $month  = sprintf("%02d", $i);
      $select .= '<option value="' . $month . '"' . ($i == (int)$selected ? ' selected' : '') . '>' . $month . '</option>' . "\n";
    }
    $select .= '</select>' . "\n";
    
    return $select;
  }
  
  /**
   * Генерация селекта дней
   */
  protected function generateDaySelect($name, $selected = null)
  {
    $selected = $selected ?? date('d');
    $days     = [];
    for ($i = 1; $i <= 31; $i++) {
      $days[sprintf("%02d", $i)] = sprintf("%02d", $i);
    }
    
    $tb = getThemeBuilder();
    return $tb->select($name, $days, $selected);
  }
  
  /**
   * Генерация выбора типа площадки
   */
  protected function generateTypeSelect($areas_types, $name = 'type')
  {
    $out = "	<tr>\n";
    $out .= '		<td class="dark">' . lang('Place') . ':</td>' . "\n";
    $out .= '		<td class="dark">';
    $out .= '			<table cellspacing="2" cellpadding="0">' . "\n";
    $out .= '				<tr>' . "\n";
    $out .= '					<td><input type="radio" name="' . $name . '" value="0" checked></td>' . "\n";
    $out .= '					<td>' . lang('All') . '</td>' . "\n";
    $out .= '				</tr>' . "\n";
    foreach ($areas_types as $index => $a) {
      $out .= '				<tr>' . "\n";
      $out .= '					<td><input type="radio" name="' . $name . '" value="' . $index . '"></td>' . "\n";
      $out .= '					<td>' . $a->title_site_url . '</td>' . "\n";
      $out .= '				</tr>' . "\n";
    }
    $out .= '			</table>' . "\n";
    $out .= "</td>\n";
    $out .= "	</tr>\n";
    
    return $out;
  }
  
  /**
   * Генерация выбора режима отчета
   */
  protected function generateModeSelect($name = 'mode')
  {
    $mode_select = "	<tr>\n";
    $mode_select .= '		<td class="light">' . lang('Type') . '</td>' . "\n";
    $mode_select .= '		<td class="light">' . "\n";
    $mode_select .= '			<table cellspacing="2" cellpadding="0">' . "\n";
    $mode_select .= '				<tr>' . "\n";
    $mode_select .= '					<td><input type="radio" name="' . $name . '" value="1" checked></td>' . "\n";
    $mode_select .= '					<td>' . lang('General', 'reports') . '</td>' . "\n";
    $mode_select .= '				</tr>' . "\n";
    $mode_select .= '				<tr>' . "\n";
    $mode_select .= '					<td><input type="radio" name="' . $name . '" value="2"></td>' . "\n";
    $mode_select .= '					<td>' . lang('Individual lessons only', 'reports') . '</td>' . "\n";
    $mode_select .= '				</tr>' . "\n";
    $mode_select .= '				<tr>' . "\n";
    $mode_select .= '					<td><input type="radio" name="' . $name . '" value="3"></td>' . "\n";
    $mode_select .= '					<td>' . lang('Abos only', 'reports') . '</td>' . "\n";
    $mode_select .= '				</tr>' . "\n";
    $mode_select .= '			</table>' . "\n";
    $mode_select .= '		</td>' . "\n";
    $mode_select .= "	</tr>\n";
    
    return $mode_select;
  }
}

