# Тестирование

## Репозитории и worktree

Код приложения живёт в **git worktree** (отдельное рабочее дерево). Обычно их несколько на одну кодовую базу — разные ветки или варианты функционала с небольшими отличиями. Имя каталога worktree на диске не фиксировано.

Unit-тесты вынесены в **отдельный git-репозиторий** `tests/`, который лежит **рядом** с worktree:

```
{worktree}/     ← код приложения (namespace AC\), документация .docs/
tests/          ← Codeception, vendor/, suite unit
```

Подробная инструкция: **[tests/README.md](../../../tests/README.md)** (путь от корня worktree: `../tests/README.md`).

## Быстрый старт

### Ветки и параллельная работа

Функциональные тесты добавляются в общую ветку репозитория тестов. Проверки,
зависящие от возможностей конкретной версии приложения, включаются через
`capabilities.php` и `TestCapabilitiesConfig`. Отдельная ветка приложения
не означает, что нужно создавать или переключать ветку тестов.

Отдельные ветки тестов используются для изменений тестовой инфраструктуры,
экспериментов или отдельного процесса review. При параллельной работе такую ветку
открывают в отдельном worktree или клоне, не переключая общий каталог тестов.

Независимые задачи оформляются отдельными логическими коммитами. В индекс
добавляются только относящиеся к задаче файлы или фрагменты, включая необходимые
изменения каталога capabilities. Чужие изменения рабочего каталога и индекса
сохраняются. Capabilities управляют запуском тестов, но не изолируют Git-состояние.

### Запуск

Требуется **PHP 8.2+** (`php -v`). Из корня worktree, который вы тестируете:

```powershell
cd ..\tests
composer install
php vendor\bin\codecept build
php vendor\bin\codecept run unit
```

Команды выполняются из каталога `tests/`, не из worktree приложения.

Если bootstrap должен смотреть на **другой** соседний worktree (не каталог по умолчанию):

```powershell
$env:AC_APP_ROOT = '..\имя-worktree-на-диске'
php vendor\bin\codecept run unit
```

Запуск непосредственно из корня тестируемого worktree без перехода в каталог
`tests` требует явно указать конфигурационный файл:

```powershell
$env:AC_APP_ROOT = (Get-Location).Path
php ..\tests\vendor\bin\codecept run unit -c ..\tests\codeception.yml
```

Для worktree без модуля шкафчиков:

```powershell
$env:AC_APP_ROOT = (Get-Location).Path
php ..\tests\vendor\bin\codecept run unit --skip-group lockers -c ..\tests\codeception.yml
```

### Единая команда `actest`

Репозиторный `bin/actest` выбирает локальный клон тестов или Composer-пакет и
делегирует запуск launcher-файлу тестового репозитория. Поэтому в Windows и
Linux используется одна команда:

```bash
composer test
composer test -- --skip-group lockers
composer test -- unit --group area-prices
composer test -- integration --group area-prices-db
```

Без Composer alias доступен прямой эквивалент:

```bash
php bin/actest
php bin/actest unit --group area-prices
```

Локальный путь задаётся в игнорируемом `.actest.loc.json`:

```json
{
  "testsRoot": "../tests"
}
```

Версионируемый пример находится в `.actest.example.json`. Относительный путь
разрешается от корня приложения; допускается также абсолютный путь.

Launcher выбирает источник в следующем порядке:

1. аргумент `--tests-root`;
2. файл `.actest.loc.json`;
3. переменная `AC_TESTS_ROOT`;
4. каталоги `tests/` и `../tests/`;
5. Composer-пакет `active-court/base-ac-dev` в `vendor/`.

Источник можно ограничить параметром `--tests-source=local` или принудительно
выбрать пакет через `--tests-source=package`. `AC_APP_ROOT` launcher передаёт
самостоятельно.

Для установки зафиксированной версии тестового пакета выполните:

```bash
composer install
```

Доступ к приватному VCS-репозиторию настраивается локальными SSH-ключами или
`auth.json`; credentials не добавляются в `composer.json` и Git. CI должен
использовать read-only deploy key или token из хранилища секретов.

