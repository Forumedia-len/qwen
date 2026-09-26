<?php

namespace AC\app\entities\traits\sets;

trait HasSpecPrice
{
  use HasPriceOption;
  
  /**
   * Создает DTO из массива данных
   */
  public static function fromArray(array $data): self
  {
    $dto = new self();
    
    $dto->id             = $data['sprice_id'] ?? $dto->id;
    $dto->code           = (string)($data['code'] ?? $dto->code);
    $dto->title          = (string)($data['title'] ?? $dto->title);
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
      'id'              => $this->id,
      'sprice_id'       => $this->id,
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
}