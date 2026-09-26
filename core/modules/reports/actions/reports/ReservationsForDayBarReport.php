<?php

namespace AC\core\modules\reports\actions\reports;


use AC\app\entities\enums\EventMode;
use AC\app\services\DataService;
use AC\core\modules\reports\actions\outputRender\TwoColumnRender;
use AC\core\modules\reports\entities\enums\ModeClientByPayment;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\TimeHelper;
use Service;

class ReservationsForDayBarReport extends ReservationsForDayReport
{
  protected string $page_key = 'report_reservations_for_day_bar';

  protected function rows(): array
  {
    $rows = [];
    $this->date($rows, lang('Date'), ['year', 'month', 'day']);
    // от ck со старой версии
    /** Подключаем вывод по сменам используем на сk -
     * сейчас там файл перезаписывает что есть в ядре (закомментировано в ядре)
     */
    $this->addRow('', view()->render('shift'), $rows);

    return $rows;
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
      $eventMode = EventMode::from(Service::request()->_('eventMode', 1));
    }
    $interval = Service::request()->_('interval');
    if ($interval && $interval !== 'all') {
      // Рассматриваем интервал в формате 08:00|14:00 или 08:00|
      [$startTime, $finishTime] = array_pad(explode('|', $interval, 2), 2, '23:59');
      $date_start  = date('Y-m-d', strtotime($date_start)) . ' ' . $startTime . ':00';
      $date_finish = date('Y-m-d', strtotime($date_finish)) . ' ' . date('H:i:s', strtotime($finishTime . ' - 1 second'));
    }
    if ($eventMode == EventMode::All || $eventMode == EventMode::Reservation) {
      $this->getEngine()->getReservations($date_start, $date_finish, $type_sport, $data);
    }
//    if ($eventMode == EventMode::All || $eventMode == EventMode::Ticket) {
//      $this->getEngine()->getSeasonTickets($date_start, $date_finish, $type_sport, $data);
//    }
    return [
      'date_start'  => $date_start,
      'date_finish' => $date_finish,
      'type_sport'  => $type_sport,
      'interval'    => $startTime && $finishTime ? $startTime . ' - ' . $finishTime : '',
      'eventMode'   => $eventMode,
      'dateTitle'   => $this->getDateTitle($date_start, $date_finish),
      'rows'        => $data,
      'template'    => $this->getTemplate(),
    ];
  }

  protected function getRenderDataForView(array &$data): void
  {
    parent::getRenderDataForView($data);
    $data['title'] .= '<br>' . lang('Cash report', 'reports') . ' - ' . $data['dateTitle'] . ' ' . $data['interval'];
    // несделано вывод магазина по сменам SHOW_ADMIN_BAR
    // общая таблица
    $this->generateGeneral($data);
//    Debug()::dvDD($data);
  }

  private function generateGeneral(&$data)
  {
    $fields = ['title' => ['row' => 1, 'col' => 1, 'title' => lang('Title'), 'parent' => null, 'style' => 'text-align: left;']];
    if (defined('SHOW_ADMIN_BAR') && SHOW_ADMIN_BAR) {
      $fields['shop'] = ['row' => 1, 'col' => 1, 'title' => lang('Cash report', 'reports'), 'parent' => null, 'style' => 'white-space: nowrap;'];
    }
    foreach (DataService::sportsByType() as $keySportType => $sportType) {
      $fields[$keySportType] = ['row' => 1, 'col' => 1, 'title' => $sportType->title_site_url, 'parent' => null, 'style' => 'white-space: nowrap;width: max-content;'];
    }
    $fields['total']                               = ['row' => 1, 'col' => 1, 'title' => lang('All'), 'parent' => null, 'style' => 'text-align: right;background-color: #bbff8f;white-space: nowrap;'];
    $data['additionalTables']['general']['fields'] = [
      'title_row' => 1,
      'fields'    => $fields,
    ];

    foreach (ModeClientByPayment::withdrawalOrder() as $paymentType) {
      if($paymentType->usePayment()) {
        $row = ['title' => $paymentType->titleReport()];
        $sum = 0;
        if(defined('SHOW_ADMIN_BAR') && SHOW_ADMIN_BAR) {
          $row['shop'] = 0;
          $sum += 0;
        }
        foreach (DataService::sportsByType() as $keySportType => $sportType) {
          $row[$keySportType] = NumberHelper::valute($data['sumPrices']['encash'][$keySportType][$paymentType->value]['full'] ?? 0);
          $sum += $data['sumPrices']['encash'][$keySportType][$paymentType->value]['full'] ?? 0;
        }
        $row['total'] = NumberHelper::valute($sum);
        $data['additionalTables']['general']['rows'][$paymentType->value] = $row;
      }
    }
    $data['additionalTables'][] = "<div id='bottom'><p><b>" . strtoupper(lang('Total amount', 'reports')) . ": " . NumberHelper::valute($data['sumPrices']['full']) . "</b></p></div>";
  }
}