<?php

namespace AC\core\modules\config\entities\dto;

use AC\app\entities\traits\fields\HasId;
use AC\app\entities\traits\fields\HasTitle;

class ClubStateDto
{
  use HasId;
  use HasTitle;

  public string $short;
  public string $mark;

  /**
   * Создание DTO из ассоциативного массива
   *
   * @param array $data
   *
   * @return self
   */
  public static function fromArray(array $data): self
  {
    $dto        = new self();
    $dto->id    = $data['id'] ?? null;
    $dto->title = $data['title'] ?? '';
    $dto->short = $data['short'] ?? '';
    $dto->mark  = $data['mark'] ?? '';

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
      'id'    => $this->id,
      'title' => $this->title,
      'short' => $this->short,
      'mark'  => $this->mark,
    ];
  }
}