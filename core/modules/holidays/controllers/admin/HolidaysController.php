<?php

namespace AC\core\modules\holidays\controllers\admin;

use AC\app\controllers\AdminController;
use AC\core\modules\holidays\engines\HolidaysEngine;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\ObjectHelper;
use DateTimeImmutable;
use Service;

class HolidaysController extends AdminController
{
  public $default_action = 'show';
  public $default_template = 'default';
  protected $useBaseModel = false;


  public function show()
  {
    $this->setViewKey('holidays_table');

    return [$this->renderForm(), $this->renderList()];
  }

  public function insertHoliday()
  {
    $this->setViewKey('holidays_table');
    $sunday_prices = (bool)Service::request()->_post('sunday_prices', 0);
    $sunday_times  = (bool)Service::request()->_post('sunday_times', 0);
    $date_start    = DateTimeImmutable::createFromFormat('!d.m.Y', Service::request()->_post('date_start'));
    $date_finish   = DateTimeImmutable::createFromFormat('!d.m.Y', Service::request()->_post('date_finish'));
    if ($date_start && !$date_start::getLastErrors()) {
      if (!$date_finish || $date_finish::getLastErrors() || $date_start > $date_finish) {
        $date_finish = $date_start;
      }
      if (!$this->isBlockingEventsForHolidays($date_start->format('Y-m-d'), $date_finish->format('Y-m-d'))) {
        $day            = $date_start->setTime(0, 0);
        $finish         = $date_finish->setTime(0, 0);
        $holidaysErrors = [];
        while ($day <= $finish) {
          if (!$this->holidaysEngine()->insertHoliday($day->format('Y-m-d'), $sunday_prices, $sunday_times, $holiday_id)) {
            $holidaysErrors[] = $day->format('Y-m-d');
          }
          $day = $day->modify('+1 day');
        }

        if (!count($holidaysErrors)) {
          $this->view->addMessage(lang('Holiday registered!', 'message_success'), 'success');
        } else {
          foreach ($holidaysErrors as $error) {
            $this->view->addMessage(lang('This holiday is already registered!', 'message_error') . ' ' . $error, 'error');
          }
        }
      }
    } else {
      $this->view->addMessage(lang('Wrong date input!', 'message_error'), 'error');
    }

    return [
      $this->renderForm(
        $date_start?->format('d.m.Y') ?? date('d.m.Y'),
        $date_finish?->format('d.m.Y') ?? date('d.m.Y')), $this->renderList(),
    ];
  }

  private function isBlockingEventsForHolidays(string $dateStart, string $dateFinish): bool
  {
    if (config('holidays')->checkBlockingEventsForHolidays()) {
      $intersectionMessages = Service::engines()->obtainIntersectionWithAllSourcesForIntervalForAllAreas(
        $dateStart,
        $dateFinish,
        '00:00',
        '23:59'
      );
      foreach ($intersectionMessages as $intersectionMessage) {
        $this->view->addMessage($intersectionMessage, 'error');
      }

      if (!empty($intersectionMessages)) {
        return true;
      }
    }

    return false;
  }

  private function isBlockingEventsForSundayTimesToggle(string $date, bool $enableSundayTimes): bool
  {
    $weekdayDay      = CalendarHelper::getWeekdayByUnixtime(strtotime($date));
    $sourceWeekday   = $enableSundayTimes ? $weekdayDay : 6;
    $targetWeekday   = $enableSundayTimes ? 6 : $weekdayDay;
    $intersectionMessages = Service::engines()->getHolidaySundayTimesConflictMessages(
      $date,
      $sourceWeekday,
      $targetWeekday
    );
    foreach ($intersectionMessages as $intersectionMessage) {
      $this->view->addMessage($intersectionMessage, 'error');
    }

    return !empty($intersectionMessages);
  }

  public function removeHoliday()
  {
    $this->setViewKey('holidays_table');
    if (($holidayId = Service::request()->_('holiday_id')) && $this->holidaysEngine()->removeHolidayById($holidayId)) {
      $this->view->addMessage(lang('Holiday deleted!', 'message_success'), 'success');
    }

    return $this->show();
  }

