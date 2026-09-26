<?php

declare(strict_types = 1);

namespace AC\core\system\object\entity\cast;

/**
 * Каст «логического» значения из БД/строки (bool + гибрид с числом).
 *
 * Поддержка «гибридных» настроек:
 * - 0/1 → false/true
 * - любое другое число → int (порог, например количество)
 *
 * Параметры:
 * - nullOnEmpty (bool): true — null и пустая строка → null (по умолчанию false)
 * - strictBool (bool): true — результат (если не null) приводится к bool (по умолчанию false)
 * - allowNull (bool): true — set(null) → null (по умолчанию false)
 */
final class BoolishCast extends BaseCast
{
  public static function get($value, array $params = []): mixed
  {
    $nullOnEmpty = (bool)($params['nullOnEmpty'] ?? false);
    $strictBool  = (bool)($params['strictBool'] ?? false);
    if ($nullOnEmpty && ($value === null || $value === '')) {
      return null;
    }

    if (is_bool($value)) {
      return $strictBool ? (bool)$value : $value;
    }

    if (is_int($value)) {
      $out = match ($value) {
        0 => false,
        1 => true,
        default => $value,
      };
      return $strictBool ? (bool)$out : $out;
    }
    if (is_float($value)) {
      $out = match (true) {
        $value === 0.0 => false,
        $value === 1.0 => true,
        default => (int)$value,
      };
      return $strictBool ? (bool)$out : $out;
    }

    $s = strtolower(trim((string)$value));
    if ($s === '') {
      return false;
    }
    if (is_numeric($s)) {
      // целые и дробные строки по-разному.
      if (preg_match('/^-?\d+$/', $s)) {
        $intVal = (int)$s;
        $out    = match ($intVal) {
          0 => false,
          1 => true,
          default => $intVal,
        };
        return $strictBool ? (bool)$out : $out;
      }

      $floatVal = (float)$s;
      $out      = match (true) {
        $floatVal === 0.0 => false,
        $floatVal === 1.0 => true,
        default => (int)$floatVal,
      };
      return $strictBool ? (bool)$out : $out;
    }

    $out = match ($s) {
      'true', 'yes', 'on' => true,
      'false', 'no', 'off' => false,
      default => (bool)$value,
    };

    return $strictBool ? (bool)$out : $out;
  }

  public static function set($value, array $params = []): int|null
  {
    $allowNull = (bool)($params['allowNull'] ?? false);
    if ($allowNull && $value === null) {
      return null;
    }

    return (int)(bool)self::get($value, ['nullOnEmpty' => false, 'strictBool' => true]);
  }
}

