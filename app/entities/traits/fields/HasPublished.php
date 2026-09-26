<?php

namespace AC\app\entities\traits\fields;

use AC\app\entities\enums\State;

trait HasPublished
{
  public State $published;
  
  public function isPublished(): bool
  {
    return $this->published === State::ON;
  }
  
  public function setPublished(int $value): void { $this->published = State::from($value); }
  
  public function getPublished(): int { return $this->published->value; }
}