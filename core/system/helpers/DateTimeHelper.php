<?php

namespace AC\core\system\helpers;

use DateTimeImmutable;

class DateTimeHelper
{

  public static function validateDate(string $dateTime, string $format = 'Y-m-d H:i:s', &$d = null): bool
  {
    $d = DateTimeImmutable::createFromFormat($format, $dateTime);

    return $d && $d->format($format) == $dateTime;
  }

}