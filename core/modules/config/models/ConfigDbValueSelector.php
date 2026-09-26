<?php

namespace AC\core\modules\config\models;

use Service;

/**
 * Универсальный селектор значений из таблицы {prefix}config с поддержкой:
 * - каскадного перебора ключа type (хвост после «|» усечением по «_» от полного к короткому)
 * - fallback на заданный базовый type (по умолчанию: default)
 *
 * Хвост type должен собираться через ConfigTypeKeyBuilder: {current_alias}[_{sport}][_{area}].
 */
class ConfigDbValueSelector extends ConfigModel
{
  private static function trimKey(string $key): string
  {
    return trim($key);
  }

  /**
   * Возвращает варианты ключа для чтения:
   * - исходный (trim)
   * - lower(trim) (если включён fallback и отличается)
   */
  private static function keyVariants(string $key, bool $tryLowercaseFallback): array
  {
    $key = self::trimKey($key);
    if ($key === '') {
      return [''];
    }
    $lower = strtolower($key);
    if (!$tryLowercaseFallback || $lower === $key) {
      return [$key];
    }
    return [$key, $lower];
  }

  private static function parseType(string $type): array
  {
    $type = self::trimKey($type);
    if ($type === '') {
      return [null, ''];
    }

    $pos = strpos($type, '|');
    if ($pos === false) {
      return [null, $type];
    }

    $prefix = trim(substr($type, 0, $pos));
    $rest   = trim(substr($type, $pos + 1));

    return [$prefix !== '' ? $prefix : null, $rest];
  }

  private static function typeFallbacks(string $type): array
  {
    [$prefix, $rest] = self::parseType($type);
    if ($rest === '') {
      return [];
    }

    $parts = array_values(array_filter(explode('_', $rest), static fn($p) => $p !== ''));
    if (count($parts) <= 1) {
      $base = [$parts[0]];
      return $prefix ? array_map(static fn($t) => $prefix . '|' . $t, $base) : $base;
    }

    $fallbacks = [];
    for ($i = count($parts); $i >= 1; $i--) {
      $fallbacks[] = implode('_', array_slice($parts, 0, $i));
    }

    $unique = [];
    foreach ($fallbacks as $f) {
      if (!in_array($f, $unique, true)) {
        $unique[] = $f;
      }
    }

    return $prefix ? array_map(static fn($t) => $prefix . '|' . $t, $unique) : $unique;
  }

  /**
   * Упорядоченные ключи поля `type` в таблице config: читаем сверху вниз, первое найденное значение побеждает.
   *
   * Как собирается список (упрощённо, без дублей и без lower-case вариантов):
   *
   *   1) Разбор входа:  $type  =  "{prefix}|{rest}"   (если "|" нет — rest = весь $type, prefix = null)
   *   2) Каскад typeFallbacks: rest режется по "_", берутся префиксы сегментов от полного к короткому,
   *      каждый ключ = "{prefix}|{усечённый_rest}"  (если prefix был null — без префикса, только rest).
   *   3) Fallback из параметров get():
   *      - при непустом $fallbackType и непустом prefix:  "{prefix}|{fallbackType}"
   *      - при $fallbackBarePrefix или fallbackUnprefixedType и prefix:
   *                                                      "{prefix}" (без хвоста)
   *      - при $fallbackUnprefixedType:                  "{fallbackType}" (без prefix)
   *
   * Пример A (type_count_alias > 1, current_alias = close_10): вход double|close_10_7_10,
   * $fallbackType = default, fallbackBarePrefix = true, fallbackUnprefixedType = false:
   *
   *   #0  double|close_10_7_10   ← current_alias + sport + area (ConfigTypeKeyBuilder)
   *   #1  double|close_10_7      ← без area
   *   #2  double|close_10        ← только current_alias (alias + disambiguation type_id)
   *   #3  double|close            ← bare alias (последний сегмент каскада)
   *   #4  double|default
   *   #5  double
   *
   * Пример B (type_count_alias = 1, current_alias = open): вход double|open_7_10 — каскад:
   *
   *   #0  double|open_7_10
   *   #1  double|open_7
   *   #2  double|open
   *   … далее fallback как в примере A (#4–#5 при тех же флагах).
   *
   * Пример C (alias с «_» внутри, current_alias = mc_arena): restriction|mc_arena_7_10 —
   * rest режется на [mc, arena, 7, 10], не на [mc_arena, 7, 10]:
   *
   *   #0  restriction|mc_arena_7_10
   *   #1  restriction|mc_arena_7
   *   #2  restriction|mc_arena
   *   #3  restriction|mc              ← побочный шаг (усечение внутри alias)
   *   … далее fallback (#4–#5 при тех же флагах).
   *
   * Селектор не различает «_» внутри alias и «_» между alias/sport/area — см. scoped-config.md.
   *
   * Если fallbackUnprefixedType = true, перед ключом default (без prefix) добавится bare prefix.
   *
   * При tryLowercaseFallback = true после «канонического» ключа может идти его lower-case копия (если отличается).
   *
   * @return list<string>
   */
  private static function prioritizedTypeKeys(
    string $type,
    string $fallbackType,
    bool $tryLowercaseFallback,
    bool $fallbackBarePrefix,
    bool $fallbackUnprefixedType
  ): array {
    $keys = [];
    $add  = static function (string $k) use (&$keys): void {
      $k = self::trimKey($k);
      if ($k === '') {
        return;
      }
      if (!in_array($k, $keys, true)) {
        $keys[] = $k;
      }
    };

    foreach (self::typeFallbacks($type) as $t) {
      foreach (self::keyVariants($t, $tryLowercaseFallback) as $typeVariant) {
        $add($typeVariant);
      }
    }

    [$prefix] = self::parseType($type);
    $fallbackType = self::trimKey($fallbackType);
    if ($fallbackType !== '') {
      if ($prefix) {
        foreach (self::keyVariants($prefix . '|' . $fallbackType, $tryLowercaseFallback) as $typeVariant) {
          $add($typeVariant);
        }
      }
      if ($prefix && ($fallbackBarePrefix || $fallbackUnprefixedType)) {
        foreach (self::keyVariants($prefix, $tryLowercaseFallback) as $typeVariant) {
          $add($typeVariant);
        }
      }
      if ($fallbackUnprefixedType) {
        foreach (self::keyVariants($fallbackType, $tryLowercaseFallback) as $typeVariant) {
          $add($typeVariant);
        }
      }
    }
    return $keys;
  }

