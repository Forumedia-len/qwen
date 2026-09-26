<?php

namespace AC\core\modules\config\helpers;

/**
 * Читает обычные и разбирает строковые константы со значениями, привязанными к контексту.
 *
 * Поддерживаемые форматы:
 *   VALUE
 *   type_id_sport_id|VALUE
 *   all|DEFAULT|type_id|VALUE|type_id_sport_id|VALUE
 *
 * Приоритет поиска: type_sport_area -> type_sport -> type -> all.
 */
final class ScopedConstantHelper
{
  public static function contextKey(?int $typeId, ?int $sportId = null, ?int $areaId = null): ?string
  {
    if ($typeId === null) {
      return null;
    }

    $parts = [$typeId];
    if ($sportId !== null) {
      $parts[] = $sportId;
    }
    if ($areaId !== null) {
      $parts[] = $areaId;
    }

    return implode('_', $parts);
  }

  public static function value(string $constantName, ?string $contextKey = null, mixed $default = null): mixed
  {
    if (!defined($constantName)) {
      return $default;
    }

    $raw = constant($constantName);
    if (!is_string($raw) || !str_contains($raw, '|')) {
      return $raw;
    }

    $values = self::parseScopedValues($raw);
    foreach (self::contextPriorities($contextKey) as $key) {
      if (array_key_exists($key, $values)) {
        return $values[$key];
      }
    }

    return $default;
  }

  public static function stringValue(
    string $constantName,
    ?string $contextKey = null,
    ?string $default = null
  ): ?string {
    $value = self::value($constantName, $contextKey, $default);
    if (!is_string($value)) {
      return $default;
    }

    $value = trim($value);

    return $value === '' || $value === 'false' ? $default : $value;
  }

  /**
   * Возвращает boolean-значение обычной или контекстной константы.
   */
  public static function boolValue(
    string $constantName,
    ?string $contextKey = null,
    bool $default = false
  ): bool {
    $value = self::value($constantName, $contextKey, $default);
    if (is_bool($value)) {
      return $value;
    }

    if (!is_int($value) && !is_string($value)) {
      return $default;
    }

    $value = is_string($value) ? trim($value) : $value;
    $result = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    return $result ?? $default;
  }

  /** @return list<string> */
  public static function listValue(
    string $constantName,
    ?string $contextKey = null,
    string $separator = ';'
  ): array {
    $value = self::stringValue($constantName, $contextKey);
    if ($value === null) {
      return [];
    }

    return array_values(array_filter(array_map('trim', explode($separator, $value))));
  }

  /** @return array<string, string> */
  private static function parseScopedValues(string $raw): array
  {
    static $cache = [];
    if (isset($cache[$raw])) {
      return $cache[$raw];
    }

    $values = [];
    $parts  = explode('|', $raw);
    for ($i = 0, $count = count($parts); $i + 1 < $count; $i += 2) {
      $key = trim($parts[$i]);
      if ($key !== '') {
        $values[$key] = trim($parts[$i + 1]);
      }
    }

    return $cache[$raw] = $values;
  }

  /** @return list<string> */
  private static function contextPriorities(?string $contextKey): array
  {
    $key        = trim((string)$contextKey, '_ ');
    $priorities = [];

    while ($key !== '' && $key !== 'all') {
      $priorities[] = $key;
      $position     = strrpos($key, '_');
      $key          = $position === false ? '' : substr($key, 0, $position);
    }

    $priorities[] = 'all';

    return array_values(array_unique($priorities));
  }
}
