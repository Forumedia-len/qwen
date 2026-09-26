<?php

namespace AC\core\modules\config\scoped\traits;

use AC\core\modules\config\models\ConfigDbValueSelector;
use AC\core\modules\config\scoped\ScopedConfigQuery;
use Service;

/**
 * DB → const → default на L4 после ScopedConfigGateway.
 */
trait ScopedConfigConstFallbackTrait
{
  protected function resolveInt(ScopedConfigQuery $query, string $constName, int $default): int
  {
    $result = Service::scopedConfig()->resolve($query);
    if ($result->found) {
      return $result->asInt($default) ?? $default;
    }

    return defined($constName) ? (int)constant($constName) : $default;
  }

  protected function resolveBool(ScopedConfigQuery $query, string $constName, bool $default): bool
  {
    $result = Service::scopedConfig()->resolve($query);
    if ($result->found) {
      return $result->asBool($default) ?? $default;
    }

    return defined($constName) ? (bool)constant($constName) : $default;
  }

  protected function resolveOption(ScopedConfigQuery $query, mixed $default): mixed
  {
    $result = Service::scopedConfig()->resolve($query);
    if (!$result->found) {
      return $default;
    }

    return ConfigDbValueSelector::normalizeResolvedValue($result->value, $default);
  }
}
