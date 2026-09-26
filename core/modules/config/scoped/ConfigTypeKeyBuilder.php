<?php

namespace AC\core\modules\config\scoped;

use AC\core\modules\areas\entities\dto\AreaTypeDto;
use AC\core\system\modules\modComm\helpers\ModCommHelper;

/**
 * Единая сборка ключа config.type: {prefix}|{aliasPart}[_{sport}][_{area}].
 *
 * Хвост после «|» одинаков для всех политик (double, restriction, paypal, …):
 * current_alias из AreaTypeDto + sport_id + area_id при наличии.
 */
final class ConfigTypeKeyBuilder
{
  /** @var array<int, AreaTypeDto|null> */
  private static array $typeByIdCache = [];

  /**
   * Собирает config.type из prefix и scope (typeId, sportId, areaId).
   *
   * Примеры: restriction|close, restriction|close_10_7, double|close_10_7_5, paypal|close_10_7.
   */
  public function build(ScopedConfigQuery $query): string
  {
    $prefix  = trim($query->prefix);
    $typeId  = $query->typeId ?? 0;
    $sportId = $query->sportId ?? 0;
    $areaId  = $query->areaId ?? 0;

    $rest = $this->buildScopeRest($typeId, $sportId, $areaId);

    if ($prefix === '') {
      return $rest ?? 'default';
    }

    if ($rest === null || $rest === '') {
      return $prefix;
    }

    if ($rest === $prefix || str_starts_with($rest, $prefix . '_')) {
      return $rest;
    }

    return $prefix . '|' . $rest;
  }

  /**
   * Единый хвост ключа: {current_alias}[_{sport}][_{area}].
   */
  private function buildScopeRest(int $typeId, int $sportId, int $areaId): ?string
  {
    if ($typeId <= 0) {
      return null;
    }

    $aliasPart = $this->typeKeyAliasPart($typeId);
    if ($aliasPart === null || $aliasPart === '') {
      return null;
    }

    return $this->appendSportAreaSegments($aliasPart, $sportId, $areaId);
  }

  /**
   * После aliasPart (close или close_{typeId}) — sport и area при наличии.
   */
  private function appendSportAreaSegments(string $aliasPart, int $sportId, int $areaId): string
  {
    $parts = [$aliasPart];
    if ($sportId > 0) {
      $parts[] = (string)$sportId;
    }
    if ($areaId > 0) {
      $parts[] = (string)$areaId;
    }

    return implode('_', $parts);
  }

  /** close vs close_{typeId} — AreaTypeDto::getCurrentAlias(). */
  private function typeKeyAliasPart(int $typeId): ?string
  {
    $type = $this->typeById($typeId);
    if (!($type instanceof AreaTypeDto)) {
      return null;
    }

    $alias = trim($type->getCurrentAlias());

    return $alias !== '' ? $alias : null;
  }

  /** Кэш AreaTypeDto по type_id (request-scope). */
  private function typeById(int $typeId): ?AreaTypeDto
  {
    if ($typeId <= 0) {
      return null;
    }

    if (array_key_exists($typeId, self::$typeByIdCache)) {
      return self::$typeByIdCache[$typeId];
    }

    $type                         = ModCommHelper::get('areas', 'areasType/getAreaType', ['type_id' => $typeId, 'asDto' => true], 'data');
    self::$typeByIdCache[$typeId] = $type instanceof AreaTypeDto ? $type : null;

    return self::$typeByIdCache[$typeId];
  }

  /** @internal Для unit-тестов: подставить AreaTypeDto без БД. */
  public static function seedTypeForTest(int $typeId, AreaTypeDto $type): void
  {
    self::$typeByIdCache[$typeId] = $type;
  }

  /** @internal Для unit-тестов: сброс кэша типов. */
  public static function resetTypeCacheForTest(): void
  {
    self::$typeByIdCache = [];
  }
}
