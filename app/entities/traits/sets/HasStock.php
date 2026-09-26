<?php

namespace AC\app\entities\traits\sets;

trait HasStock
{
  use HasPriceOption;
  public bool    $onlyOnce       = false;
  public string  $dimension      = '1'; // set: '1', '2'
  
  /**
   *
   * @param array $data
   * @return self
   */
  public static function fromArray(array $data): self
  {
    $dto = new self();
    
    $dto->id             = $data['stock_id'] ?? $dto->id;
    $dto->code           = (string)($data['code'] ?? $dto->code);
    $dto->title          = (string)($data['title'] ?? $dto->title);
    $dto->rate           = (float)($data['rate'] ?? $dto->rate);
    $dto->typeSport      = $data['type_sport'] ?? $dto->typeSport;
    $dto->forAll         = (bool)($data['for_all'] ?? $dto->forAll);
    $dto->duration       = (bool)($data['duration'] ?? $dto->duration);
    $dto->durationStart  = $data['duration_start'] ?? $dto->durationStart;
    $dto->durationFinish = $data['duration_finish'] ?? $dto->durationFinish;
    $dto->onlyOnce       = (bool)($data['only_once'] ?? $dto->onlyOnce);
    $dto->dimension      = (string)($data['dimension'] ?? $dto->dimension);
    $dto->durations      = $data['durations'] ?? $dto->durations;
    
    return $dto;
  }
  
  public function toArray(): array
  {
    return [
      'id'              => $this->id,
      'stock_id'        => $this->id,
      'code'            => $this->code,
      'title'           => $this->title,
      'rate'            => $this->rate,
      'type_sport'      => $this->typeSport,
      'for_all'         => $this->forAll ? '1' : '0', // enum('0','1') — сохраняем как строку
      'duration'        => $this->duration ? '1' : '0',
      'duration_start'  => $this->durationStart,
      'duration_finish' => $this->durationFinish,
      'only_once'       => (int)$this->onlyOnce, // tinyint(1)
      'dimension'       => $this->dimension,
      'durations'       => $this->durations,
    ];
  }
}