<?php

namespace AC\core\modules\mailing\entities\enums;

enum ModeTemplate: int
{
  case ADMIN   = 0;
  case USER    = 1;
  case SERVICE = 2;
  
  public function label(): string
  {
    return match ($this) {
      self::ADMIN   => lang('Admin'),
      self::USER    => lang('Client'),
      self::SERVICE => lang('Service'),
    };
  }
  
  public static function checkCase(int $value, ModeTemplate $mode): bool
  {
    return $value === $mode->value;
  }
  
  public static function getModeForPerson(): array
  {
    return [self::ADMIN->value, self::USER->value];
  }
}
