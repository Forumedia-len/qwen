<?php

declare(strict_types = 1);

namespace AC\core\system\i18n;

use DateTimeImmutable;
use Stringable;

/**
 * A localized date/time package inspired
 * by Nesbot/Carbon and CakePHP/Chronos.
 *
 * Requires the intl PHP extension.
 *
 * @property-read int $age
 * @property-read string $day
 * @property-read string $dayOfWeek
 * @property-read string $dayOfYear
 * @property-read bool $dst
 * @property-read string $hour
 * @property-read bool $local
 * @property-read string $minute
 * @property-read string $month
 * @property-read string $quarter
 * @property-read string $second
 * @property-read int $timestamp
 * @property-read bool $utc
 * @property-read string $weekOfMonth
 * @property-read string $weekOfYear
 * @property-read string $year
 *
 */
class Time extends DateTimeImmutable implements Stringable
{
  use TimeTrait;
}
