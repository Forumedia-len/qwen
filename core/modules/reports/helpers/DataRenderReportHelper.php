<?php

namespace AC\core\modules\reports\helpers;

use AC\app\entities\enums\Encash;
use AC\app\entities\enums\EventMode;
use AC\app\services\DataService;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\TimeHelper;

class DataRenderReportHelper
{
  public static function getEventTitleData(array $row): string
  {
    $stocks     = DataService::stocks();
    $specPrices = DataService::specPrices();

    $eventTitle = [$row['area']->getTitle()];

    if (!empty($row['stocks'])) {
      $eventTitle[] = '<strong>[</strong>' .
        implode('|', array_map(function ($stockId) use ($stocks) {
          $dto = $stocks[$stockId] ?? null;
          return $dto->code;
        }, $row['stocks'])) . '<strong>]</strong>';
    }
    if (!empty($row['specPrices'])) {
      $eventTitle[] = '<strong>[' .
        implode('|', array_map(function ($specPriceId) use ($specPrices) {
          $dto = $specPrices[$specPriceId] ?? null;
          return $dto->code;
        }, $row['specPrices'])) . ']</strong>';
    }
    if ($row['encash'] == Encash::PrivateAccount) {
      $eventTitle[] = '<strong>' . $row['encash']->shortLabel() . '</strong>';
    }
    if ($row['encash'] == Encash::PayOnline) {
      $raw      = strtolower(trim((string)($row['paypal_status'] ?? '')));
      $provider = '';
      if ($raw !== '') {
        if (str_starts_with($raw, 'payone')) {
          $provider = 'payone';
        } elseif (str_starts_with($raw, 'paypal')) {
          $provider = 'paypal';
        }
      }
      if ($provider === '') {
        // Старые данные могли не фиксировать провайдера в БД.
        $provider = config('payment')->usePayonePayment() ? 'payone' : 'paypal';
      }
      $eventTitle[] = $provider === 'payone'
        ? '<img src="' . cdn_url(paths()->getAssetsDir('images/payone_small.png', 'common')) . '" alt="Payone" style="margin-bottom:-3px;"/>'
        : '<img src="' . cdn_url(paths()->getAssetsDir('images/paypal_small.png', 'common')) . '" alt="PayPal" style="margin-bottom:-3px;"/>';
    }
    if (!empty($row['comment'])) {
      $eventTitle[] = '<br/>[' . $row['comment'] . ']';
    }

    return implode(' ', $eventTitle);
  }

  public static function getOptionalData(array $row): string
  {
    $result = '';
    if ($row['typeEvent'] == EventMode::Reservation && !empty($row['street_friends'])) {
      $friends = [];
      foreach ($row['street_friends'] as $friend) {
        $friends[] = '<span style="white-space:nowrap">' . $friend['name'] . ' ('
          . (DataService::clubStateModel()::getItemById($friend['club_state'], 'short')) . ')</span>';
      }
      $result = implode(', ', $friends);
    }
    return $result;
  }

  public static function getPricesData(array $row): string
  {
    $status           = $row['typeEvent'] == EventMode::Ticket ? null : (bool)$row['payment_status'];
    $webIoActiveTypes = DataService::webIoActiveTypes();
    $prices           = [self::setPaymentStatusColor($row['prices']['price'], $status)];
    foreach (array_keys($webIoActiveTypes) as $type) {
      $prices[] = '<span style="color:blue"> ' . $webIoActiveTypes[$type]->shortLabel() . ':</span >'
        . self::setPaymentStatusColor($row['prices'][$type . '_price'], $status);
    }
    if ($row['typeEvent'] == EventMode::Ticket) {
      $prices[] = '<strong>' . lang('Abo') . '</strong>';
    }

    return implode(' ', $prices);
  }

  public static function setPaymentStatusColor(float $price, $status = null): string
  {
    $color = match ($status) {
      true    => 'green',
      false   => 'red',
      default => 'black',
    };
    return '<span style="color: ' . $color . '">' . NumberHelper::valute($price) . '</span>';
  }

  public static function getDateData(array $row): string
  {
    return $row['date'] . ' ' . $row['time'] . ' (' . TimeHelper::convertMinutes2MySQLTime($row['area']->period * $row['count_periods']) . ')';
  }
}