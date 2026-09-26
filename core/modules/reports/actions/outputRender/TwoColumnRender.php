<?php

namespace AC\core\modules\reports\actions\outputRender;

use AC\app\entities\enums\EventMode;
use AC\app\services\DataService;
use AC\core\modules\reports\entities\enums\ModeClientByPayment;
use AC\core\modules\reports\helpers\DataRenderReportHelper;
use AC\core\system\helpers\ArrayHelper;

class TwoColumnRender
{
  public function renderOutputs(array &$data): array
  {
    $rows = $data['rows'];
    $data['rows'] = [];
    $gameTimeClients = $clientsByColumns = [];

    $sumPrices = [
      'full'            => 0,
      'full_type_sport' => [],
      'encash'          => [],
      'client'          => [],
    ];

    foreach ($rows as $row) {
      $keyTypeSport  = $row['area']->typeSportAsString();
      $keyClient     = $row['client_name'] . '|' . $row['client_id'] . '|' . $row['club_state'];
      $keyModeEncash = $row['client_mode'] . '_' . $row['encash']->value;

      // подсчет рабочих периодов для клиента
      $this->calculatingGameTime($row, $gameTimeClients);
      // подсчет кол-ва бронирований для формирования 2-х колонок
      $this->countingNumberOfGames($row, $clientsByColumns);
      // подсчет суммы
      if ($row['payment_status'] || config('reports')->takeIntoAccountFailedPayment()) {
        if (ModeClientByPayment::tryFrom($keyModeEncash)?->usePayment()) {
          $this->setSumPricesData($row, $sumPrices);
        }
      }

      // формирование данных для вывода
      $data['rows'][$keyTypeSport][$keyModeEncash][$keyClient][$row['area']->areaId . '|' . $row['date'] . '|' . $row['time']] = [
        'eventTitle' => DataRenderReportHelper::getEventTitleData($row),
        'date_time'  => DataRenderReportHelper::getDateData($row),
        'prices'     => DataRenderReportHelper::getPricesData($row),
        'optional'   => DataRenderReportHelper::getOptionalData($row),
      ];
    }
    $this->renderClientsByTowColumn($clientsByColumns);


    $data['sumPrices']        = $sumPrices;
    $data['gameTimeClients']  = $gameTimeClients;
    $data['clientsByColumns'] = $clientsByColumns;

    ksort($data['clientsByColumns']);

    return $data;
  }

