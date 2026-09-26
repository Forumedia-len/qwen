<?php

namespace AC\core\modules\config\scoped;

interface ScopedConfigGatewayInterface
{
  /**
   * Чтение по ScopedConfigQuery: сборка type-ключа, каскад, все источники политики.
   */
  public function resolve(ScopedConfigQuery $query): ScopedConfigResult;

  /**
   * Чтение по уже собранному type-ключу (фасады L4: DoubleGameDbConfigModel и т.д.).
   *
   * @param list<string> $aliasChain Цепочка alias для каскада (если пусто — один alias)
   */
  public function resolvePrebuilt(
    string $typeKey,
    string $alias,
    ScopedConfigPolicy $policy,
    array $aliasChain = [],
  ): ScopedConfigResult;
}
