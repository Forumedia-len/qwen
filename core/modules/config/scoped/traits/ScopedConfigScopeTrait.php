<?php

namespace AC\core\modules\config\scoped\traits;

use AC\core\modules\config\scoped\ConfigTypeKeyBuilder;
use AC\core\modules\config\scoped\ScopedConfigPolicy;
use AC\core\modules\config\scoped\ScopedConfigQuery;
use Service;

/**
 * Сборка ScopedConfigQuery по scope площадки (L4).
 */
trait ScopedConfigScopeTrait
{
  protected function buildScopedConfigQuery(
    string $alias,
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default',
    ?string $correlationPrefix = null,
    bool $resolveTypeAliasFromTypeId = true,
  ): ScopedConfigQuery {
    [$typeId, $sportId, $areaId] = $this->resolveScopeIds($typeId, $sportId, $areaId);

    $prefix = $correlationPrefix !== null ? trim($correlationPrefix) : '';

    return new ScopedConfigQuery(
      prefix: $prefix,
      alias: trim($alias),
      typeId: $typeId > 0 ? $typeId : null,
      sportId: $sportId > 0 ? $sportId : null,
      areaId: $areaId > 0 ? $areaId : null,
      policy: $this->scopedPolicyForPrefix($prefix),
      dimensions: [
        'fallbackTypeAlias'          => trim($fallbackTypeAlias) !== '' ? trim($fallbackTypeAlias) : 'default',
        'resolveTypeAliasFromTypeId' => $resolveTypeAliasFromTypeId,
      ],
      nullIfMissing: true,
    );
  }

  protected function buildScopedTypeKey(
    string $prefix,
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default',
  ): string {
    return (new ConfigTypeKeyBuilder())->build(
      $this->buildScopedConfigQuery('', $typeId, $sportId, $areaId, $fallbackTypeAlias, $prefix),
    );
  }

  /**
   * @return array{0: int, 1: int, 2: int}
   */
  protected function resolveScopeIds(?int $typeId, ?int $sportId, ?int $areaId): array
  {
    if ($typeId === null) {
      $typeId = (int)Service::request()->_('type_id', 0);
    }
    if ($sportId === null) {
      $sportId = (int)Service::request()->_('sport_id', 0);
    }
    if ($areaId === null) {
      $areaId = (int)Service::request()->_('area_id', 0);
    }

    return [$typeId, $sportId, $areaId];
  }

  protected function scopedPolicyForPrefix(string $prefix): ScopedConfigPolicy
  {
    if ($prefix === 'double') {
      return ScopedConfigPolicy::DOUBLE;
    }

    if (str_starts_with($prefix, 'options_')) {
      return ScopedConfigPolicy::OPEN_TYPE;
    }

    return ScopedConfigPolicy::OPEN_TYPE;
  }
}
