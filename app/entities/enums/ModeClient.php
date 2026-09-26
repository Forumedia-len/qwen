<?php

namespace AC\app\entities\enums;

enum ModeClient: int
{
  case Online  = 1;
  case Offline = 2;
  case Guest   = 3;
  
  public function label(): string
  {
    return match ($this) {
      self::Online  => lang('Online client', 'reports'),
      self::Offline => lang('Offline client', 'reports'),
      self::Guest   => lang('Guest', 'reports'),
    };
  }
  
  public function shortLabelByGuest()
  {
    return match ($this) {
      self::Online  => lang('short_not'),
      self::Offline => lang('short_not') . '|OFF',
      self::Guest   => lang('short_yes')
    };
  }
}