<?php

namespace AC\core\modules\config\scoped;

use AC\core\modules\config\scoped\sources\ConfigSourceInterface;
use AC\core\modules\config\scoped\sources\ConfigTableSource;
use AC\core\system\db\Query;

/**
 * Единая точка входа чтения scoped-настроек из БД (L2).
 *
 * Собирает type-ключ и цепочку alias, перебирает источники (config, …).
 * Не подставляет default и не читает PHP-константы — только found=true/false из storage.
 */
final class ScopedConfigGateway implements ScopedConfigGatewayInterface
{
  private ConfigTypeKeyBuilder $typeKeyBuilder;
  private AliasChainResolver $aliasResolver;
  private ConfigTableSource $configTableSource;

  /** @var bool|null */
  private static ?bool $clientRestrictionTableExistsCache = null;

  public function __construct(
    ?ConfigTypeKeyBuilder $typeKeyBuilder = null,
    ?AliasChainResolver $aliasResolver = null,
    ?ConfigTableSource $configTableSource = null,
  ) {
    $this->typeKeyBuilder           = $typeKeyBuilder ?? new ConfigTypeKeyBuilder();
    $this->aliasResolver            = $aliasResolver ?? new AliasChainResolver();
    $this->configTableSource        = $configTableSource ?? new ConfigTableSource();
  }

  /**
   * Читает значение по полному запросу: сборка ключа, каскад type×alias, перебор sources.
   *
   * Используют L4-обёртки (ClientRestrictionConfig, DoubleGameConfig, …).
   */
  public function resolve(ScopedConfigQuery $query): ScopedConfigResult
  {
    $typeKey    = $this->typeKeyBuilder->build($query);
    $aliasChain = $this->aliasResolver->chain($query);

    foreach ($this->sourcesFor($query) as $source) {
      $result = $source->read($typeKey, $aliasChain, $query);
      if ($result !== null && $result->found) {
        return $result;
      }
    }

    return ScopedConfigResult::notFound();
  }

  /**
   * Чтение по уже собранному config.type (без повторной сборки ключа из scope).
   *
   * Для фасадов L4, где type-ключ собирается заранее (DoubleGameDbConfigModel, OpenTypeDbConfigModel).
   * Обходит только ConfigTableSource, без client_restriction.
   */
  public function resolvePrebuilt(
    string $typeKey,
    string $alias,
    ScopedConfigPolicy $policy,
    array $aliasChain = [],
  ): ScopedConfigResult {
    $typeKey = trim($typeKey);
    if ($typeKey === '') {
      return ScopedConfigResult::notFound();
    }

    if ($aliasChain === []) {
      $aliasChain = [trim($alias)];
    }

    $query = new ScopedConfigQuery(
      prefix: self::prefixFromTypeKey($typeKey),
      alias: trim($alias),
      policy: $policy,
    );

    $result = $this->configTableSource->read($typeKey, $aliasChain, $query);

    return $result ?? ScopedConfigResult::notFound();
  }

  /**
   * Извлекает prefix из готового type-ключа (часть до «|»).
   */
  private static function prefixFromTypeKey(string $typeKey): string
  {
    $pos = strpos($typeKey, '|');

    return $pos === false ? $typeKey : trim(substr($typeKey, 0, $pos));
  }

  /**
   * Порядок источников для запроса.
   *
   * restriction: [client_restriction (фаза 2)] → config.
   * double/open/paypal: только config.
   *
   * @return list<ConfigSourceInterface>
   */
  private function sourcesFor(ScopedConfigQuery $query): array
  {
    $sources = [];

    if ($query->prefix === 'restriction' && $this->clientRestrictionTableExists()) {
      // ClientRestrictionTableSource — фаза 2 (PR-7).
    }

    $sources[] = $this->configTableSource;

    return $sources;
  }

  /**
   * Кэш наличия таблицы client_restriction (request-scope) для фазы 2.
   */
  private function clientRestrictionTableExists(): bool
  {
    if (self::$clientRestrictionTableExistsCache !== null) {
      return self::$clientRestrictionTableExistsCache;
    }

    self::$clientRestrictionTableExistsCache = Query::getDB()->checkTable('client_restriction');

    return self::$clientRestrictionTableExistsCache;
  }
}
