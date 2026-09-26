<?php

namespace AC\core\modules\config\scoped\sources;

use AC\core\modules\config\scoped\ScopedConfigQuery;
use AC\core\modules\config\scoped\ScopedConfigResult;

interface ConfigSourceInterface
{
  /**
   * Читает значение из одного storage.
   *
   * @param string       $typeKey    Собранный config.type
   * @param list<string> $aliasChain Цепочка alias для каскада (см. AliasChainResolver)
   * @return null|ScopedConfigResult null — источник не применим; notFound — через null или found=false
   */
  public function read(string $typeKey, array $aliasChain, ScopedConfigQuery $query): ?ScopedConfigResult;
}
