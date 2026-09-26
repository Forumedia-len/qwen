<?php

namespace AC\core\modules\config\scoped;

/**
 * Цепочка alias для каскадного чтения (RESTRICTION: param__clubMark → param).
 */
final class AliasChainResolver
{
  /**
   * Строит цепочку alias для каскадного чтения.
   *
   * RESTRICTION: [reservation_limit__club_rate2, reservation_limit].
   * Остальные policy: один alias без fallback.
   *
   * @return list<string>
   */
  public function chain(ScopedConfigQuery $query): array
  {
    $alias = trim($query->alias);
    if ($alias === '') {
      return [];
    }

    if (!$query->policy->usesAliasChain()) {
      return [$alias];
    }

    $clubMark = trim((string)$query->dimension('clubMark', ''));
    if ($clubMark === '') {
      return [$alias];
    }

    $specific = $alias . '__' . $clubMark;

    return $specific === $alias ? [$alias] : [$specific, $alias];
  }
}
