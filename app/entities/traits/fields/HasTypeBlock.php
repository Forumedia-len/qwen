<?php

namespace AC\app\entities\traits\fields;

trait HasTypeBlock
{
  public ?string $typeBlock = null;
  
  public function getTypeBlock(): ?string { return $this->typeBlock; }
  
  public function setTypeBlock(?string $typeBlock): void { $this->typeBlock = $typeBlock; }
}