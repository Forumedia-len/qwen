<?php

namespace AC\core\modules\areas\entities\dto;

use AC\app\entities\traits\sets\HasAreaType;

class AreaTypeDto
{
  use HasAreaType {
    fromArray as traitFromArray;
    toArray as traitToArray;
  }
  
  public ?int   $type_id;
  public string $current_alias;
  public string $type_alias_type_id;
  public int    $type_count_alias;
  
  public static function fromArray(array $data): self
  {
    $dto          = self::traitfromArray($data);
    $dto->type_id = $dto->getId();
    $dto->setTypeAliasTypeId($data['type_alias_type_id'] ?? $dto->getAlias() . '_' . $dto->getId());
    $dto->setTypeCountAlias($data['type_count_alias'] ?? 1);
    $dto->setCurrentAlias($data['current_alias'] ?? null);
    
    return $dto;
  }
  
  public function toArray(): array
  {
    $data                       = self::traittoArray();
    $data['type_alias_type_id'] = $this->getTypeAliasTypeId();
    $data['type_count_alias']   = $this->getTypeCountAlias();
    $data['current_alias']      = $this->getCurrentAlias();
    return $data;
  }
  
  public function getCurrentAlias(): string
  {
    return $this->current_alias ?? ($this->getTypeCountAlias() > 1 ? $this->getAlias() . '_' . $this->getId() : $this->getAlias());
  }
  
  public function setCurrentAlias(?string $current_alias): void
  {
    $this->current_alias = $current_alias ?? ($this->getTypeCountAlias() > 1 ? $this->getAlias() . '_' . $this->getId() : $this->getAlias());
  }
  
  public function getTypeAliasTypeId(): string
  {
    return $this->type_alias_type_id ?? $this->getAlias() . '_' . $this->getId();
  }
  
  public function setTypeAliasTypeId(?string $type_alias_type_id): void
  {
    $this->type_alias_type_id = $type_alias_type_id ?? $this->getAlias() . '_' . $this->getId();
  }
  
  public function getTypeCountAlias(): int
  {
    return $this->type_count_alias ?? 1;
  }
  
  public function setTypeCountAlias(?int $type_count_alias): void
  {
    $this->type_count_alias = $type_count_alias ?? 1;
  }
}
