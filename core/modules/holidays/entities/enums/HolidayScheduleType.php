<?php

declare(strict_types=1);

namespace AC\core\modules\holidays\entities\enums;

/** Тип праздничного расписания, которое может использовать правила воскресенья. */
enum HolidayScheduleType: string
{
  case Prices = 'prices';
  case Times  = 'times';
}