  /**
   * When $default is null, value is returned as stored (no cast). This matches previous cast($v, null)
   * behavior and avoids a redundant cast before getBoolOrNull/getIntOrNull apply typed normalization.
   */
  private static function castOrRaw(mixed $value, mixed $default): mixed
  {
    return $default === null ? $value : self::cast($value, $default);
  }

  /** Нормализация значения из gateway для фасадов L4 (cast по типу default). */
  public static function normalizeResolvedValue(mixed $value, mixed $default): mixed
  {
    return self::castOrRaw($value, $default);
  }

  public static function get(
    string $type,
    string $alias,
    mixed $default = null,
    string $fallbackType = 'default',
    bool $tryLowercaseFallback = false,
    bool $fallbackBarePrefix = false,
    bool $fallbackUnprefixedType = true
  ): mixed {
    $match = self::resolveFirst(
      $type,
      [$alias],
      $fallbackType,
      $tryLowercaseFallback,
      $fallbackBarePrefix,
      $fallbackUnprefixedType
    );

    if ($match === null) {
      return $default;
    }

    return self::castOrRaw($match['value'], $default);
  }

  /**
   * Первое найденное значение с каскадом type и списка alias.
   *
   * @param list<string> $aliases
   * @return array{value: mixed, type: string, alias: string}|null
   */
  public static function resolveFirst(
    string $type,
    array $aliases,
    string $fallbackType = 'default',
    bool $tryLowercaseFallback = false,
    bool $fallbackBarePrefix = false,
    bool $fallbackUnprefixedType = true
  ): ?array {
    $aliases = array_values(array_filter(
      array_map(static fn(string $a) => self::trimKey($a), $aliases),
      static fn(string $a) => $a !== ''
    ));

    if ($aliases === []) {
      return null;
    }

    foreach (
      self::prioritizedTypeKeys(
        $type,
        $fallbackType,
        $tryLowercaseFallback,
        $fallbackBarePrefix,
        $fallbackUnprefixedType
      ) as $typeVariant
    ) {
      foreach ($aliases as $alias) {
        foreach (self::keyVariants($alias, $tryLowercaseFallback) as $aliasVariant) {
          $value = static::_($typeVariant, $aliasVariant);
          if ($value !== null && $value !== false) {
            return [
              'value' => $value,
              'type'  => $typeVariant,
              'alias' => $aliasVariant,
            ];
          }
        }
      }
    }

    return null;
  }

  private static function cast(mixed $value, mixed $default): mixed
  {
    if (is_bool($default)) {
      // Normalize using shared "boolish" cast.
      // Supports "hybrid bool" configs: 0/1 -> bool, any other numeric value -> int threshold.
      return Service::cast('boolish')->get($value, ['strictBool' => false]);
    }

    if (is_int($default)) {
      return (int)$value;
    }

    if (is_float($default)) {
      return (float)$value;
    }

    if (is_array($default) || is_object($default)) {
      if (is_string($value)) {
        $decoded = json_decode($value, true);

        return $decoded === null ? $default : $decoded;
      }

      return $default;
    }

    return $value;
  }
}