Подробные параметры suite и групп определяются в
[README тестового репозитория](../../../tests/README.md).

### Быстрая команда PowerShell

После установки проектного профиля командой `.\install-profile.ps1` запуск сокращается до:

```powershell
actest
actest --skip-group lockers
actest unit --group area-prices
actest integration --group area-prices-db
```

`actest` определяет текущий Git worktree, вызывает его `bin/actest` и передаёт тестовому launcher явный `--app-root`. Проект, из которого установлен профиль, не используется как fallback. Вне Active Court worktree команда завершается ошибкой. Установка, обновление и удаление быстрых команд описаны в [руководстве по установке](../installation/setup.md#быстрые-команды-powershell), подробная логика - в [описании PowerShell-команд](powershell-commands.md).

## Codeception

| Параметр | Значение |
|----------|----------|
| Корень конфигурации | `tests/codeception.yml` |
| Namespace тестов | `ACTests` |
| Suite | `unit`, `integration` |
| Код под тестом | worktree приложения через `_bootstrap.php` |

Bootstrap регистрирует autoload только для префикса `AC\` — без `config.php` и без подключения к БД.

Переменная **`AC_APP_ROOT`** — путь к корню worktree с кодом `AC\` (если не задана, используется соседний каталог по умолчанию, см. `tests/_bootstrap.php`).

Тесты модуля шкафчиков входят в группу `lockers`. Для worktree без этого модуля
используйте `php vendor\bin\codecept run unit --skip-group lockers`.

## PhpStorm

Откройте в IDE **тот worktree**, изменения в котором проверяете:

1. **Settings → PHP → Test Frameworks → Codeception** — `../tests/codeception.yml` и `../tests/vendor/.../codecept` (часто уже в `.idea/php-test-framework.xml`).
2. Перед запуском: в `tests/` — `composer install` и `codecept build`.
3. При необходимости — `AC_APP_ROOT` в environment конфигурации запуска.
4. В поле **Пользовательская рабочая директория** укажите абсолютный путь к
   соседнему репозиторию `tests`, например `C:\path\to\tests`.

Запуск: ПКМ на suite / класс / метод в `../{git-path-tests}/tests/unit/`.

## Что уже покрыто

Suite `unit`, каталог `tests/unit/ScopedConfig/`:

- `ConfigTypeKeyBuilder` — единый хвост ключа для всех policy
- `AliasChainResolver`
- `ScopedConfigPolicy`, `ScopedConfigResult`
- `ConfigTableSourceCascadeTest`, `ClientRestrictionConfigTest`
- `OpenTypeConfigTest` — ключи `open|…`, `who_can_play_with_whom`, `checkCombination`
- `DoubleGameConfigTypeKeyTest` — `buildConfigTypeKey()` для double

Актуальный список файлов — в `{git-path-tests}/tests/unit/ScopedConfig/`.

Suite `unit`, каталог `tests/unit/Reservations/`:

- `ArchivedReservationDataDecoderTest` — корректный архивный JSON,
  восстановление неэкранированных serialized-данных `street_friends` с разной
  вложенностью, безопасная нормализация и получение обязательных полей из
  префикса при невосстановимом `street_friends`.
- `OrdersModelReservationCheckOrderTest` — цепочка проверок заказа: запрет бронирования, минимум периодов, последовательные периоды, максимум подряд, горизонт бронирования, недоступные виды спорта, диапазон клубного статуса, лимит бронирований, временной диапазон, happy path, обходы для admin/super.
- `DoubleOrdersModelOpenReservationPriceMockTest` — PFP-расчёт цены для single/double game:
  - одиночная игра сохраняет существующую цену пары при выключенном `double_count_other_players_only`;
  - одиночная игра не меняется при включенном `double_count_other_players_only`;
  - двойная игра со снятым чекбоксом использует старый grouped PFP-расчёт;
  - двойная игра с включенным чекбоксом считает `главный игрок один раз + сгруппированные дополнительные игроки`.

Suite `unit`, каталог `tests/unit/AreaPrices/`, группа `area-prices`:

- `ReservationAreasPriceGroupsTest` — объединение площадок по длительности и
  совместимой временной сетке независимо от набора рабочих дней, разделение при несовместимом смещении рабочих часов,
  сохранение всех воскресных тарифов для праздников и отсутствие дублей при
  наличии обычного воскресного расписания.

Запуск группы для worktree с поддержкой групповых цен:

```powershell
$env:AC_APP_ROOT = (Get-Location).Path
php ..\tests\vendor\bin\codecept run unit --group area-prices -c ..\tests\codeception.yml
```

Suite `integration`, каталог `tests/integration/AreaPrices/`, группа
`area-prices-db`:

- `ReservationAreasPriceDatabaseTest` — реальные SQL-запросы группировки и
  сохранения цен, ограничение записи площадками выбранной группы, воскресные
  тарифы праздников и проверка полного отката фикстур.

Для всех integration-тестов используется база `test_at`. Префикс таблиц берётся
из `DB_TABLE_PREFIX` выбранного сайта; для текущего проекта это
`at_cksportcenter_`. Если базы нет, integration-bootstrap создаёт её
автоматически. Недостающие таблицы `areas`, `areas_timetables`, `areas_prices`
и `areas_prices_periods` создаются через `CREATE TABLE ... LIKE` таблиц сайта:
копируется только структура, без производственных данных. Тестовые таблицы
автоматически переводятся в InnoDB для поддержки `ROLLBACK`. Пользователю БД
нужны права `CREATE DATABASE`, `CREATE` и `ALTER` для подготовки `test_at`.

```powershell
$env:AC_APP_ROOT = 'C:\path\to\application-worktree'
$env:AC_TEST_SITE_ROOT = 'C:\path\to\public-site'
php ..\tests\vendor\bin\codecept run integration --group area-prices-db -c ..\tests\codeception.yml
```

Чтобы не задавать путь в каждой PowerShell-сессии, сохраните его один раз в
пользовательской переменной:

```powershell
$siteRoot = 'C:\path\to\public-site'
[Environment]::SetEnvironmentVariable('AC_TEST_SITE_ROOT', $siteRoot, 'User')
$env:AC_TEST_SITE_ROOT = $siteRoot
```

После этого настроенная локальная команда запускает тот же тест из worktree
приложения без дополнительной установки переменной:

```powershell
actest integration --group area-prices-db
```

По умолчанию тест создаёт фикстуры внутри транзакции, выполняет проверки и
делает `ROLLBACK`. Поэтому после обычного запуска тестовая база остаётся
пустой.

Suite `integration`, каталог `tests/integration/Areas/`, группа
`areas-archive-db` проверяет фильтрацию площадок по `areas.archived_at`:

- обычные списки исключают архивные площадки;
- `onlyActive=false` добавляет неактивные, но не архивные площадки;
- `archived=true` выбирает только архивные площадки;
- `archived=null` отключает архивный фильтр;
- получение по `area_id` сохраняет доступ к архивной площадке;
- схема без `archived_at` продолжает работать без SQL-ошибок.

```powershell
$env:AC_APP_ROOT = (Get-Location).Path
php ..\tests\vendor\bin\codecept run integration --group areas-archive-db -c ..\tests\codeception.yml
```

Для визуальной проверки задайте `AC_TEST_KEEP_DATA=1`. Фикстуры будут
зафиксированы через `COMMIT` только после успешного выполнения всех проверок;
при падении теста транзакция всё равно откатывается. Параметр `--debug` выводит
имя базы, уникальную метку запуска, ID созданных площадок и готовые SQL-запросы
для просмотра данных:

```powershell
$env:AC_TEST_KEEP_DATA = '1'
actest integration --group area-prices-db --debug
Remove-Item Env:AC_TEST_KEEP_DATA
```

Созданные данные помечаются префиксом `AC-TEST-AREA-PRICES:`. После визуальной
проверки запустите тот же тест в cleanup-режиме:

```powershell
$env:AC_TEST_CLEANUP_DATA = '1'
actest integration --group area-prices-db
Remove-Item Env:AC_TEST_CLEANUP_DATA
```

При `AC_TEST_CLEANUP_DATA=1` основной тест не создаёт новые фикстуры, а удаляет
только площадки, сезоны, расписания и цены с тестовой меткой. Без этой
переменной выполняется обычная функциональная проверка с `ROLLBACK`; остальные
записи тестовой базы не затрагиваются.

Suite `unit`, каталог `tests/unit/Helpers/`:

- `CalendarHelperTest` — генерация игровых дат до и после опорной даты,
  сохранение фазы двухнедельного цикла, фильтрация по рабочим периодам и
  проверка положительного целого `space`.
- `DateHelperTest` — преобразования и нормализация дат, календарные диапазоны,
  списки годов, месяцев и дней.
- `DateHelperSubtractRangesTest` — строгий разбор дат и вычитание включительных
  диапазонов с пересечениями и граничными случаями.
- `ScopedConstantHelperTest` — построение контекста, каскад scoped-значений,
  нормализация строк и списков, boolean-значения и default.

Suite `unit`, каталог `tests/unit/Tickets/`:

- `TicketBlockIntersectionServiceTest` — полные и частичные пересечения
  абонемента с блокировками, сетка слотов, неигровые даты, двухнедельная
  периодичность, удаление остаточных диапазонов без игровых дат, однодневные
  остаточные диапазоны и значения `source_type`.

Актуальный список файлов — в `{git-path-tests}/tests/unit/Reservations/`.

Связанные документы по покрытию:

- [double-game.md](../modules/reservations/double-game.md#тестирование) — тесты double game и PFP-ценообразования.
- [open-type-config.md](../modules/config/open-type-config.md#тестирование) — тесты scoped-настроек OpenType.

## Совместимость версий проекта

Feature-тесты должны наследовать `ACTests\Support\ApplicationTestCase` и
объявлять необходимые capabilities. Все известные имена хранятся со значением
`false` в `../tests/capabilities.php`, а конкретный worktree включает только
поддерживаемые возможности в `app/config/TestCapabilitiesConfig.php`.

Обязательное правило: при добавлении или изменении feature-теста либо отдельного
тестового метода новый capability добавляется в общий каталог со значением
`false` и без отдельного подтверждения включается через `true` в текущем
тестируемом worktree. Другие worktree менять не требуется. При удалении
последнего использования capability удаляется из каталога и проектных конфигов.
Имена веток и каталогов в тестах не проверяются.

## Добавление тестов

1. Класс в `{git-path-tests}/tests/unit/<Area>/`, namespace `ACTests\unit\<Area>`.
2. `extends Codeception\Test\Unit`.
3. Тестируемый код — из `AC\...` (autoload из worktree через bootstrap).
4. Для классов с зависимостью от БД — моки или вынос логики в слой без IO.

Зависимости тестов (`composer.json`, `vendor/`) — только в репозитории `tests/`, не в worktree приложения.

## PHPUnit в документации

В руководстве разработчика и модулях встречаются примеры на `PHPUnit\Framework\TestCase` — это **целевой стиль** для будущих тестов. Текущая инфраструктура — **Codeception unit suite** (совместим с PHPUnit-assertions через модуль `Asserts`).

Новые unit-тесты добавляйте в репозиторий `tests/` в формате Codeception, пока не будет отдельного решения по PHPUnit.

## Связанные документы

- [developer-guide.md](developer-guide.md) — принципы и примеры (в т.ч. иллюстративные PHPUnit-сниппеты)
- [scoped-config.md](../modules/config/scoped-config.md) — архитектура gateway и ключей
- [open-type-config.md](../modules/config/open-type-config.md) — OpenTypeConfig, комбинации игроков
- [client-restriction.md](../modules/config/client-restriction.md) — домен restriction
- [scoped-config-implementation.md](../archive/tasks/scoped-config-implementation.md) — PR-1…PR-3
- [client-restriction-implementation.md](../tasks/client-restriction-implementation.md) — PR-4…PR-7
