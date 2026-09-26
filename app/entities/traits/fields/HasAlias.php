<?php

namespace AC\app\entities\traits\fields;

trait HasAlias
{
  public string $alias = '';
  
  public function setAlias(string $alias): void { $this->alias = $alias; }
  
  public function getAlias(): string { return $this->alias; }
}