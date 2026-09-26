<?php

namespace AC\core\modules\areas\entities\dto;

use AC\app\entities\traits\sets\HasArea;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\JsonHelper;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;

class AreaDto
{
  public int $areaId;
  use HasArea {
    fromArray as traitFromArray;
    toArray as traitToArray;
  }
  
  public ?DateTimeInterface $start;
  public ?DateTimeInterface $finish;
  
  private string  $_prices       = '';
  private ?string $prices;
  private string  $_workingHours = '';
  private ?string $workingHours;
  private string  $_seasons;
  
  public static function fromArray(array $data): self
  {
    $dto         = self::traitFromArray($data);
    $dto->areaId = $data['area_id'];
    $dto->start  = !empty($data['start']) ? DateTimeImmutable::createFromFormat('H:i:s', $data['start']) : null;
    $dto->finish = !empty($data['finish']) ? DateTimeImmutable::createFromFormat('H:i:s', $data['finish']) : null;
    $dto->setSeasons($data['seasons']);
    $dto->setWorkingHours($data['working_hours']);
    $dto->setPrices($data['prices']);
    
    return $dto;
  }
  
  public function toArray(): array
  {
    $array                  = $this->traitToArray();
    $array['start']         = $this->start?->format('H:i:s');
    $array['finish']        = $this->finish?->format('H:i:s');
    $array['prices']        = $this->getPrices();
    $array['working_hours'] = $this->getWorkingHours();
    $array['seasons']       = $this->getSeasons();
    
    return $array;
  }
  
  public function getWorkingHours(?int $weekday = null): array
  {
    $result = $weekdays = [];
    /** @var AreaWorkingHourDto $workingHour */
    foreach ($this->getWorkingHoursDto() as $workingHour) {
      $weekdays[$workingHour->weekday->value] = $workingHour;
    }
    
    if ($weekday !== null && isset($weekdays[$weekday])) {
      return $weekdays[$weekday]->workTime() ?? [];
    }
    foreach (array_keys($this->workDays) as $day) {
      /** @var AreaWorkingHourDto $workDay */
      $workDay      = $weekdays[$day] ??
        AreaWorkingHourDto::fromArray([
          'weekday' => $day,
          'start'   => $this->getStart(),
          'finish'  => $this->getFinish(),
        ])->renderWorkTime($this->period);
      $result[$day] = $workDay->workTime() ?? [];
    }
    return $result;
  }
  
  public function getWorkingHoursDto(): array
  {
    $result = [];
    
    foreach (JsonHelper::decode($this->_workingHours, true) ?? [] as $workingHour) {
      $result[] = AreaWorkingHourDto::fromArray($workingHour)->renderWorkTime($this->period);
    }
    return $result;
  }
  
  private function setWorkingHours($hours): void
  {
    $this->_workingHours = !empty($hours)
      ? (is_array($hours) || is_object($hours) ? JsonHelper::encode($hours) : (string)$hours)
      : JsonHelper::encode([]);
  }
  
  private function setSeasons($seasons): void
  {
    $this->_seasons = !empty($seasons)
      ? (is_array($seasons) || is_object($seasons) ? JsonHelper::encode($seasons) : (string)$seasons)
      : JsonHelper::encode([]);
  }
  
  public function getSeasons(): array
  {
    static $seasons;
    if (empty($seasons)) {
      foreach (JsonHelper::decode($this->_seasons, true) as $season) {
        $seasons[$season['period_id']] = $season['start'];
      }
      asort($seasons);
    }
    return $seasons ?? [];
  }
  
  private function setPrices($prices): void
  {
    $this->_prices = !empty($prices)
      ? (is_array($prices) || is_object($prices) ? JsonHelper::encode($prices) : (string)$prices)
      : JsonHelper::encode([]);
  }
  
  /**
   * @return array<AreaPriceDto>
   * @throws Exception
   */
  public function getPricesDto(): array
  {
    $result = [];
    foreach (JsonHelper::decode($this->_prices, true) ?? [] as $price) {
      $result[] = AreaPriceDto::fromArray($price);
    }
    
    return $result;
  }
  
  /**
   * @throws Exception
   */
  public function getPrices(?int $season = null, ?int $weekday = null): array
  {
    if (empty($this->prices)) {
      $prices = [];
      foreach ($this->getPricesDto() as $price) {
        $prices[$price->season][$price->weekday->value][$price->getStart()] = $price->amount;
      }
      foreach (array_keys($this->getSeasons()) as $seasonId) {
        foreach (array_keys($this->workDays) as $week) {
          foreach (array_keys($this->getWorkingHours($week)) as $time) {
            if (!isset($prices[$seasonId][$week][$time])) {
              $prices[$seasonId][$week][$time] = 0;
            }
          }
        }
      }
      $this->prices = JsonHelper::encode($prices);
    }
    $prices = JsonHelper::decode($this->prices, true) ?? [];
    if ($season !== null && isset($prices[$season])) {
      $prices = $prices[$season];
    }
    if ($weekday !== null) {
      if ($season !== null && isset($prices[$weekday])) {
        $prices = $prices[$weekday];
      } else {
        $prices = array_map(function ($price) use ($weekday) { return $price[$weekday]; }, $prices);
      }
    }
    
    return $prices;
  }
  
  public function getPrice(int $season, int $weekday, string $time): float
  {
    return $this->getPrices($season, $weekday)[date('H:i', strtotime($time))] ?? 0;
  }
  
  public function getPriceByDateTime($date, $time): float
  {
    $time = date('H:i', strtotime($time));
    
    return $this->getPrice(CalendarHelper::getSeasonByDate($date, $this->getSeasons()), CalendarHelper::getWeekdayByUnixTime($date), $time);
  }
  
  public function getStart(bool $addSeconds = false): string
  {
    return $addSeconds ? $this->start?->format('H:i:s') : $this->start?->format('H:i');
  }
  
  public function getFinish(bool $addSeconds = false): string
  {
    return $addSeconds ? $this->finish?->format('H:i:s') : $this->finish?->format('H:i');
  }

  public function getFullTitle(): string
  {
    return $this->getTitleForTypeSport() . ' - ' . $this->getTitle();
  }
}