<?php

namespace AC\app\entities\traits\fields;

use AC\app\entities\enums\State;

trait HasDefault
{
  public State $default;
  
  public function isDefault(): bool
  {
    return $this->default === State::ON;
  }
  
  public function setDefault(int $value): void { $this->default = State::from($value); }
  
  public function getDefault(): int { return $this->default->value; }
}