<?php

namespace AC\core\modules\reports\actions\reports;

use AC\app\entities\enums\Encash;
use AC\app\entities\enums\EventMode;
use AC\app\services\DataService;
use AC\core\modules\reports\helpers\LayoutReportHelper;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\DateHelper;
use AC\core\system\helpers\LayoutHelper;
use Service;

abstract class ReservationReport extends Report
{
  protected string $page_key = 'reports_reservations';
  
  public function getData(): array
  {
    $data = [];
    $this->getTitle($data);
    $this->getRenderDataForView($data);
    
    return $data;
  }
  
  protected function getDataBase(?string $date_start = null, ?string $date_finish = null, $type_sport = null, $eventMode = 0): array
  {
    $data       = [];
    $dateFields = $this->getDateFields();
    if (!$date_start) {
      $date_start = implode('-', [
        Service::request()->_($dateFields['start']['year']['name']),
        Service::request()->_($dateFields['start']['month']['name']),
        Service::request()->_($dateFields['start']['day']['name'], '01'),
      ]);
      $date_start = $date_start . ' 00:00:00';
    }
    if (!$date_finish) {
      $date_finish = implode('-', [
        Service::request()->_($dateFields['end']['year']['name']),
        Service::request()->_($dateFields['end']['month']['name']),
        Service::request()->_($dateFields['end']['day']['name'], CalendarHelper::getDaysInMonthByDate($date_start)),
      ]);
      $date_finish = $date_finish . ' 23:59:59';
    }
    if (!$type_sport) {
      $type_sport = Service::request()->_('type_sport', null);
    }
    if (!$eventMode) {
      $eventMode = EventMode::from(Service::request()->_('eventMode', 0));
    }
    if ($eventMode == EventMode::All || $eventMode == EventMode::Reservation) {
      $this->getEngine()->getReservations($date_start, $date_finish, $type_sport, $data);
    }
    if ($eventMode == EventMode::All || $eventMode == EventMode::Ticket) {
      $this->getEngine()->getSeasonTickets($date_start, $date_finish, $type_sport, $data);
    }
    
    return [
      'date_start'  => $date_start,
      'date_finish' => $date_finish,
      'type_sport'  => $type_sport,
      'eventMode'   => $eventMode,
      'dateTitle'   => $this->getDateTitle($date_start, $date_finish),
      'rows'        => $data,
      'template'    => $this->getTemplate(),
    ];
  }
  
  protected function getRenderDataForView(array &$data): void
  {
    $data = array_merge($data, $this->getDataBase());
  }
  
  protected function getTitle(array &$data): void
  {
    $data['title'] = config('app')->getProjectTitle();
  }
  
  protected function sportsByTypes(array &$data, string $title, array $params = []): void
  {
    $values        = [0 => lang('All')];
    $sportsByTypes = DataService::sportsByType();
    foreach ($sportsByTypes as $key => $sportByType) {
      $values[$key] = $sportByType->title_site_url;
    }
    $this->addRow(
      $title,
      LayoutReportHelper::radioTable($params['name'] ?? 'type_sport', $values, $params['current'] ?? 0),
      $data);
  }
  
  protected function eventMode(array &$data, string $title, array $params = []): void
  {
    $values = [
      0 => lang('General', 'reports'),
      1 => lang('Individual lessons only', 'reports'),
      2 => lang('Abos only', 'reports'),
    ];
    $this->addRow(
      $title,
      LayoutReportHelper::radioTable($params['name'] ?? 'eventMode', $values, $params['current'] ?? 0),
      $data);
  }
  
  protected function minYear(): int
  {
    $engine = Service::engines();
    return (int)date('Y', min($engine->getMinDateReservation(), $engine->getMinDateAbo(), strtotime('-4 years')));
  }
  
  protected function addRowYearMonth(&$rows): void
  {
    $values = [
      lang('Year') . ':',
      LayoutHelper::getSelectYears($this->minYear(), DateHelper::addYear(1)),
      lang('Month') . ':',
      LayoutHelper::getSelectMonths(),
    ];
    $value  = '<div style="display: flex; gap: 7px; justify-content: flex-start;align-items: center;padding: 4px 0">
              <span>' . implode('</span><span>', $values) . '</span></div>';
    $this->addRow('', $value, $rows);
  }
  
  protected function getDateFields(): array
  {
    return [
      'start' => [
        'year'  => ['name' => 'start_year'],
        'month' => ['name' => 'start_month'],
        'day'   => ['name' => 'start_day'],
      ],
      'end'   => [
        'year'  => ['name' => 'end_year'],
        'month' => ['name' => 'end_month'],
        'day'   => ['name' => 'end_day'],
      ],
    ];
  }
  
  protected function actionForm(string $url = ''): string
  {
    return site_url(Service::structure()->getPageHrefByKey($this->page_key));
  }
  
  protected function captionTableForm(string $title = ''): string
  {
    return Service::structure()->getPageDataByKey($this->page_key)['title'];
  }
  
  protected function actions(): string
  {
    return LayoutReportHelper::buttonExecute();
  }
  
  protected function getTemplate(): string
  {
    return 'default';
  }
  
  protected function getDateTitle($date_start, $date_finish = null): string
  {
    return date('d/m/Y', strtotime($date_start)) . ' - ' . date('d/m/Y', strtotime($date_finish));
  }
}