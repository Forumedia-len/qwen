<?php

namespace AC\app\entities\enums;

enum Active: int
{
  case YES = 1;
  case NO  = 0;
  
  public function isActive(): bool { return $this === self::YES; }
}
