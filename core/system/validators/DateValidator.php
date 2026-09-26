<?php

namespace AC\core\system\validators;

/**
 * Проверяет календарную дату Y-m-d; формат d.m.Y и нормализация включаются явно.
 *
 */
class DateValidator extends Validator
{
  /**
   * Constant for specifying the validation [[type]] as a date value, used for validation with intl short format.
   * @see type
   */
  const TYPE_DATE = 'date';
  /**
   * Constant for specifying the validation [[type]] as a datetime value, used for validation with intl short format.
   * @see type
   */
  const TYPE_DATETIME = 'datetime';
  /**
   * Constant for specifying the validation [[type]] as a time value, used for validation with intl short format.
   * @see type
   */
  const TYPE_TIME = 'time';

  /**
   * @var string the type of the validator. Indicates, whether a date, time or datetime value should be validated.
   *
   * This property can be set to the following values:
   *
   * - [[TYPE_DATE]] - (default) for validating date values only, that means only values that do not include a time range are valid.
   * - [[TYPE_DATETIME]] - for validating datetime values, that contain a date part as well as a time part.
   * - [[TYPE_TIME]] - for validating time values, that contain no date information.
   *
   * @since 2.0.8
   */
  public $type = self::TYPE_DATE;

  public $pattern = '|^\d{4}-\d{1,2}-\d{1,2}$|';

  /** @var bool Дополнительно принимать d.m.Y из существующих форм бронирования. */
  public bool $allowDottedFormat = false;

  /** @var bool Возвращать проверенную дату в формате Y-m-d. */
  public bool $normalize = false;

  /**
   * {@inheritdoc}
   */
  public function validateType($model)
  {
    if (parent::validateType($model)) {
      $value = $model->{$this->getAttribute()};
      switch (gettype($value)) {
        case 'object' :
          if (checkdate($value->year, $value->month, $value->day)) {
            return true;
          }
          break;
        case 'string' :
          if ($this->strict && !preg_match('/\A(?:\d{4}-\d{1,2}-\d{1,2}|\d{1,2}\.\d{1,2}\.\d{4})\z/', $value)) {
            return false;
          }
          if ($this->allowDottedFormat && preg_match('/\A(\d{1,2})\.(\d{1,2})\.(\d{4})\z/', $value, $parts)) {
            if (!checkdate((int)$parts[2], (int)$parts[1], (int)$parts[3])) {
              return false;
            }
            if ($this->normalize) {
              $model->{$this->getAttribute()} = sprintf('%04d-%02d-%02d', $parts[3], $parts[2], $parts[1]);
            }
            return true;
          }
          if (preg_match($this->pattern, $value)) {
            $tmp = explode('-', $value);
            if (checkdate($tmp[1], $tmp[2], $tmp[0])) {
              if ($this->normalize) {
                $model->{$this->getAttribute()} = sprintf('%04d-%02d-%02d', $tmp[0], $tmp[1], $tmp[2]);
              }
              return true;
            }
          }
          break;
      }
    }

    return false;
  }
}
