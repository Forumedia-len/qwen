<?php

namespace AC\core\modules\discounts\entities\enums;

enum TypeDimension: int
{
  case PERCENT = 1;
  case FIXED   = 2;
  
  public function show(): string
  {
    return match ($this) {
      self::PERCENT => '%',
      self::FIXED   => CURR_VALUTE,
    };
  }
}
