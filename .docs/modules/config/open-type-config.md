# OpenTypeConfig — настройки открытых кортов

Общий обзор бронирования open (контроллеры, join, проверки): [open-courts.md](../reservations/open-courts.md).

## Обзор

`OpenTypeConfig` (`core/modules/reservations/config/OpenTypeConfig.php`) — L4-фасад для чтения scoped-настроек открытых площадок: система цен, опции PFP/GT/…, матрица «кто с кем может играть».

Общая инфраструктура ключей и gateway: [scoped-config.md](scoped-config.md).

Админка `config.php?mode=open_type` использует `ConfigOpenTypeModel` как тонкий слой записи в БД и делегирует чтение в `config('OpenType')`.

---

## Разделение ответственности

```
Админка                         Весь проект
─────────                       ───────────
ConfigOpenTypeControllerAdmin   areas.php, OrdersModelOpenReservation, …
        ↓                               ↓
ConfigOpenTypeModel             config('OpenType')
  • вкладки type_id               • чтение через ScopedConfigGateway
  • saveItem() в БД               • каскад open|open_3_7 → open|open_3 → open
  • JSON из форм                  • checkCombination(), getCombinationsOfPlayers()
        └──────────────┬──────────────┘
                       ↓
                OpenTypeConfig
```

| Слой | Читает | Пишет | Scope |
|------|--------|-------|-------|
| `OpenTypeConfig` | да | нет | `typeId`, `sportId`, `areaId` |
| `ConfigOpenTypeModel` | через конфиг | да (`ConfigEngine`) | в admin: `sportId=0`, `areaId=0` |

---

## Параметры в таблице `config`

| Alias | Prefix type | Описание |
|-------|-------------|----------|
| `type_pricing_system` | `open\|…` | ST / STP / GT / PFP / PV |
| `guest_as_no_member` | `options_PFP\|…` | опции системы цен |
| `main_no_member_price_for_no_member` | `options_PFP\|…` | опции системы цен |
| `double_count_other_players_only` | `options_PFP\|…` | PFP: для двойной игры строки цен содержат только дополнительных игроков |
| `who_can_play_with_whom` | `open\|…` | JSON-матрица клубных статусов |

Формат ключа `type`: `{prefix}|{current_alias}[_{sport}][_{area}]` — см. [scoped-config.md#формат-ключа-type](scoped-config.md#формат-ключа-type).

Примеры:

```
open|open
open|open_3
open|open_7_10
options_PFP|open_3
double|open_3
```

---

## Scope: type_id, sport_id, area_id

Все публичные методы принимают необязательные `?int $typeId`, `?int $sportId`, `?int $areaId`.

Если аргумент `null`, значение берётся из `Service::request()` (`type_id`, `sport_id`, `area_id`).

**Админка** передаёт явно `sportId=0`, `areaId=0` — настройка на уровне типа площадки (вкладка), без привязки к спорту/корту.

**Runtime** (бронирование, цены) передаёт полный scope корта.

---

## Комбинации игроков (`who_can_play_with_whom`)

### Чтение

```php
config('OpenType')->getCombinationsOfPlayers($typeId, $sportId, $areaId);
// → ['players' => ClubStateDto…, 'use' => [mainId => [otherId => 0|1]]]
```

1. `getWhoCanPlayWithWhom()` — JSON из БД через gateway (`prefix: open`, каскад вниз к `open`).
2. `buildCombinationsMatrix()` — список клубных статусов + матрица чекбоксов.
3. Пустой JSON / нет записи → все пары разрешены (`use === []` → checkboxes checked).

### Проверка при бронировании

```php
config('OpenType')->checkCombination(
  $mainClubStateId,
  $otherClubStateId,
  $typeId,
  $sportId,
  $areaId,
);
```

### Сохранение (только админка)

```php
// ConfigOpenTypeModel::setCombinationsOfPlayers()
$engine->saveItem(
  'who_can_play_with_whom',
  JsonHelper::encode($combinations),
  config('OpenType')->buildOpenConfigTypeKey($typeId, 0, 0),
);
config('OpenType')->resetCombinationsCache($typeId, 0, 0);
```

---

## API (основное)

| Метод | Назначение |
|-------|------------|
| `buildOpenConfigTypeKey(…)` | ключ `open\|…` для записи open-параметров |
| `buildOptionsConfigTypeKey(…)` | ключ `options_{ST\|PFP\|…}\|…` |
| `getPricingSystemId(…)` | ST / PFP / … |
| `getOption(…)` / `getOptionBool(…)` | опции системы цен |
| `isDoubleCountOtherPlayersOnly(…)` | PFP-флаг расчёта double game: главный игрок + строки только дополнительных игроков |
| `getWhoCanPlayWithWhom(…)` | сырой JSON → массив |
| `getCombinationsOfPlayers(…)` | матрица для UI |
| `checkCombination(…)` | можно ли играть вместе |
| `resetCombinationsCache(…)` | сброс кэша после save |
| `checkOpenTypeAsClose(…)` | pricing system = as_close |

`DoubleGameConfig::buildConfigTypeKey(…)` — тот же паттерн для prefix `double`.

Подробное описание double game (флаги, UI, проверки, цены): [double-game.md](../reservations/double-game.md).

---

## Связанные файлы

| Файл | Роль |
|------|------|
| `OpenTypeConfig.php` | чтение, матрица, checkCombination |
| `ConfigOpenTypeModel.php` | admin: вкладки, save, делегирование |
| `ConfigOpenTypeDoubleGameModel.php` | admin: double game save |
| `ConfigOpenTypeControllerAdmin.php` | формы open_type |
| `ScopedConfigScopeTrait.php` | `buildScopedTypeKey()`, `buildScopedConfigQuery()` |

---

## Тестирование

Unit-тесты (без БД): `{git-path-tests}/tests/unit/ScopedConfig/`:

- `OpenTypeConfigTest.php` — ключи, JSON, `checkCombination`, prefix в query
- `DoubleGameConfigTypeKeyTest.php` — `buildConfigTypeKey()`

Запуск: [development/testing.md](../../development/testing.md).

---

## Связанные документы

- [scoped-config.md](scoped-config.md) — gateway, каскад, политики
- [scoped-config-implementation.md](../../archive/tasks/scoped-config-implementation.md) — архив выполненного плана
