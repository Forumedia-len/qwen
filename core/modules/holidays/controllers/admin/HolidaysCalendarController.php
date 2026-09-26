<?php

namespace AC\core\modules\holidays\controllers\admin;

use AC\app\controllers\AdminController;
use AC\core\modules\holidays\engines\HolidaysEngine;
use AC\core\system\helpers\ObjectHelper;
use Service;

class HolidaysCalendarController extends AdminController
{
  public $default_action = 'show';
  public $default_template = 'default';
  protected $useBaseModel = false;

  public function show()
  {
    $this->setViewKey('holidays_calendar');

    return $this->renderCalendar();
  }

  public function updateCalendar()
  {
    $this->setViewKey('holidays_calendar');

    $items  = explode(',', (string)Service::request()->_post('holidays'));
    $items2 = [];
    if (is_array($items) && count($items) > 0) {
      foreach ($items as $i) {
        $items2[] = addslashes($i);
      }
    }

    $this->holidaysEngine()->setAllHolidaysByYear((int)Service::request()->_post('year'), $items2);

    return $this->renderCalendar();
  }

  protected function renderCalendar(): string
  {
    $year = (int)Service::request()->_('year', date('Y'));

    if ($year < (int)date('Y') - 1 || $year > (int)date('Y') + 2) {
      $year = (int)date('Y');
    }

    return $this->render('calendar', [
      'year'                   => $year,
      'yearLinksHtml'          => $this->yearLinksHtml($year),
      'holidaysInSelectedYear' => $this->holidaysEngine()->getHolidaysByYear($year),
      'calendarUrl'            => site_url(Service::structure()->getPageHrefByKey('holidays_calendar')),
      'useUpdate'              => !config('holidays')->useHolidayAsSanday(),
    ]);
  }

  private function yearLinksHtml(int $year): string
  {
    $years   = [];
    $baseUrl = site_url(Service::structure()->getPageHrefByKey('holidays_calendar'));
    for ($i = (int)date('Y') - 1; $i <= (int)date('Y') + 2; $i++) {
      $years[] = ObjectHelper::createObject(['key' => $i, 'title' => $i, 'href' => $baseUrl . '/year/' . $i], true);
    }

    return useLayout()->render('menu/link_menu',
      [
        'menu'        => $years,
        'currentItem' => $year,
      ], 'admin');
  }


  protected function holidaysEngine(): HolidaysEngine
  {
    return getEngine('holidays', false);
  }
}
