<?php

namespace AC\core\modules\reports\actions\reports;

use AC\app\services\DataService;
use AC\core\modules\reports\helpers\LayoutReportHelper;
use AC\core\system\helpers\CalendarHelper;
use Service;

class PaymentPayOnlineReport extends ReservationReport
{
  protected string $page_key = 'report_payment_pay_online';
  
  protected function rows(): array
  {
    $rows = [];
    $this->date($rows, lang('From'),
      [
        'year'  => ['name' => 'start_year'],
        'month' => ['name' => 'start_month'],
        'day'   => ['name' => 'start_day'],
      ],
    );
    $this->date($rows, lang('Until'),
      [
        'year'  => ['name' => 'end_year'],
        'month' => ['name' => 'end_month'],
        'day'   => ['name' => 'end_day'],
      ],
    );
    $this->sportsByTypes($rows, lang('Place'));
    
    return $rows;
  }
  
  protected function getRenderDataForView(array &$data): void
  {
    parent::getRenderDataForView($data);
    $rows = $data['rows'];
    $data['rows'] = [];
    foreach ($rows as $dates) {
      foreach ($dates as $row) {
        $data['rows'][] = $row;
      }
    }
    if($data['type_sport']) {
      $data['title'] .= '<br>' . DataService::sportsByType()[$data['type_sport']]->title_site_url;
    }
    $this->getFieldsAsTitle($data);
  }
  
  private function getFieldsAsTitle(array &$data): void
  {
    $data['fields'] = [
      'title_row' => 2,
      'fields'    => [
        'ordered_date' => ['row' => 1, 'col' => 1, 'title' => lang('Date'), 'parent' => lang('Booking date', 'reports')],
        'ordered_time' => ['row' => 1, 'col' => 1, 'title' => lang('Time'), 'parent' => lang('Booking date', 'reports')],
        'pay_state'    => ['row' => 1, 'col' => 1, 'title' => lang('Pay status', 'reports'), 'parent' => lang('Booking date', 'reports')],
        'reference'    => ['row' => 1, 'col' => 1, 'title' => lang('Pay reference', 'reports'), 'parent' => lang('Booking date', 'reports')],
        'surname'      => ['row' => 1, 'col' => 1, 'title' => lang('Family name'), 'parent' => lang('Client')],
        'name'         => ['row' => 1, 'col' => 1, 'title' => lang('First name'), 'parent' => lang('Client')],
        'email'        => ['row' => 1, 'col' => 1, 'title' => lang('E-mail'), 'parent' => lang('Client')],
        'registered'   => ['row' => 1, 'col' => 1, 'title' => lang('Registered on'), 'parent' => lang('Client')],
        'area_type'    => ['row' => 1, 'col' => 1, 'title' => lang('Type'), 'parent' => lang('Reservations', 'reports')],
        'area'         => ['row' => 1, 'col' => 1, 'title' => lang('Place'), 'parent' => lang('Reservations', 'reports')],
        'date'         => ['row' => 1, 'col' => 1, 'title' => lang('Date'), 'parent' => lang('Reservations', 'reports')],
        'time'         => ['row' => 1, 'col' => 1, 'title' => lang('Time period'), 'parent' => lang('Reservations', 'reports')],
        'pay_one_type' => ['row' => 1, 'col' => 1, 'title' => lang('Payment methods'), 'parent' => lang('Reservations', 'reports')],
        'gh_number'    => ['row' => 1, 'col' => 1, 'title' => lang('invoice number', 'reports'), 'parent' => lang('Reservations', 'reports')],
        'instruction'  => ['row' => 1, 'col' => 1, 'title' => lang('Notice', 'reports'), 'parent' => lang('Reservations', 'reports')],
        'price'        => ['row' => 1, 'col' => 1, 'title' => lang('Price') . ' ' . CURR_VALUTE, 'parent' => lang('Reservations', 'reports')],
      ],
      'useNumber' => true,
    ];
  }
  
  protected function getDataBase(?string $date_start = null, ?string $date_finish = null, $type_sport = null, $eventMode = 0): array
  {
    $dateFields = $this->getDateFields();
    if (!$date_start) {
      $date_start = implode('-', [
        Service::request()->_($dateFields['start']['year']['name']),
        Service::request()->_($dateFields['start']['month']['name']),
        Service::request()->_($dateFields['start']['day']['name'], '01'),
      ]);
    }
    if (!$date_finish) {
      $date_finish = implode('-', [
        Service::request()->_($dateFields['end']['year']['name']),
        Service::request()->_($dateFields['end']['month']['name']),
        Service::request()->_($dateFields['end']['day']['name'], CalendarHelper::getDaysInMonthByDate($date_start)),
      ]);
    }
    if (!$type_sport) {
      $type_sport = Service::request()->_('type_sport', null);
    }
    $data = $this->getEngine()->getPayOneReports($date_start, $date_finish, $type_sport, $eventMode);
    return [
      'date_start'  => $date_start,
      'date_finish' => $date_finish,
      'type_sport'  => $type_sport,
      'dateTitle'   => $this->getDateTitle($date_start, $date_finish),
      'rows'        => $data,
      'template'    => $this->getTemplate(),
    ];
  }
  
  protected function actions(): string
  {
    return parent::actions() . LayoutReportHelper::buttonCSV();
  }
}