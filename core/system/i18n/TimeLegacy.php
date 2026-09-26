<?php

declare(strict_types = 1);

namespace AC\core\system\i18n;

use DateTime;

/**
 * Legacy Time class.
 *
 * This class is only for backward compatibility. Do not use.
 * This is not immutable! Some methods are immutable,
 * but some methods can alter the state.
 *
 * @property int $age         read-only
 * @property string $day         read-only
 * @property string $dayOfWeek   read-only
 * @property string $dayOfYear   read-only
 * @property bool $dst         read-only
 * @property string $hour        read-only
 * @property bool $local       read-only
 * @property string $minute      read-only
 * @property string $month       read-only
 * @property string $quarter     read-only
 * @property string $second      read-only
 * @property int $timestamp   read-only
 * @property bool $utc         read-only
 * @property string $weekOfMonth read-only
 * @property string $weekOfYear  read-only
 * @property string $year        read-only
 *
 * @deprecated Use Time instead.
 */
class TimeLegacy extends DateTime
{
  use TimeTrait;
}
