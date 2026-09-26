<?php

namespace AC\app\entities\traits\fields;

use AC\app\entities\enums\Active;

trait HasActive
{
  public Active $active;
  
  public function isActive(): bool
  {
    return $this->active === Active::YES;
  }
  
  public function setActive(int $value): void { $this->active = Active::from($value); }
  
  public function getActive(): int { return $this->active->value; }
}