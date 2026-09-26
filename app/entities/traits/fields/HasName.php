<?php

namespace AC\app\entities\traits\fields;

trait HasName
{
  public string $name = '';
  
  public function getName(): string { return $this->name; }
  
  public function setName(string $name): void { $this->name = $name; }
}