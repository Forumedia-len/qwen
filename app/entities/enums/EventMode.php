<?php

namespace AC\app\entities\enums;

enum EventMode: int
{
  case All         = 0;
  case Reservation = 1;
  case Ticket      = 2;
  case Block       = 3;
  case Holiday     = 4;
  
  public function label(): string
  {
    return match ($this) {
      self::All         => 'all',
      self::Reservation => 'reservation',
      self::Ticket      => 'ticket',
      self::Block       => 'block',
      self::Holiday     => 'holiday',
    };
  }
  
  public function titleReport(): string
  {
    return match ($this) {
      self::Reservation => lang('Individual lessons only', 'reports'),
      self::Ticket      => lang('Abos only', 'reports'),
      default           => lang('General', 'reports'),
    };
  }
}
