<?php

namespace AC\core\modules\reports\actions\reports;

use AC\app\entities\enums\EventMode;
use AC\app\entities\enums\ModeClient;
use AC\app\services\DataService;
use AC\core\modules\discounts\entities\enums\TypeDiscount;
use AC\core\modules\reports\entities\enums\ModeClientByPayment;
use AC\core\modules\reports\helpers\DataRenderReportHelper;
use AC\core\modules\reports\helpers\LayoutReportHelper;
use AC\core\modules\webIo\entities\dto\WebIoTypeSateDto;
use AC\core\system\helpers\NumberHelper;
use Service;

class ReservationsExportReport extends ReservationReport
{
  protected string $page_key = 'report_reservations_export';
  
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
    $this->eventMode($rows, lang('Type'));
    
    return $rows;
  }
  
  protected function actions(): string
  {
    return parent::actions() . LayoutReportHelper::buttonCSV();
  }
  
  protected function getRenderDataForView(array &$data): void
  {
    parent::getRenderDataForView($data);
    $hasCsv = Service::request()->_get('action') === 'CSV';
    $rows   = $data['rows'];
    $data['rows'] = [];
    $sportsByType   = DataService::sportsByType();
    $stocksData     = DataService::stocks();
    $specPricesData = DataService::specPrices();
    
    foreach ($rows as $row) {
      $keyTypeSport  = $row['area']->typeSportAsString();
      $keyModeEncash = $row['client_mode'] . '_' . $row['encash']->value;
      $status        = $row['typeEvent'] == EventMode::Ticket ? null : (bool)$row['payment_status'];
      $stocks        = $specPrices = $friends = [];
      if (!empty($row['stocks'])) {
        $stocks['codes'] = implode(($hasCsv ? ' | ' : '<br>'), array_map(function ($stockId) use ($stocksData) {
          $dto = $stocksData[$stockId] ?? null;
          return $dto->code;
        }, $row['stocks']));
        $stocks['rates'] = implode(($hasCsv ? ' | ' : '<br>'), array_map(function ($stockId) use ($stocksData) {
          $dto = $stocksData[$stockId] ?? null;
          return $dto->amount();
        }, $row['stocks']));
      }
      if (!empty($row['specPrices'])) {
        $specPrices['codes'] = implode(($hasCsv ? ' | ' : '<br>'), array_map(function ($specPricesId) use ($specPricesData) {
          $dto = $specPricesData[$specPricesId] ?? null;
          return $dto->code;
        }, $row['specPrices']));
        $specPrices['rates'] = implode(($hasCsv ? ' | ' : '<br>'), array_map(function ($specPricesId) use ($specPricesData) {
          $dto = $specPricesData[$specPricesId] ?? null;
          return $dto->amount();
        }, $row['specPrices']));
      }
      if ($row['typeEvent'] == EventMode::Reservation && !empty($row['street_friends'])) {
        foreach ($row['street_friends'] as $friend) {
          $friends[] = ($hasCsv ? '' : '<span style="white-space:nowrap">') . $friend['name'] . ' ('
            . (DataService::clubStateModel()::getItemById($friend['club_state'], 'short')) . ')' . ($hasCsv ? '' : '</span>');
        }
      }
      
      $_row = [
        'title'      => ModeClientByPayment::from($keyModeEncash)->titleReport(),
        'mode'       => ModeClient::from($row['client_mode'])->shortLabelByGuest(),
        'encash'     => $row['encash']->shortLabel(),
        'area_type'  => $sportsByType[$row['area']->typeSportAsString()]->type_title,
        'area_sport' => $sportsByType[$row['area']->typeSportAsString()]->sport_title,
        'area'       => $row['area']->title,
        'date'       => date('d.m.Y', strtotime($row['date'])),
        'time'       => $row['time'],
        'client'     => $row['client_name'],
        'abo'        => $row['typeEvent'] == EventMode::Ticket ? lang('short_yes') : lang('short_not'),
        'club_state' => $row['club_state'],
        'friends'    => implode(', ', $friends ?? []),
        'price'      => !$hasCsv
          ? DataRenderReportHelper::setPaymentStatusColor($row['prices']['price'], $status)
          : NumberHelper::valute($row['prices']['price']),
      ];
      /** @var WebIoTypeSateDto $WebIoType */
      foreach (DataService::webIoActiveTypes() as $webIoType) {
        $_row[$webIoType->alias . '_price'] = NumberHelper::valute($row['prices'][$webIoType->alias . '_price']);
      }
      
      $_row = array_merge($_row,
        [
          'comment'         => $row['comment'],
          'sp_code'         => $specPrices['codes'] ?? '',
          'sp_rate'         => $specPrices['rates'] ?? '',
          'lt_code'         => $stocks['codes'] ?? '',
          'lt_rate'         => $stocks['rates'] ?? '',
          'discount_client' => $row['client_discount']?->label($row['typeEvent'] == EventMode::Ticket ? TypeDiscount::Abo : TypeDiscount::Client),
          'discount_abo'    => $row['ticket_discount']?->label($row['typeEvent'] == EventMode::Ticket ? TypeDiscount::Abo : TypeDiscount::Client),
          'nds'             => $row['client_nds']->label(),
        ]);
      
      $data['rows'][$keyModeEncash . '|' . $row['date'] . '|' . $row['time'] . '|' . $keyTypeSport . '|' . $row['area']->id] = $_row;
    }
    ksort($data['rows']);
    
    $this->getFieldsAsTitle($data);
  }
  
  private function getFieldsAsTitle(array &$data): void
  {
    $webIoTypes = DataService::webIoActiveTypes();
    $fields = [
      'title_row' => 2,
      'fields'    => [
        'title'      => ['row' => 2, 'col' => 1, 'title' => lang('Customer Login', 'reports'), 'parent' => null],
        'mode'       => ['row' => 2, 'col' => 1, 'title' => lang('Guest_player'), 'parent' => null],
        'encash'     => ['row' => 2, 'col' => 1, 'title' => lang('Payment method'), 'parent' => null],
        'area_type'  => ['row' => 1, 'col' => 1, 'title' => lang('Type'), 'parent' => lang('Place')],
        'area_sport' => ['row' => 1, 'col' => 1, 'title' => lang('Sport'), 'parent' => lang('Place')],
        'area'       => ['row' => 1, 'col' => 1, 'title' => lang('Place'), 'parent' => lang('Place')],
        'date'       => ['row' => 2, 'col' => 1, 'title' => lang('Date'), 'parent' => null],
        'time'       => ['row' => 2, 'col' => 1, 'title' => lang('Time period'), 'parent' => null],
        'client'     => ['row' => 2, 'col' => 1, 'title' => lang('Client'), 'parent' => null],
        'abo'        => ['row' => 2, 'col' => 1, 'title' => lang('Abo'), 'parent' => null],
        'club_state' => ['row' => 2, 'col' => 1, 'title' => lang('Member status'), 'parent' => null],
        'friends'    => ['row' => 2, 'col' => 1, 'title' => lang('Guest'), 'parent' => null],
        'price'      => count($webIoTypes)
          ? ['row' => 1, 'col' => 1, 'title' => lang('Place'), 'parent' => lang('Costs', 'reports')]
          : ['row' => 2, 'col' => 1, 'title' => lang('Costs', 'reports'), 'parent' => null],
      ]
    ];
    /** @var WebIoTypeSateDto $WebIoType */
    foreach ($webIoTypes as $webIoType) {
      $fields['fields'][$webIoType->alias . '_price'] = ['row' => 1, 'col' => 1, 'title' => $webIoType->title, 'parent' => lang('Costs', 'reports')];
    }
    $fields['fields'] = array_merge($fields['fields'],
      [
        'comment'         => ['row' => 2, 'col' => 1, 'title' => lang('Comment field', 'reports'), 'parent' => null],
        'sp_code'         => ['row' => 1, 'col' => 1, 'title' => lang('Code'), 'parent' => lang('Special price')],
        'sp_rate'         => ['row' => 1, 'col' => 1, 'title' => lang('Price'), 'parent' => lang('Special price')],
        'lt_code'         => ['row' => 1, 'col' => 1, 'title' => lang('Code'), 'parent' => lang('Booking options')],
        'lt_rate'         => ['row' => 1, 'col' => 1, 'title' => lang('Set'), 'parent' => lang('Booking options')],
        'discount_client' => ['row' => 1, 'col' => 1, 'title' => lang('Client'), 'parent' => lang('Discount')],
        'discount_abo'    => ['row' => 1, 'col' => 1, 'title' => lang('Abo'), 'parent' => lang('Discount')],
        'nds'             => ['row' => 2, 'col' => 1, 'title' => lang('NDS'), 'parent' => null],
      ]
    );
    
    $data['fields'] = $fields;
  }
}