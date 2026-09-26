<?php

namespace AC\core\modules\clients\entities\dto;

use AC\core\system\entities\dto\Dto;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\StringHelper;

class ClientSpecPriceDto extends Dto
{
  public ?int    $spriceId       = null;
  public string  $code           = '';
  public string  $title          = '';
  public float   $rate           = 0.0;
  public ?string $typeSport      = null;
  public bool    $forAll         = false;  // enum('0','1')
  public bool    $duration       = false;  // enum('0','1')
  public ?string $durationStart  = null;   // format: 'Y-m-d'
  public ?string $durationFinish = null;   // format: 'Y-m-d'
  public array   $durations      = [];
  
  /**
   * Создает DTO из массива данных
   */
  public static function fromArray(array $data): self
  {
    $dto = new self();
    
    $dto->spriceId       = $data['sprice_id'] ?? $dto->spriceId;
    $dto->code           = StringHelper::shield((string)($data['code'] ?? $dto->code));
    $dto->title          = StringHelper::shield((string)($data['title'] ?? $dto->title));
    $dto->rate           = (float)($data['rate'] ?? $dto->rate);
    $dto->typeSport      = $data['type_sport'] ?? $dto->typeSport;
    $dto->forAll         = (bool)($data['for_all'] ?? $dto->forAll);
    $dto->duration       = (bool)($data['duration'] ?? $dto->duration);
    $dto->durationStart  = $data['duration_start'] ?? $dto->durationStart;
    $dto->durationFinish = $data['duration_finish'] ?? $dto->durationFinish;
    $dto->durations      = $data['durations'] ?? $dto->durations;
    
    return $dto;
  }
  
  /**
   * Преобразует DTO в массив для сериализации/хранения
   */
  public function toArray(): array
  {
    return [
      'sprice_id'       => $this->spriceId,
      'code'            => $this->code,
      'title'           => $this->title,
      'rate'            => $this->rate,
      'type_sport'      => $this->typeSport,
      'for_all'         => $this->forAll ? '1' : '0', // enum('0','1') — сохраняем как строку
      'duration'        => $this->duration ? '1' : '0',
      'duration_start'  => $this->durationStart,
      'duration_finish' => $this->durationFinish,
      'durations'       => $this->durations,
    ];
  }
  
  public function amount(): string
  {
    return NumberHelper::format($this->rate, 2, ',', ' ') . ' ' . CURR_VALUTE;
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