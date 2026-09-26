<?php

namespace AC\core\modules\clients\entities\dto;

use AC\core\system\entities\dto\Dto;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\StringHelper;

class ClientStockDto extends Dto
{
  public ?int    $stockId        = null;
  public string  $code           = '';
  public string  $title          = '';
  public float   $rate           = 0.0;
  public ?string $amount         = null;
  public ?string $typeSport      = null;
  public string  $dimension      = '1'; // set: '1', '2'
  public ?int    $group          = null;
  public array   $conditions     = [];
  public ?string $preferences    = null;
  public bool    $onlyOnce       = false;
  public bool    $forAll         = false;
  public bool    $duration       = false;
  public ?string $durationStart  = null; // формат: 'Y-m-d'
  public ?string $durationFinish = null; // формат: 'Y-m-d'
  public array   $durations      = [];
  
  
  /**
   *
   * @param array $data
   * @return self
   */
  public static function fromArray(array $data): self
  {
    $dto = new self();
    
    $dto->stockId        = $data['stock_id'] ?? $dto->stockId;
    $dto->code           = StringHelper::shield((string)($data['code'] ?? $dto->code));
    $dto->title          = StringHelper::shield((string)($data['title'] ?? $dto->title));
    $dto->rate           = (float)($data['rate'] ?? $dto->rate);
    $dto->dimension      = (string)($data['dimension'] ?? $dto->dimension);
    $dto->typeSport      = $data['type_sport'] ?? $dto->typeSport;
    $dto->forAll         = (bool)($data['for_all'] ?? $dto->forAll);
    $dto->onlyOnce       = (bool)($data['only_once'] ?? $dto->onlyOnce);
    $dto->group          = isset($data['group']) ? (int)$data['group'] : $dto->group;
    $dto->preferences    = $data['preferences'] ?? $dto->preferences;
    $dto->duration       = (bool)($data['duration'] ?? $dto->duration);
    $dto->durationStart  = $data['duration_start'] ?? $dto->durationStart;
    $dto->durationFinish = $data['duration_finish'] ?? $dto->durationFinish;
    $dto->durations      = $data['durations'] ?? $dto->durations;
    
    return $dto;
  }
  
  public function toArray(): array
  {
    return [
      'stock_id'        => $this->stockId,
      'code'            => $this->code,
      'title'           => $this->title,
      'rate'            => $this->rate,
      'dimension'       => $this->dimension,
      'type_sport'      => $this->typeSport,
      'for_all'         => $this->forAll ? '1' : '0', // enum('0','1') — сохраняем как строку
      'duration'        => $this->duration ? '1' : '0',
      'duration_start'  => $this->durationStart,
      'duration_finish' => $this->durationFinish,
      'durations'       => $this->durations,
      'only_once'       => (int)$this->onlyOnce, // tinyint(1)
      'group'           => $this->group,
      'preferences'     => $this->preferences,
    ];
  }
  
  public function amount(): string
  {
    return NumberHelper::format($this->rate, 2, ',', ' ') . ' ' .
      match ($this->dimension) {
        '2'     => '%',
        default => CURR_VALUTE,
      };
  }
  
  public function getDurationsStrToTimeByWeekday($weekday): array
  {
    $durations = [];
    
    foreach (array_keys($this->durations[$weekday]) as $duration) {
      $durations[] = strtotime($duration);
    }
    
    return $durations;
  }
}