  protected function setSumPricesData(array $row, array &$sum): void
  {
    $webIoActiveTypes = array_keys(DataService::webIoActiveTypes());
    // подсчет суммы
    $keyTypeSport  = $row['area']->typeSportAsString();
    $keyClient     = $row['client_name'] . '|' . $row['client_id'] . '|' . $row['club_state'];
    $keyModeEncash = $row['client_mode'] . '_' . $row['encash']->value;

    $price       = $row['prices']['price'];
    $sum['full'] += $price;

    $sum['full_type_sport'][$keyTypeSport]['reservation'] ??= 0;
    $sum['full_type_sport'][$keyTypeSport]['ticket']      ??= 0;
    $sum['full_type_sport'][$keyTypeSport]['full']        ??= 0;
    $sum['full_type_sport'][$keyTypeSport]['full']        += $price;

    $sum['encash'][$keyTypeSport][$keyModeEncash]['reservation'] ??= 0;
    $sum['encash'][$keyTypeSport][$keyModeEncash]['ticket']      ??= 0;
    $sum['encash'][$keyTypeSport][$keyModeEncash]['full']        ??= 0;
    $sum['encash'][$keyTypeSport][$keyModeEncash]['full']        += $price;

    $sum['client'][$keyTypeSport][$keyModeEncash][$keyClient]['full']        ??= 0;
    $sum['client'][$keyTypeSport][$keyModeEncash][$keyClient]['reservation'] ??= 0;
    $sum['client'][$keyTypeSport][$keyModeEncash][$keyClient]['ticket']      ??= 0;
    $sum['client'][$keyTypeSport][$keyModeEncash][$keyClient]['full']        += $price;

    if ($row['typeEvent'] == EventMode::Reservation) {
      $sum['full_type_sport'][$keyTypeSport]['reservation']                    += $price;
      $sum['encash'][$keyTypeSport][$keyModeEncash]['reservation']             += $price;
      $sum['client'][$keyTypeSport][$keyModeEncash][$keyClient]['reservation'] += $price;
    }
    if ($row['typeEvent'] == EventMode::Ticket) {
      $sum['full_type_sport'][$keyTypeSport]['ticket']                    += $price;
      $sum['encash'][$keyTypeSport][$keyModeEncash]['ticket']             += $price;
      $sum['client'][$keyTypeSport][$keyModeEncash][$keyClient]['ticket'] += $price;
    }
    foreach ($webIoActiveTypes as $WebIoType) {
      $price                                                            = $row['prices'][$WebIoType . '_price'] ?? 0;
      $sum['full']                                                      += $price;
      $sum['full_type_sport'][$keyTypeSport]['full']                    += $price;
      $sum['encash'][$keyTypeSport][$keyModeEncash]['full']             += $price;
      $sum['client'][$keyTypeSport][$keyModeEncash][$keyClient]['full'] += $price;

      if ($row['typeEvent'] == EventMode::Reservation) {
        $sum['full_type_sport'][$keyTypeSport]['reservation']                    += $price;
        $sum['encash'][$keyTypeSport][$keyModeEncash]['reservation']             += $price;
        $sum['client'][$keyTypeSport][$keyModeEncash][$keyClient]['reservation'] += $price;
      }
      if ($row['typeEvent'] == EventMode::Ticket) {
        $sum['full_type_sport'][$keyTypeSport]['ticket']                    += $price;
        $sum['encash'][$keyTypeSport][$keyModeEncash]['ticket']             += $price;
        $sum['client'][$keyTypeSport][$keyModeEncash][$keyClient]['ticket'] += $price;
      }
    }
  }

  protected function countingNumberOfGames(array $row, array &$clientReservationsCount): void
  {
    $keyTypeSport  = $row['area']->typeSportAsString();
    $keyClient     = $row['client_name'] . '|' . $row['client_id'] . '|' . $row['club_state'];
    $keyModeEncash = $row['client_mode'] . '_' . $row['encash']->value;

    // подсчет кол-ва бронирований для формирования 2-х колонок
    $clientReservationsCount[$keyTypeSport][$keyModeEncash][$keyClient] ??= 0;
    $clientReservationsCount[$keyTypeSport][$keyModeEncash][$keyClient] += 1;
  }

  protected function calculatingGameTime(array $row, array &$minuteClients): void
  {
    $keyTypeSport  = $row['area']->typeSportAsString();
    $keyClient     = $row['client_name'] . '|' . $row['client_id'] . '|' . $row['club_state'];
    $keyModeEncash = $row['client_mode'] . '_' . $row['encash']->value;

    // подсчет рабочих периодов для клиента
    $minuteClients[$keyTypeSport][$keyModeEncash][$keyClient]['full']        ??= 0;
    $minuteClients[$keyTypeSport][$keyModeEncash][$keyClient]['reservation'] ??= 0;
    $minuteClients[$keyTypeSport][$keyModeEncash][$keyClient]['ticket']      ??= 0;
    $minuteClients[$keyTypeSport][$keyModeEncash][$keyClient]['full']        += $row['count_periods'] * $row['area']->period;
    if ($row['typeEvent'] == EventMode::Reservation) {
      $minuteClients[$keyTypeSport][$keyModeEncash][$keyClient]['reservation'] += $row['count_periods'] * $row['area']->period;
    }
    if ($row['typeEvent'] == EventMode::Ticket) {
      $minuteClients[$keyTypeSport][$keyModeEncash][$keyClient]['ticket'] += $row['count_periods'] * $row['area']->period;
    }
  }

  protected function renderClientsByTowColumn(array &$rows): void
  {
    foreach ($rows as $keyTypeSport => $modeEncash) {
      foreach ($modeEncash as $keyModeEncash => $clients) {
        $rows[$keyTypeSport][$keyModeEncash] = ArrayHelper::greedySortingByValue($clients);
      }
    }
  }
}