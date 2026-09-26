# Задачи: ScopedConfigGateway (ядро и домены double / OpenType / payment)

**Статус:** PR-1…PR-3 ✅ завершены  
**Приоритет:** high  
**Создана:** 2025-06-24  
**Обновлена:** 2026-06-26  
**Теги:** config, scoped-config, refactoring, architecture

## Связанная документация

- [scoped-config.md](../../modules/config/scoped-config.md) — архитектура, контракты, ключи
- [client-restriction-implementation.md](../../tasks/client-restriction-implementation.md) — PR-4…PR-7 (restriction)
- [development/testing.md](../../development/testing.md), [tests/README.md](../../../../tests/README.md)

---

## Цель

Единая точка входа `ScopedConfigGateway` для чтения scoped-настроек из `config`:

- `ConfigTypeKeyBuilder` — один хвост `{current_alias}[_{sport}][_{area}]` для всех prefix;
- миграция `DoubleGameDbConfigModel`, `OpenTypeDbConfigModel`, payment read;
- доменные `DoubleGameConfig`, `OpenTypeConfig` на gateway.

Ограничения клиента — [client-restriction-implementation.md](../../tasks/client-restriction-implementation.md).

---

## Прогресс

| PR | Содержание | Статус |
|----|------------|--------|
| **PR-1** | `scoped/*`, `ConfigTypeKeyBuilder`, `ConfigTableSource`, unit-тесты | ✅ |
| **PR-2** | `*DbConfigModel`, payment read → gateway | ✅ |
| **PR-3** | `DoubleGameConfig`, `OpenTypeConfig`, traits | ✅ |

---

## Диагноз (scoped-часть)

| Компонент | Проблема |
|-----------|----------|
| `ConfigTypeKeyTrait` | **@deprecated** — legacy type_id отдельным сегментом |
| `BindingConfigTypeHelper` | Дублирование логики ключей |
| `ConfigDbValueSelector` | Разные флаги в `*DbConfigModel` |
| `DoubleGameDbConfigModel` / `OpenTypeDbConfigModel` | Прямой вызов селектора |

---

## Архитектура (кратко)

| Слой | Ответственность |
|------|-----------------|
| L1 | `ScopedConfigQuery`, `ScopedConfigPolicy` |
| L2 | `ScopedConfigGateway`, `ConfigTypeKeyBuilder` |
| L3 | `ConfigTableSource` |
| L4 | `*Config`, `*DbConfigModel` |

```
core/modules/config/scoped/
├── ScopedConfigQuery.php
├── ScopedConfigResult.php
├── ScopedConfigPolicy.php
├── ScopedConfigGateway.php
├── ConfigTypeKeyBuilder.php
├── AliasChainResolver.php
└── sources/ConfigTableSource.php
```

`Service::scopedConfig(): ScopedConfigGatewayInterface`

Подробнее: [scoped-config.md](../../modules/config/scoped-config.md).

---

## ConfigTypeKeyBuilder

```
{prefix}|{current_alias}[_{sportId}][_{areaId}]
```

- `AreaTypeDto::getCurrentAlias()` по `typeId`;
- policy **не** влияет на ключ;
- кэш DTO — request-scope static.

**Миграция:** аудит legacy ключей `double|open_10_7_10` per-site.

---

## Цепочка разрешения (gateway vs L4)

Gateway — только БД (`config`), без default и const.

| Домен | Gateway | L4 после `found=false` |
|-------|---------|------------------------|
| `double` | `config` | const → default |
| `open` / `options_*` | `config` | default аргумента |
| `paypal` / `payone` | `config` | default / masked |

```php
protected function resolveInt(ScopedConfigQuery $query, string $constName, int $default): int
{
  $result = Service::scopedConfig()->resolve($query);
  if ($result->found) {
    return $result->asInt($default);
  }
  return defined($constName) ? (int) constant($constName) : $default;
}
```

---

## Стратегия тестирования

| Уровень | Что | PR | Статус |
|---------|-----|-----|--------|
| Unit | `ConfigTypeKeyBuilder` — единый хвост для всех policy | PR-1 | ✅ |
| Unit | `AliasChainResolver`, `ScopedConfigPolicy`, `ScopedConfigResult` | PR-1 | ✅ |
| Unit | `ConfigTableSourceCascadeTest` | PR-1 | ✅ |
| Characterization | Golden DOUBLE/OPEN vs селектор | PR-2 | ❌ |

**Проверено 2026-06-26:** `codecept run unit ScopedConfig` — 19 tests OK.

---

## Этап 1.1 — Ядро (PR-1)

| # | Задача | Статус |
|---|--------|--------|
| 1.1 | `ScopedConfigQuery`, `ScopedConfigResult`, `ScopedConfigPolicy` | ✅ |
| 1.2 | `ConfigTypeKeyBuilder` + кэш `AreaTypeDto` | ✅ |
| 1.3 | `AliasChainResolver` | ✅ |
| 1.4 | `ConfigSourceInterface` + `ConfigTableSource` | ✅ |
| 1.5 | `ScopedConfigGateway` + `Service::scopedConfig()` | ✅ |
| 1.6 | Unit-тесты каскада | ✅ |
| 1.7 | Deprecated `ConfigTypeKeyTrait` / `BindingConfigTypeHelper` | ✅ |

Критерии PR-1:

- [x] `testScopeRestIsSameForAllPolicies`
- [x] Формат = `getCurrentAlias()` + scope

---

## Этап 1.2 — DbConfigModel (PR-2)

| # | Задача | Статус |
|---|--------|--------|
| 2.1 | `DoubleGameDbConfigModel` → gateway | ✅ |
| 2.2 | `OpenTypeDbConfigModel` → gateway | ✅ |
| 2.3a | Payment `ProfileContextModel::readField()` | ✅ |
| 2.3b | `PaymentProfile::priorityTypeKey()` без изменений | ✅ |

- [x] `ScopedConfigGateway::resolvePrebuilt()`
- [ ] Регрессия characterization fixtures

---

## Этап 1.3 — Доменные конфиги (PR-3)

| # | Задача | Статус |
|---|--------|--------|
| 3.1 | `ScopedConfigConstFallbackTrait` | ✅ |
| 3.1b | `ScopedConfigScopeTrait` | ✅ |
| 3.2 | `DoubleGameConfig` → gateway | ✅ |
| 3.3 | `OpenTypeConfig` → gateway | ✅ |

---

## Риски

| Риск | Митигация |
|------|-----------|
| Два билдера ключей | Единый `ConfigTypeKeyBuilder` |
| Регрессия double/OpenType | Аудит legacy type-ключей per-site |

---

## Definition of Done (scoped)

- [x] `ConfigTypeKeyBuilder` — единый хвост; policy только для каскада
- [x] Gateway не подставляет default / const
- [x] Unit-тесты ScopedConfig проходят
- [~] `*DbConfigModel` — фасады gateway
- [~] Double/OpenType через `ScopedConfigScopeTrait`

---

## Вне scope

- `client_restriction` DDL (PR-7) — см. [client-restriction-implementation.md](../../tasks/client-restriction-implementation.md)
- ConstSource в gateway
- Registry доменов (вариант D)

---

## История

| Дата | Событие |
|------|---------|
| 2025-06-24 | План PR-1, ConfigTypeKeyBuilder в ядре |
| 2026-06-24 | PR-1…PR-3 завершены |
| 2026-06-26 | Унификация ключа; документ разделён с client-restriction |

---

*Связанный архивный индекс: [scoped config и client restriction](scoped-config-routing-index.md).*
