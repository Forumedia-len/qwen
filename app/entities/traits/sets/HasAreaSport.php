<?php

namespace AC\app\entities\traits\sets;

use AC\app\entities\traits\fields\HasAlias;
use AC\app\entities\traits\fields\HasId;
use AC\app\entities\traits\fields\HasSort;
use AC\app\entities\traits\fields\HasTitle;

trait HasAreaSport
{
  use HasId, HasTitle, HasSort, HasAlias;
  
  public string $comment = '';
  
  public static function fromArray(array $data): self
  {
    $dto = new self();
    
    $dto->id      = $data['sport_id'] ? (int)$data['sport_id'] : null;
    $dto->title   = $data['title'] ?? '';
    $dto->sort    = $data['sort'] ?? $dto->sort;
    $dto->comment = $data['comment'] ?? $dto->comment;
    $dto->alias   = $data['alias'] ?? $dto->alias;
    
    return $dto;
  }
  
  public function toArray(): array
  {
    return [
      'id'      => $this->id,
      'sport_id' => $this->id,
      'title'   => $this->title,
      'sort'    => $this->sort,
      'comment' => $this->comment,
      'alias'   => $this->alias,
    ];
  }
}