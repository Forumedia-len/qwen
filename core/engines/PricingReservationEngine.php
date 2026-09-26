<?php

namespace AC\core\engines;

use AC\core\modules\holidays\entities\enums\HolidayScheduleType;
use AC\core\modules\holidays\helpers\HolidayHelper;
use Service;
use InvalidArgumentException;

class PricingReservationEngine
{
  /** Код ошибки: нет тарифа на период (checkAreaDateTimeAvailable) */
  public const ERROR_NO_PERIOD_PRICE = 7;

  /**
   * Request-local кеш тарифов слота.
   * Ключ: areaId:clientId:unixTime; значение — [price, extra_price] или null.
   *
   * @var array<string, array{0: mixed, 1: mixed}|null>
   */
  private array $periodPriceCache = [];

  /** Открытый корт с оплатой «игрок за игроков» — тариф в areas_prices не требуется */
  public function isOpenPlayerPricing($area_id): bool
  {
    $this->engines()->areas->getAreaData($area_id, $area_row);

    return config('OpenType')->isPricingSystem('price_for_player', (int)$area_row['type_id'], (int)$area_row['sport_id'], (int)$area_id);
  }

  /**
   * Есть ли тариф на слот (открытые корты «игрок за игроков» — без строки в areas_prices).
   *
   * @param int|null $error_code при отсутствии тарифа — ERROR_NO_PERIOD_PRICE
   */
  public function hasPeriodPrice(
    $area_id,
    $client_id,
    $mysql_date,
    $mysql_time,
    &$error_code = null
  ): bool {
    if ($this->isOpenPlayerPricing($area_id)) {
      return true;
    }

    if ($this->findPeriodPrice($area_id, $client_id, $mysql_date, $mysql_time) !== null) {
      return true;
    }

    if (func_num_args() >= 5) {
      $error_code = self::ERROR_NO_PERIOD_PRICE;
    }

    return false;
  }

  /**
   * Цена и наценка за слот (праздник → воскресенье при useHolidayAsSanday).
   * Наличие тарифа не проверяется — вызывать после hasPeriodPrice или checkAreaDateTimeAvailable.
   */
  public function getPeriodPrice(
    $area_id,
    $client_id,
    $mysql_date,
    $mysql_time,
    &$price,
    &$extra_price
  ): bool {
    if ($this->isOpenPlayerPricing($area_id)) {
      $price = $extra_price = '0.00';
      return true;
    }

    if ($prices = $this->findPeriodPrice($area_id, $client_id, $mysql_date, $mysql_time)) {
      [$price, $extra_price] = $prices;
      return true;
    }

    $price = $extra_price = null;
    return false;
  }

  /**
   * День недели для расчёта наценок (праздник → воскресенье при useHolidayAsSanday).
   */
  public function getPriceWeekday($mysql_date, $mysql_time): int
  {
    return $this->resolvePriceWeekdayAndUnix($mysql_date, $mysql_time)[0];
  }

  /**
   * Сбрасывает request-local кеш тарифов (после изменения цен/праздников в том же запросе).
   */
  public function clearPeriodPriceCache(): void
  {
    $this->periodPriceCache = [];
  }

  /**
   * @return array{0: int, 1: int} [weekday, unixtime]
   */
  protected function resolvePriceWeekdayAndUnix($mysql_date, $mysql_time, ?bool $useSundayPrices = null): array
  {
    $unix = strtotime($mysql_date . ' ' . $mysql_time);
    if ($unix === false) {
      throw new InvalidArgumentException('Invalid reservation date or time.');
    }

    return [
      HolidayHelper::resolveWeekday($unix, HolidayScheduleType::Prices, $useSundayPrices),
      $unix,
    ];
  }

  /**
   * Один lookup тарифа на слот в рамках запроса: holiday-resolve + SQL только при промахе кеша.
   *
   * @return array{0: mixed, 1: mixed}|null
   */
  protected function findPeriodPrice($areaId, $clientId, $mysqlDate, $mysqlTime): ?array
  {
    $unix = strtotime($mysqlDate . ' ' . $mysqlTime);
    if ($unix === false) {
      throw new InvalidArgumentException('Invalid reservation date or time.');
    }

    $key = $this->buildPeriodPriceCacheKey($areaId, $clientId, $unix);
    if (array_key_exists($key, $this->periodPriceCache)) {
      return $this->periodPriceCache[$key];
    }

    [$weekday, $resolvedUnix] = $this->resolvePriceWeekdayAndUnix($mysqlDate, $mysqlTime);

    return $this->periodPriceCache[$key] = $this->loadPeriodPriceForClient(
      $clientId,
      $areaId,
      $weekday,
      $resolvedUnix
    );
  }

  /**
   * @return array{0: mixed, 1: mixed}|null
   */
  protected function loadPeriodPriceForClient($clientId, $areaId, int $weekday, int $unixTime): ?array
  {
    return $this->engines()->areas->getPricePeriodForClient($clientId, $areaId, $weekday, $unixTime);
  }

  /**
   * null / 0 / 'guest' дают один ключ — SQL для гостя одинаковый.
   */
  private function buildPeriodPriceCacheKey($areaId, $clientId, int $unixTime): string
  {
    $clientKey = ($clientId && $clientId !== 'guest') ? (int)$clientId : 0;

    return (int)$areaId . ':' . $clientKey . ':' . $unixTime;
  }

  private function engines(): Engines
  {
    return Service::engines();
  }
}
