<?php

declare(strict_types=1);

namespace AC\core\modules\tickets\entities\enums;

/** Источник строки исключённого времени абонемента. */
enum TicketDisabledTimeSource: int
{
  case Cancellation = 1;
  case InitialBlock = 2;
  case Manual       = 3;
}
