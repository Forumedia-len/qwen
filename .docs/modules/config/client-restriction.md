# Ограничения клиента (Client Restriction)

## Обзор

Документ описывает домен **ограничений клиента** при бронировании:

- лимиты активных бронирований (`RESERVATION_LIMIT`);
- запрет брони по клубному статусу (`POSSIBILITY_BOOKING`);
- хранение в `config` (фаза 1) и таблице `client_restriction` (фаза 2);
- интеграцию с `OrdersModelReservation` и legacy `TableRules`.

Общая инфраструктура ключей и gateway: [scoped-config.md](scoped-config.md).

План задач: [client-restriction-implementation.md](../../tasks/client-restriction-implementation.md).

---

## Проблема

### Текущее состояние (до рефакторинга)

Конфиг описывал ограничения в PHP-массиве:

```
[тип_ограничения => [alias_корта => [club_mark => параметры]]]
```

Значения читались как `Service::configDB('reservation', $alias)`.

**Ограничения подхода:**

1. Ключ по **alias корта**, не по `type_id` — два `close` не разделить.
2. Клубное членство в PHP, не в ключе БД.
3. Дублирование с `club_reservation_rules` / `TableRules`.
4. `limit=0` ломался через truthy-проверку.

### Контекст эксплуатации

- **200+ сайтов**, своя БД на инсталляцию.
- Мигратор схемы в зачаточном состоянии — **без ALTER** на всех БД.
- `club_reservation_rules` не подходит для новых правил (см. ниже).

---

## Целевое хранение

### Фаза 1: таблица `config`

- Только `INSERT`, prefix `restriction`.
- Синхронизация через существующий `mapi`.

```
type  = restriction|close_10_7_10
alias = reservation_limit__club_rate2
value = 2
```

