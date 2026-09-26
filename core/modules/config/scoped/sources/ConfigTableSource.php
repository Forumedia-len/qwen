<?php

namespace AC\core\modules\config\scoped\sources;

use AC\core\modules\config\models\ConfigDbValueSelector;
use AC\core\modules\config\scoped\ScopedConfigQuery;
use AC\core\modules\config\scoped\ScopedConfigResult;

/**
 * Чтение из таблицы {prefix}config с каскадом type и alias.
 */
final class ConfigTableSource implements ConfigSourceInterface
{
  public const SOURCE_NAME = 'config';

  /** @var class-string<ConfigDbValueSelector> */
  private string $selectorClass;

  /**
   * @param class-string<ConfigDbValueSelector> $selectorClass
   */
  public function __construct(string $selectorClass = ConfigDbValueSelector::class)
  {
    $this->selectorClass = $selectorClass;
  }

  /**
   * Читает из {prefix}config с каскадом type и alias через ConfigDbValueSelector.
   *
   * @param list<string> $aliasChain
   */
  public function read(string $typeKey, array $aliasChain, ScopedConfigQuery $query): ?ScopedConfigResult
  {
    if ($aliasChain === []) {
      $aliasChain = [trim($query->alias)];
    }

    $aliasChain = array_values(array_filter(array_map('trim', $aliasChain), static fn(string $a) => $a !== ''));
    if ($aliasChain === []) {
      return null;
    }

    $policy         = $query->policy;
    $match          = $this->selectorClass::resolveFirst(
      $typeKey,
      $aliasChain,
      $policy->fallbackType(),
      $policy->tryLowercaseFallback(),
      $policy->fallbackBarePrefix(),
      $policy->fallbackUnprefixedType(),
    );

    if ($match === null) {
      return null;
    }

    return ScopedConfigResult::fromSource(
      $match['value'],
      $match['type'],
      $match['alias'],
      self::SOURCE_NAME,
    );
  }
}
