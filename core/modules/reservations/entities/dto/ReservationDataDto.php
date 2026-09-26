<?php

namespace AC\core\modules\reservations\entities\dto;

use AC\app\entities\traits\sets\HasMetaData;
use AC\core\system\entities\dto\DtoInterface;

class ReservationDataDto implements DtoInterface
{
  use HasMetaData;
  
  public ?int $reservationId = null;
  
  public ?int $tmpReservationId = null;
  
  /**
   * @inheritDoc
   */
  public static function fromArray(array $data): self
  {
    $dto                   = new self();
    $dto->id               = $data['id'] ?? $dto->id;
    $dto->reservationId    = $data['reservation_id'] ?? $dto->reservationId;
    $dto->tmpReservationId = $data['tmp_reservation_id'] ?? $dto->tmpReservationId;
    $dto->typeBlock        = $data['type_block'] ?? $dto->typeBlock;
    $dto->entryId          = $data['entry_id'] ?? $dto->entryId;
    $dto->name             = $data['name'] ?? $dto->name;
    $dto->value            = $data['value'] ?? $dto->value;
    
    return $dto;
  }
  
  /**
   * @inheritDoc
   */
  public function toArray(): array
  {
    return [
      'id'                 => $this->id,
      'reservation_id'     => $this->reservationId,
      'tmp_reservation_id' => $this->tmpReservationId,
      'type_block'         => $this->typeBlock,
      'entry_id'           => $this->entryId,
      'name'               => $this->name,
      'value'              => $this->value,
    ];
  }
}