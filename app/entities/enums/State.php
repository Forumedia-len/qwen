<?php

namespace AC\app\entities\enums;

enum State: int
{
  case ON = 1;
  case OFF = 0;
  
  public function isOn(): bool { return $this === self::ON; }
  public function isOff(): bool { return $this === self::OFF; }
  
}
