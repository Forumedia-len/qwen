<?php

namespace AC\core\modules\webIo\entities\dto;

use AC\app\entities\enums\Active;
use AC\app\entities\enums\State;

class WebIoTypeSateDto
{
  public ?int    $id;
  public ?string $alias;
  public ?string $title;
  public Active  $active;
  public State   $use_reservation;
  
  /**
   * Создание DTO из ассоциативного массива
   *
   * @param array $data
   * @return self
   */
  public static function fromArray(array $data): self
  {
    $dto                  = new self();
    $dto->id              = $data['id'] ?? null;
    $dto->alias           = $data['alias'] ?? 'state';
    $dto->title           = $data['title'] ?? 'State';
    $dto->active          = Active::from($data['active'] ?? 0);
    $dto->use_reservation = State::from($data['use_reservation'] ?? 0);
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
      'id'              => $this->id,
      'alias'           => $this->alias,
      'title'           => $this->title,
      'active'          => $this->active->value,
      'use_reservation' => $this->use_reservation->value,
    ];
  }
  
  public function shortLabel(): string
  {
    return lang($this->alias . '_short_label', 'webIo');
  }
}