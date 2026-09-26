<?php

namespace AC\app\entities\traits\fields;

trait HasValue
{
  public ?string $value = null;
  
  public function getValue(): ?string { return $this->value; }
  
  public function setValue(?string $value): void { $this->value = $value; }
}