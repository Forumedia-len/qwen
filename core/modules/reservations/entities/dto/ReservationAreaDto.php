<?php

namespace AC\core\modules\reservations\entities\dto;

use AC\app\entities\traits\sets\HasArea;

class ReservationAreaDto
{
  use HasArea {
    fromArray as traitFromArray;
    toArray as traitToArray;
  }
  
  public ?ReservationAreaTypeDto  $type;
  public ?ReservationAreaSportDto $sport;
  
  public static function fromArray(array $data): self
  {
    // Сначала вызываем fromArray() из трейта
    $dto = self::traitFromArray($data);
    
    // Дополнительно обрабатываем свои поля
    $dto->type  = isset($data['type']) ? ReservationAreaTypeDto::fromArray($data['type']) : null;
    $dto->sport = isset($data['sport']) ? ReservationAreaSportDto::fromArray($data['sport']) : null;
    
    return $dto;
  }
  
  public function toArray(): array
  {
    $data          = $this->traitToArray();
    $data['type']  = $this->type instanceof ReservationAreaTypeDto ? $this->type->toArray() : null;
    $data['sport'] = $this->sport instanceof ReservationAreaSportDto ? $this->sport->toArray() : null;
    
    return $data;
  }
}