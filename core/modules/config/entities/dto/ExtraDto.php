<?php

namespace AC\core\modules\config\entities\dto;

use AC\app\entities\enums\State;
use AC\core\system\helpers\JsonHelper;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\TimeHelper;

class ExtraDto
{
  public int   $extra_id;
  public float $rate;
  public int   $club_state;
  public int   $area_type_id;
  public int   $area_sport_id;
  public State $use_time;
  
  private ?string $pricesByWeekTime;
  
  /**
   * Создание DTO из ассоциативного массива
   *
   * @param array $data
   * @return self
   */
  public static function fromArray(array $data): self
  {
    $dto                   = new self();
    $dto->extra_id         = $data['extra_id'] ?? null;
    $dto->rate             = NumberHelper::float($data['rate'] ?? 0);
    $dto->club_state       = $data['club_state'] ?? 1;
    $dto->area_type_id     = $data['area_type_id'] ?? 1;
    $dto->area_sport_id    = $data['area_sport_id'] ?? 1;
    $dto->use_time         = State::from($data['use_time'] ?? 0);
    $dto->pricesByWeekTime = $data['week_times'] ?? null;
    return $dto;
  }
  
  /**
   * Преобразование DTO в ассоциативный массив
   *
   * @return array
   */
  public function toArray(): array
  {
    return [
      'extra_id'      => $this->extra_id,
      'rate'          => $this->rate,
      'club_state'    => $this->club_state,
      'area_type_id'  => $this->area_type_id,
      'area_sport_id' => $this->area_sport_id,
      'use_time'      => $this->use_time->value,
      'week_times'    => $this->pricesByWeekTime,
    ];
  }
  
  public function getAmount($weekday, $time): float
  {
    if ($this->use_time->isOn()) {
      return $this->getPricesByWeekTime($weekday, $time);
    }
    
    return $this->rate;
  }
  
  private function getPricesByWeekTime($weekday, $time)
  {
    static $pricesByWeekTime;
    if (!$pricesByWeekTime) {
      foreach (JsonHelper::decode($this->pricesByWeekTime, true) as $row) {
        $pricesByWeekTime[$row['weekday']][TimeHelper::convertTime24($row['start'], false)] = NumberHelper::float($row['price']);
      }
    }
    
    return $pricesByWeekTime[$weekday][TimeHelper::convertTime24($time, false)] ?? 0;
  }
}