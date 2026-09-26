<?php

namespace AC\core\system\validators;

/** Проверяет время начала и массив отмеченных периодов бронирования. */
class TimeValidator extends Validator
{
  /** @var bool Принимать только массив флагов времени. */
  public bool $arrayOnly = false;

  /** Проверяет время или каждый ключ и флаг массива time[HH:MM]. */
  public function validateType($model)
  {
    $attribute = $this->getAttribute();
    $value = $model->$attribute;
    if (is_array($value) && $this->allowArray) {
      $times = [];
      foreach ($value as $time => $selected) {
        $normalized = $this->normalizeTime(urldecode((string)$time));
        if ($normalized === null || !in_array($selected, [0, 1, '0', '1', false, true], true)) {
          return false;
        }
        $times[$normalized] = (int)$selected;
      }
      $model->$attribute = $times;
      return true;
    }
    if ($this->arrayOnly) {
      return false;
    }
    $time = $this->normalizeTime($value);
    if ($time === null) {
      return false;
    }
    $model->$attribute = $time;
    return true;
  }

  /** Нормализует время только после проверки полного значения. */
  private function normalizeTime(mixed $value): ?string
  {
    if (!is_string($value) || !preg_match('/\A([01]?\d|2[0-3]):([0-5]\d)(?::00)?\z/', $value, $parts)) {
      return null;
    }
    return sprintf('%02d:%02d', $parts[1], $parts[2]);
  }
}
