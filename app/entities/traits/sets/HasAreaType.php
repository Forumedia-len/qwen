<?php

namespace AC\app\entities\traits\sets;

use AC\app\entities\traits\fields\HasActive;
use AC\app\entities\traits\fields\HasAlias;
use AC\app\entities\traits\fields\HasId;
use AC\app\entities\traits\fields\HasSort;
use AC\app\entities\traits\fields\HasTitle;

trait HasAreaType
{
  use HasId, HasTitle;
  
  public string $color = '58bd4b';
  use HasSort;
  
  public ?string $comments = null;
  use HasActive, HasAlias;
  
  public string $group = 'reservation';
  
  public static function fromArray(array $data): self
  {
    $dto = new self();
    
    $dto->id       = $data['type_id'] ? (int)$data['type_id'] : null;
    $dto->title    = $data['title'] ?? '';
    $dto->color    = $data['color'] ?? $dto->color;
    $dto->sort     = $data['sort'] ?? $dto->sort;
    $dto->comments = $data['comments'] ?? $dto->comments;
    $dto->setActive($data['active'] ?? 0);
    $dto->alias = $data['alias'] ?? $dto->alias;
    $dto->group = $data['group'] ?? $dto->group;
    
    return $dto;
  }
  
  public function toArray(): array
  {
    return [
      'id'       => $this->id,
      'type_id'  => $this->id,
      'title'    => $this->title,
      'color'    => $this->color,
      'sort'     => $this->sort,
      'comments' => $this->comments,
      'active'   => $this->getActive(),
      'alias'    => $this->alias,
      'group'    => $this->group,
    ];
  }
}