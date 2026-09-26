<?php

namespace AC\core\modules\reports\controllers\admin;

use AC\app\controllers\AdminController;
use Service;
use AC\core\modules\membershipFees\actions\FormsReport;
use AC\core\system\object\entity\format\CSV;

class ReportsController extends AdminController
{
  public $default_action = 'showForms';
  public function showForms()
  {
    $reportForms = [];
    $search      = Service::locator()->search('actions\FormsReport', 'php', false);
    foreach ($search as $path) {
      $className = Service::locator()->getClassname($path);
      if (self::class != $className) {
        $reportForms[] = useClass(Service::locator()->getClassname($path), true, null, false)->showForms();
      }
    }
    $reportForms = array_merge(...$reportForms);
    
    return $this->render('main', ['reportForms' => $reportForms]);
  }
  
  public function view(): string
  {
    Service::history('test', ['useFullHistory' => true]);
    $report = Service::request()->_get('report');
    $this->setViewKey($report);
    $data = (new FormsReport())->getData($report);
    if(isset($data['fields'])) {
      $data['titles'] = $this->renderTitles($data['fields']);
    }
    if (Service::request()->_('action') === 'CSV') {
      return $this->giveCsvFile(['titles' => $data['fields'], 'rows' => $data['rows']]);
    }
    return $this->render('reports/' . ($data['template'] ?? 'default'), ['data' => $data])
      . $this->getAdditionalTablesAsString($data);
  }

  private function getAdditionalTablesAsString($data): string
  {
    $additionalTables = [];
    if (isset($data['additionalTables'])) {
      foreach ($data['additionalTables'] as $keyTable => $table) {
        if(is_array($table) && isset($table['fields'])) {
          $table['titles'] = $this->renderTitles($table['fields']);
          $table = '<br><div class="periods">' . $this->render('table', ['data' => $table]) . '</div>';
        }
        $additionalTables[] = $table;
      }
    }

    return '<div style="width: 1200px;margin: 0 auto 20px">' . implode('', $additionalTables) . '</div>';
  }
  
  public function setViewKey($key = 'index')
  {
    if(Service::structure()->isPageKey('report_' . $key)) {
      $key = 'report_' . $key;
    }
    parent::setViewKey($key);
  }
  
  /** Пример массива
   * $titles = array(
   * 'title_row' => 2,
   * 'fields'    => array(
   * 'title'           => array('row' => 2, 'col' => 1, 'title' => lang('Customer Login', 'reports'), 'parent' => null,'defaultValue' => 0),
   * 'mode'            => array('row' => 2, 'col' => 1, 'title' => lang('Guest_player'), 'parent' => null),
   * 'encash'          => array('row' => 2, 'col' => 1, 'title' => lang('Payment method'), 'parent' => null),
   * 'area_type'       => array('row' => 1, 'col' => 1, 'title' => lang('Type'), 'parent' => lang('Place')),
   * 'area_sport'      => array('row' => 1, 'col' => 1, 'title' => lang('Sport'), 'parent' => lang('Place')),
   * 'area'            => array('row' => 1, 'col' => 1, 'title' => lang('Place'), 'parent' => lang('Place')),
   * 'date'            => array('row' => 2, 'col' => 1, 'title' => lang('Date'), 'parent' => null),
   * 'time'            => array('row' => 2, 'col' => 1, 'title' => lang('Time period'), 'parent' => null),
   * 'client'          => array('row' => 2, 'col' => 1, 'title' => lang('Client'), 'parent' => null),
   * 'abo'             => array('row' => 2, 'col' => 1, 'title' => lang('Abo'), 'parent' => null),
   * 'club_state'      => array('row' => 2, 'col' => 1, 'title' => lang('Member status'), 'parent' => null),
   * 'friends'         => array('row' => 2, 'col' => 1, 'title' => lang('Guest'), 'parent' => null),
   * 'price'           => array('row' => 1, 'col' => 1, 'title' => lang('Place'), 'parent' => lang('Costs', 'reports')),
   * 'light_price'     => array('row' => 1, 'col' => 1, 'title' => lang('Light'), 'parent' => lang('Costs', 'reports')),
   * 'heating_price'   => array('row' => 1, 'col' => 1, 'title' => lang('Heating'), 'parent' => lang('Costs', 'reports')),
   * 'comment'         => array('row' => 2, 'col' => 1, 'title' => lang('Comment field', 'reports'), 'parent' => null),
   * 'sp_code'         => array('row' => 1, 'col' => 1, 'title' => lang('Code'), 'parent' => lang('Special price')),
   * 'sp_rate'         => array('row' => 1, 'col' => 1, 'title' => lang('Price'), 'parent' => lang('Special price')),
   * 'lt_code'         => array('row' => 1, 'col' => 1, 'title' => lang('Code'), 'parent' => lang('Booking options')),
   * 'lt_rate'         => array('row' => 1, 'col' => 1, 'title' => lang('Set'), 'parent' => lang('Booking options')),
   * 'discount_client' => array('row' => 1, 'col' => 1, 'title' => lang('Client'), 'parent' => lang('Discount')),
   * 'discount_abo'    => array('row' => 1, 'col' => 1, 'title' => lang('Abo'), 'parent' => lang('Discount')),
   * )
   * );
   *
   * @param $titles
   *
   * @return array
   */
  protected function renderTitles($titles): array
  {
    $rows = [];
    for ($row = 1; $row <= $titles['title_row']; $row++) {
      $col = ['title' => null, 'col' => 0];
      foreach ($titles['fields'] as $key => $field) {
        $title          = $field['row'] != $row || $titles['title_row'] == 1 ? $field['title'] : ($field['parent'] ?: '');
        $field['title'] = $title;
        if (($col['col'] > 1 && $col['title'] != $field['title'])) {
          $rows[$row][] = ['col' => ($col['col'] > 1 ? $col['col'] : null), 'title' => $col['title']];
          $col          = ['title' => null, 'col' => 0];
        }
        if ($field['row'] == $row) {
          if ($field['parent'] && ($col['title'] == null || $col['title'] == $field['parent'])) {
            $col['col']   += $field['col'];
            $col['title'] = $field['parent'];
            continue;
          }
        }
        $rowspan = ($row != $field['row'] && $field['row'] > 1 ? $field['row'] : null);
        if ($title) {
          $rows[$row][] = ['title' => $title, 'row' => $rowspan];
        }
      }
      if ($col['col'] > 1) {
        $rows[$row][] = ['title' => $col['title'], 'col' => $col['col']];
      }
    }
    
    return $rows;
  }
  
  protected function giveCsvFile($data = []): string
  {
    $out = [];
    if (!empty($data['titles']) && !empty($data['rows'])) {
      for ($row = 1; $row <= $data['titles']['title_row']; $row++) {
        $last = [];
        foreach ($data['titles']['fields'] as $key => $field) {
          $title          = $field['row'] != $row || $data['titles']['title_row'] == 1 ? $field['title'] : ($field['parent'] ?: '');
          $field['title'] = $title;
          
          $title       = !empty($last) && $title == $last['title'] ? '' : $title;
          $out[$row][] = $title;
          $last        = $field;
        }
      }
      (new CSV())->addRowsFromArray(array_merge($out, $data['rows']))->getAsFile('stats_export');
    }
    
    return '';
  }
}