  public function activeSunday()
  {
    if (($sundayType = Service::request()->_('sundayType')) && in_array($sundayType, ['prices', 'times'])
      && ($holidayId = Service::request()->_('holiday_id'))) {
      $state = (bool)Service::request()->_('state');
      if ($sundayType === 'times'
        && config('holidays')->useSundayTimes()
        && config('holidays')->checkBlockingEventsForHolidays()) {
        $holiday = $this->holidaysEngine()->getHolidayById((int)$holidayId);
        if (!empty($holiday['date'])
          && $this->isBlockingEventsForSundayTimesToggle($holiday['date'], $state)) {
          return $this->show();
        }
      }
      if ($this->holidaysEngine()->activeSunday((int)strtolower(trim($holidayId)), strtolower(trim($sundayType)), $state, $sunday)) {
        $message = match ((bool)$sunday) {
          true  => lang('Sunday is activated', 'message_success', ['text' => lang('The holiday is like Sunday', 'holiday')]),
          false => lang('Sunday is deactivated', 'message_success', ['text' => lang('The holiday is like Sunday', 'holiday')]),
        };
        $this->view->addMessage($message, 'success');
        $text    = match ($sundayType) {
          'prices' => lang('Use Sunday Prices', 'holiday'),
          'times'  => lang('Use opening hours on Sundays', 'holiday'),
        };
        $message = match ($state) {
          true  => lang('Sunday is activated', 'message_success', ['text' => $text]),
          false => lang('Sunday is deactivated', 'message_success', ['text' => $text]),
        };
        $this->view->addMessage($message, 'success');
      }
    }

    return $this->show();
  }

  protected function renderForm(?string $date_start = null, ?string $date_finish = null): string
  {
    $this->view->addJsFile('jquery-ui/jquery-ui.min', 'third', true, 'cdn');
    $this->view->addJsFile('jquery-ui/i18n/datepicker-' . config('lang')->getCurrentLang(), 'third', true, 'cdn');
    $this->view->addCssFile('jquery-ui/jquery-ui.min', 'third', true, 'cdn');

    return $this->render('_form', [
      'date_start'       => $date_start ?? date('d.m.Y'),
      'date_finish'      => $date_finish ?? date('d.m.Y'),
      'showSundayColumn' => config('holidays')->useHolidayAsSanday(),
      'actionUrl'        => site_url(Service::structure()->getPageHrefByKey('holidays_table')) . '/insertHoliday',
    ]);
  }

  protected function renderList(): string
  {
    $year        = (int)Service::request()->_('year', date('Y'));
    $page        = (int)Service::request()->_('page', 1);
    $perPage     = 60;
    $pagination  = $this->pagination($year, $perPage, $page);
    $hasHolidays = $this->holidaysEngine()->getHolidaysList($perPage * ($page - 1), $perPage, $holidays, $year);

    return $this->render('list', [
      'holidays'         => $holidays,
      'hasHolidays'      => $hasHolidays,
      'showSundayColumn' => config('holidays')->useHolidayAsSanday(),
      'baseUrl'          => site_url(Service::structure()->getPageHrefByKey('holidays_table')),
      'prefixUrl'        => '/year/' . $year . '/page/' . $page,
      'yearLinksHtml'    => $this->yearLinksHtml($year),
      'pagination'       => $pagination,
      'year'             => $year,
    ]);
  }

  private function yearLinksHtml(?int $year = null): string
  {
    $baseUrl = site_url(Service::structure()->getPageHrefByKey('holidays_table'));
    $years   = [];
    $yearsH  = $this->holidaysEngine()->getYears();
    if (!in_array(date('Y'), $yearsH)) {
      array_unshift($yearsH, date('Y'));
    }
    foreach ($yearsH as $yearI) {
      $years[] = ObjectHelper::createObject(['key' => $yearI, 'title' => $yearI, 'href' => $baseUrl . '/year/' . $yearI], true);
    }
    return useLayout()->render('menu/link_menu',
      [
        'menu'        => $years,
        'currentItem' => $year,
      ], 'admin');
  }

  private function pagination(?int $year, ?int $perPage, ?int &$page): string
  {
    $year    = $year ?? date('Y');
    $perPage = $perPage ?? 60;
    if (empty($page) || $page < 1) {
      $page = 1;
    }

    $count       = $this->holidaysEngine()->getHolidaysCount($year);
    $pages_count = $count > 0 ? (int)ceil($count / $perPage) : 1;
    if ($page > $pages_count) {
      $page = $pages_count;
    }

    return useLayout()->render('pagination',
      [
        'page'        => $page,
        'pages_count' => $pages_count,
        'baseUrl'     => site_url(Service::structure()->getPageHrefByKey('holidays_table')) . '/year/' . $year,
      ], 'admin');
  }

  protected function holidaysEngine(): HolidaysEngine
  {
    return getEngine('holidays', false);
  }
}
