<?php

namespace AC\app\entities\traits\fields;

trait HasId
{
  public ?int $id = null;
  
  public function getId(): ?int
  {
    return $this->id;
  }
  
  public function setIdFromDataArray(array $data): self
  {
    foreach ($this->getIdAliases() as $alias) {
      if (isset($data[$alias])) {
        $this->id = (int)$data[$alias];
        break;
      }
    }
    
    return $this;
  }
  
  protected function getIdAliases(): array
  {
    return ['id'];
  }
  
  public function setId(?int $id): void
  {
    $this->id = $id;
  }
}