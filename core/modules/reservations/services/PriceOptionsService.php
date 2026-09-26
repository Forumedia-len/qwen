<?php

namespace AC\core\modules\reservations\services;

use AC\core\modules\reservations\config\ReservationsConfig;
use AC\core\modules\reservations\entities\contracts\Option;
use AC\core\modules\reservations\entities\dto\PriceOptionFormInputDto;
use AC\core\modules\reservations\entities\dto\PriceOptionsFormBlockDto;
use AC\core\modules\reservations\entities\dto\ReservationAreaDto;
use AC\core\modules\reservations\entities\dto\ReservationClientDto;
use AC\core\modules\reservations\entities\dto\ReservationSpecPriceDto;
use AC\core\modules\reservations\entities\dto\ReservationStockDto;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\DateHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;

class PriceOptionsService
{
  /** Получить Акции доступные клиенту
   *  в зависимости от даты и времени бронирования
   *
   * @param ReservationClientDto $client - модель клиента
   * @param                      $date   - дата (дата бронирования)
   * @param                      $times  - массив со значением времени (промежутки бронирования)
   * @param ReservationAreaDto   $area
   *
   * @return array
   */
  public function getStocks(ReservationClientDto $client, $date, $times, ReservationAreaDto $area): array
  {
    $stocks = [];
    /** @var ReservationStockDto $stock */
    foreach ($this->stocks() as $stock) {
      if ($this->checkUsedOption($stock, $date, $times, $client->stockId, $area->typeId . '_' . $area->sportId, $area->period)) {
        $stocks[$stock->id] = $stock;
      }
    }
    
    return $stocks;
  }
  
  
  /** Получить Спец Цены доступные клиенту
   *  в зависимости от даты и времени бронирования
   *
   * @param ReservationClientDto $client - модель клиента
   * @param                      $date   - дата (дата бронирования)
   * @param                      $times  - массив со значением времени (промежутки бронирования)
   * @param ReservationAreaDto   $area
   *
   * @return array
   */
  public function getSpecPrices(ReservationClientDto $client, $date, $times, ReservationAreaDto $area): array
  {
    $specPrices = [];
    /** @var ReservationSpecPriceDto $specPrice */
    foreach ($this->specPrices() as $specPrice) {
      if ($this->checkUsedOption($specPrice, $date, $times, $client->spriceId, $area->typeId . '_' . $area->sportId, $area->period)) {
        $specPrices[$specPrice->id] = $specPrice;
      }
    }
    
    return $specPrices;
  }
  