Формат `type` — общий scoped-конфиг: [scoped-config.md](scoped-config.md#формат-ключа-type).

### Фаза 2: таблица `client_restriction`

После появления мигратора — второй source в gateway; обёртки L4 не меняются.

```sql
CREATE TABLE {prefix}client_restriction (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restriction_type VARCHAR(64)  NOT NULL,
  type_id          INT UNSIGNED NULL,
  sport_id         INT UNSIGNED NULL,
  area_id          INT UNSIGNED NULL,
  club_state_id    INT UNSIGNED NULL,
  device           VARCHAR(16)   NULL,
  value_int        INT           NULL,
  value_bool       TINYINT(1)    NULL,
  priority         SMALLINT      NOT NULL DEFAULT 100,
  active           TINYINT(1)    NOT NULL DEFAULT 1,
  UNIQUE KEY uq_scope (
    restriction_type, type_id, sport_id, area_id, club_state_id, device
  )
);
```

---

## Клубное членство

**Членство — в `alias`, не в `type`.**

Причина: каскад режет `type` по `_`; при fallback теряется контекст клуба.

То же для alias площадки с `_` в имени (`mc_arena`): селектор режет каждый `_`, после `restriction|mc_arena` пробуется `restriction|mc` — подробнее в [scoped-config.md](scoped-config.md) (раздел «Алиас с подчёркиванием»).

**Каскад alias:**

1. `reservation_limit__club_rate2`
2. `reservation_limit` (общее для scope площадки)

Справочник: `config_club_state` / `ConfigClubStateModel` (`id`, `mark`).

### Типы ограничений

| Константа | Смысл | Пример alias |
|-----------|-------|--------------|
| `RESERVATION_LIMIT` | Лимит бронирований | `reservation_limit` |
| `POSSIBILITY_BOOKING` | Разрешено ли бронирование | `possibility_booking` |

---

## Почему не `club_reservation_rules`

| Критерий | `club_reservation_rules` | `config` (фаза 1) | `client_restriction` (фаза 2) |
|----------|--------------------------|-------------------|-------------------------------|
| Без миграций на 200+ БД | ✅ | ✅ INSERT | ❌ сейчас |
| Опциональные правила | ⚠️ | ✅ нет строки + `adminRequiresDbRow=true` → не применяем; `defaultValue` при `false` | ✅ |
| Несколько `close` | через type_id | ✅ type-ключ | ✅ |
| Явный приоритет | ❌ | ✅ каскад | ✅ `priority` |

Новые ограничения **не** добавлять в `TableRules`.

---

## RestrictionContext

VO контекста для L4/L5:

```php
final class RestrictionContext
{
  public function __construct(
    public readonly ?int $typeId = null,
    public readonly ?int $sportId = null,
    public readonly ?int $areaId = null,
    public readonly ?int $clubStateId = null,
    public readonly ?string $clubMark = null,
    public readonly ?string $device = null,
    public readonly ?string $courtTypeAlias = null,  // только для $rules, не для type-ключа
  ) {}

  public function toQuery(string $dbAlias, bool $nullIfMissing = true): ScopedConfigQuery;
}
```

`courtTypeAlias` — проверка PHP `$rules['courtAliases']`, **не** сборка type-ключа.

Фабрики: `ClientRestrictionConfig::fromReservationModel()` (L5), `RestrictionContext::fromCourtAliasAndClub()` (админка, тесты).

---

## Декларация правил на сайте (PHP)

```php
private array $rules = [
  ClientRestrictionConfig::RESERVATION_LIMIT => [
    'courtAliases'       => ['close'],
    'clubMarks'          => ['club_rate2'],
    'dbAlias'            => 'reservation_limit',
    'typeValue'          => 'int',
    'adminRequiresDbRow' => true,   // поле в админке и значение — только при строке в БД
  ],
  ClientRestrictionConfig::POSSIBILITY_BOOKING => [
    'courtAliases'       => ['close'],
    'clubMarks'          => ['no_club_rate'],
    'dbAlias'            => 'possibility_booking',
    'typeValue'          => 'int',
    'adminRequiresDbRow' => false,  // поле в админке всегда; без строки — defaultValue
    'defaultValue'       => ClientRestrictionConfig::BOOKING_ALLOWED, // или BOOKING_STOPPED — на усмотрение сайта
  ],
];
```

Проверка scope: тип в `$rules` → alias корта → `clubMark`.

### Параметры правила

| Ключ | Смысл |
|------|-------|
| `courtAliases` | Alias типа площадки (`close`, …) |
| `clubMarks` | Mark клубного статуса (`club_rate2`, `no_club_rate`, …) |
| `dbAlias` | Базовый alias в `config` (`reservation_limit`, `possibility_booking`) |
| `typeValue` | Приведение значения (`int`) |
| `adminRequiresDbRow` | `true` — правило/поле только при строке в БД; `false` — всегда активно |
| `defaultValue` | Значение при `adminRequiresDbRow=false` и отсутствии строки в БД; без ключа — `null` |

Значения `possibility_booking`: `BOOKING_ALLOWED` (`1`) — бронь разрешена, `BOOKING_STOPPED` (`0`) — запрет.

---

## Семантика чтения

`getRestrictionValue()` — единая точка чтения для всех типов ограничений:

1. Строка в БД найдена → значение из gateway (с `castValue`).
2. Строки нет, `adminRequiresDbRow=false` и задан `defaultValue` → `defaultValue`.
3. Иначе → `null` (параметр не используется).

| Ситуация | `adminRequiresDbRow` | `defaultValue` | Результат `getRestrictionValue()` |
|----------|----------------------|----------------|-----------------------------------|
| Нет строки в БД | `true` | — | `null` → L5 fallback (`TableRules`, константы) |
| Нет строки в БД | `false` | задан | значение `defaultValue` |
| Нет строки в БД | `false` | не задан | `null` |
| `limit = 0` в БД | любой | — | `0` (валидное значение, не теряется) |

Поля ограничения выводятся в админке только для активных типов площадок с alias из `courtAliases`.
Если подходящих активных типов нет, параметры правила не выводятся. Для найденных типов дополнительно применяется
`useRestriction()`: при `adminRequiresDbRow=true` поле показывается только если есть строка в БД; при `false` — всегда.

`isBookingAllowed()`: при `null` (правило не в scope или нет значения) → `true`; иначе сравнение с `BOOKING_ALLOWED`.

### Приоритет лимита (L5)

1. `ClientRestrictionConfig::resolveReservationLimit()` (если не `null`)
2. `TableRules` / `club_reservation_rules`
3. `$max_count_reservation`

---

## Интеграция с бронированием

```php
$ctx = config('clientRestriction')->fromReservationModel($this, $device_type);

if (config('clientRestriction')->isBookingDenied($ctx)) {
  return false;
}

$configLimit = config('clientRestriction')->resolveReservationLimit($ctx);
$max_count_reservation = $configLimit ?? $rule['reservation_limit'] ?? $this->max_count_reservation;
```

**Файлы:** `OrdersModelReservation.php`, `ClientRestrictionConfig.php`.

---

## Примеры данных в `config`

| type | alias | value | Смысл |
|------|-------|-------|-------|
| `restriction\|close_10` | `reservation_limit__club_rate2` | `2` | Лимит 2, club_rate2 |
| `restriction\|close` | `reservation_limit__club_rate2` | `1` | Fallback все close |
| `restriction\|close_10_7` | `possibility_booking__no_club_rate` | `0` | Запрет no_club (явно в БД) |

Без строки для `possibility_booking` на `close` / `no_club_rate` действует `defaultValue` из PHP `$rules` (см. декларацию правил).

---

## API `ClientRestrictionConfig`

```php
public function fromReservationModel(object $model, ?string $device = null): RestrictionContext;
public function hasRule(string $restrictionType, RestrictionContext $ctx): bool;
public function useRestriction(string $restrictionType, RestrictionContext $ctx): bool;
public function getRestrictionValue(string $restrictionType, RestrictionContext $ctx): mixed;
public function resolveReservationLimit(RestrictionContext $ctx, ?int $defaultLimit = null): ?int;
public function isBookingAllowed(RestrictionContext $ctx): bool;
public function isBookingDenied(RestrictionContext $ctx): bool;
public function getDbTypeKey(RestrictionContext $ctx, string $restrictionType): string;
public function getDbAlias(string $restrictionType, string $clubMark): string;
```

---

## Карта файлов

| Файл | Роль |
|------|------|
| `app/config/ClientRestrictionConfig.php` | L4 обёртка, фабрика `fromReservationModel()` |
| `core/modules/config/contexts/RestrictionContext.php` | VO контекста |
| `core/modules/config/views/admin/default/index.php` | Админка restriction |
| `core/modules/reservations/models/OrdersModelReservation.php` | Проверки при бронировании |
| `core/engines/TableRules.php` | Legacy fallback |

---

## Опциональность (три уровня)

| Уровень | Где | Поведение |
|---------|-----|-----------|
| Сайт | PHP `$rules` | Тип объявлен на инсталляции |
| Админка | `useRestriction()` | `adminRequiresDbRow=true` — поле только при строке в БД; `false` — всегда |
| БД | `getRestrictionValue()` | Нет строки + нет `defaultValue` → `null`, fallback в L5; есть `defaultValue` при `adminRequiresDbRow=false` → дефолт |

---

## Чеклист

- [ ] Не добавлять правила в `club_reservation_rules`.
- [ ] Клуб — в alias chain (`__clubMark`), не в type.
- [ ] `defaultValue` только при `adminRequiresDbRow=false`; иначе нет строки → `null`.
- [ ] `limit=0` — валидное значение.
- [ ] Админка write: `restriction|…[reservation_limit__club_rate2]`.

---

## Связанные документы

- [scoped-config.md](scoped-config.md) — gateway, ключи, политики
- [client-restriction-implementation.md](../../tasks/client-restriction-implementation.md) — PR-4…PR-5, PR-7

---

## История

- 2025-06 — рефакторинг на `ScopedConfigGateway`, prefix `restriction`.
- 2026-06-26 — унификация type-ключа с общим `ConfigTypeKeyBuilder`.
- 2026-06-26 — `defaultValue` / `adminRequiresDbRow` в `$rules`; единая семантика `getRestrictionValue()`.

---

*Документ актуален для worktree `dev/`.*