  /**
   * Получить данные для опций (спец цены и наценки)
   * @param ReservationClientDto $client - модель клиента
   * @param                      $date   - дата
   * @param                      $times  - время
   * @param ReservationAreaDto   $area   - модель площадки
   * @return array
   */
  public function getPriceOptions(ReservationClientDto $client, $date, $times, ReservationAreaDto $area): array
  {
    /** @var ReservationsConfig $configReservations */
    $configReservations = config('Reservations');
    $weekday            = CalendarHelper::getWeekdayByUnixtime($date);
    $globalUseOptions   = $configReservations->useOptionsPrice($area->type?->alias === 'open');
    $options_price = ['use_options' => false, 'data' => []];
    foreach ([
               'stock'  => $this->getStocks($client, $date, $times, $area),
               'sprice' => $this->getSpecPrices($client, $date, $times, $area)
             ] as $name => $options) {
      $useNullValue = !USE_NEW_STRATEGY_OPTION || !$options_price['use_options'];
      if ($globalUseOptions && match ($name) {
          'stock'  => $configReservations->useStock($area->type?->alias === 'open'),
          'sprice' => $configReservations->useSpecPrice($area->type?->alias === 'open'),
        }) {
        if (count($options) > 0) {
          $options_price['use_options'] = true;
        }
        $priceOption = PriceOptionsFormBlockDto::fromArray([
          'h3'          => lang('show_order_block_options_' . $name . '_h3', 'show_order'),
          'description' => lang('show_order_block_options_' . $name . '_description', 'show_order'),
          'id'          => $name . '_block',
          'alias'       => $name,
          'value'       => (USE_NEW_STRATEGY_OPTION ? $name . '_' : '') . '0',
        ]);
        if ($useNullValue && ($name !== 'stock' || USE_NEW_STRATEGY_OPTION)) {
          $priceOption->priceOptions[] = PriceOptionFormInputDto::fromArray([
            'id'      => $name . '_input_' . '0',
            'checked' => true,
            'title'   => lang('no'),
            'value'   => (USE_NEW_STRATEGY_OPTION ? $name . '_' : '') . '0',
            'alias'   => $name,
            'name'    => (USE_NEW_STRATEGY_OPTION ? 'option' : $name) . '_id',
          ]);
        }
        /** @var Option $option */
        foreach ($options as $option) {
          $inputData       = [];
          $prefixTitleTime = $option->duration
            ? ' (' . lang('valid', 'show_order') . ': '
            . implode(', ', TimeHelper::getTimeByStartFinish(array_keys($option->durations[$weekday]), $area->period)) . ')'
            : '';
          if (!USE_NEW_STRATEGY_OPTION && $name === 'stock') {
            $inputData['classInput']  = 'check';
            $inputData['typeInput']   = 'checkbox';
            $inputData['podlogInput'] = 'podlog2';
          }
          $inputData['id']    = $name . '_input_' . $option->id;
          $inputData['title'] = (!empty($option->code) ? "[{$option->code}] " : '') . "$option->title $prefixTitleTime <strong>({$option->amount()})</strong>";
          $inputData['value'] = (USE_NEW_STRATEGY_OPTION ? $name . '_' : '') . $option->id;
          $inputData['alias'] = $name;
          $inputData['name']  = (USE_NEW_STRATEGY_OPTION ? 'option' : $name) . '_id'
            . (!USE_NEW_STRATEGY_OPTION && $name === 'stock' ? '[]' : '');
          
          $priceOption->priceOptions[] = PriceOptionFormInputDto::fromArray($inputData);
        }
        $options_price['data'][$name] = $priceOption;
      }
    }
    
    return $options_price;
  }
  
  public function checkNegativeValueStocks(array $stocksIds): bool
  {
    $stocks = $this->stocks();
    foreach ($stocksIds as $stockId) {
      if (isset($stocks[$stockId]) && $stocks[$stockId]->rate < 0) {
        return true;
      }
    }
    
    return false;
  }
  
  private function getDurationsStrToTimeByWeekday(&$times, int $period = 30): array
  {
    $durations = [];
    
    foreach ($times as $start => $finish) {
      foreach (TimeHelper::generateArrayTimeInIncrements($start, $finish, $period) as $duration) {
        $times[$duration] = TimeHelper::addMinutes2MySQLTime($duration, $period, true);
        $durations[]      = strtotime($duration);
      }
    }
    ksort($times);
    
    return $durations;
  }
  
  private function checkUsedOption($option, string $date, array $times, array $clientOptionsIds, string $typeSport, $period = 30): bool
  {
    $weekday = CalendarHelper::getWeekdayByUnixtime($date);
    if ($option->forAll || (!empty($clientOptionsIds) && in_array((string)$option->id, $clientOptionsIds, true))) {
      if ((empty($option->typeSport) || $option->typeSport === $typeSport)
        && (!$option->duration || (DateHelper::checkDurationByDate($date, $option->durationStart, $option->durationFinish)
            && isset($option->durations[$weekday])
            && TimeHelper::checkDurationByTime($times, $this->getDurationsStrToTimeByWeekday($option->durations[$weekday], $period)))
        )) {
        return true;
      }
    }
    return false;
  }
  
  private function stocks(): array
  {
    static $stocks;
    if (empty($stocks)) {
      foreach (ModCommHelper::get('stocks', 'stocks/getStocks', [], 'stocks', []) as $stock) {
        $stocks[$stock['stock_id']] = ReservationStockDto::fromArray($stock);
      }
    }
    return $stocks ?? [];
  }
  
  private function specPrices(): array
  {
    static $specPrices;
    if (empty($specPrices)) {
      foreach (ModCommHelper::get('specPrices', 'specPrices/getSpecPrices', [], 'specPrices', []) as $sprice) {
        $specPrices[$sprice['sprice_id']] = ReservationSpecPriceDto::fromArray($sprice);
      }
    }
    return $specPrices ?? [];
  }
  
  public function stock($id): ReservationStockDto
  {
    return $this->stocks()[$id] ?? ReservationStockDto::fromArray([]);
  }
}