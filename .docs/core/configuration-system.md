# Система конфигурационных файлов и констант

## Обзор

В проекте base-ac реализована гибкая система управления конфигурацией через конфигурационные файлы с поддержкой локальных переопределений. Система позволяет иметь разные настройки для продакшена, тестирования и локальной разработки.

## Основные принципы

### Приоритет конфигурационных файлов

1. **Локальные файлы (.loc.php)** - высший приоритет
2. **Основные файлы (.php)** - базовая конфигурация

### Условное определение констант

Все константы определяются с проверкой существования:
```php
defined('CONSTANT_NAME') || define('CONSTANT_NAME', 'value');
```

Это позволяет локальным файлам переопределять значения из основных файлов.

### Индексация HTML-страниц

Константа `NOINDEX_SITE` из `app/uses/defined.php` по умолчанию равна `false`.
Для включения в конкретной установке задайте в её `app/uses/defined.php`:

```php
defined('NOINDEX_SITE') || define('NOINDEX_SITE', true);
```

При `true` штатные HTML-шаблоны выводят в `<head>` тег
`<meta name="robots" content="noindex, indexifembedded">`: сайт, админка (включая вход и печать),
touchscreen, display, служебные HTML-страницы mapi и страницы HTTP-ошибок.
JSON, текстовые ответы и скачиваемые файлы этим переключателем не изменяются.
Локальные переопределения HTML-шаблонов должны сохранять блок проверки `NOINDEX_SITE` из общего шаблона.

В [шаблоне widget](../devices/widget.md) тег присутствует постоянно, независимо от константы.
При `false` прочие страницы сохраняют прежнее поведение, включая `noindex` на страницах ошибок.

### Контекстные строковые константы

`core/modules/config/helpers/ScopedConstantHelper.php` читает строковые константы, значение которых может зависеть
от `type_id`, `sport_id` и `area_id`. Helper является самостоятельным механизмом разбора констант и не относится
к `ScopedConfigGateway`, который читает конфигурацию из БД.

Поддерживаются два формата:

```text
VALUE
all|DEFAULT|type_id|VALUE|type_id_sport_id|VALUE|type_id_sport_id_area_id|VALUE
```

Обычное значение без `|` возвращается без разбора. Контекстная строка разбирается как последовательность пар
`ключ|значение`. Для контекста `10_7_3` ключи проверяются в следующем порядке:

```text
10_7_3 -> 10_7 -> 10 -> all
```

Методы helper'а:

| Метод | Назначение |
|-------|------------|
| `contextKey()` | Собирает ключ из `type_id`, `sport_id` и `area_id`; без `type_id` возвращает `null` |
| `value()` | Возвращает исходное или найденное контекстное значение; при отсутствии константы или совпадения возвращает default |
| `stringValue()` | Дополнительно обрезает пробелы и считает пустую строку и строку `false` отсутствующим значением |
| `boolValue()` | Возвращает boolean; неизвестное или неподдерживаемое значение заменяет переданным default |
| `listValue()` | Разделяет строковое значение по `;`, обрезает пробелы и удаляет пустые элементы |

`boolValue()` сохраняет нативные значения `true` и `false`. Для контекстных строк поддерживаются пары
`true`/`false`, `1`/`0`, `yes`/`no` и `on`/`off` без учёта регистра. Например,
`all|false|10_7|true` вернёт `true` для контекста `10_7` и `false` для остальных контекстов.

Контекстный формат используется следующими константами из `app/uses/defined.php`:

| Константа | Пример | Результат для контекста `10_7` |
|-----------|--------|--------------------------------|
| `DEFAULT_PAYMENT_METHOD` | `all|BR|10|GH|10_7|PP` | `PP` |
| `HIDE_PAYMENT_METHOD` | `all|BR|10_7|GH;RE` | список `GH`, `RE` |
| `DOOR_CODES` | `all|false|10_7|true` | `true` |

Если в контекстной строке нет подходящего ключа, helper не интерпретирует всю строку как общее значение и возвращает
переданный default. Для общего fallback в таком формате нужно явно добавить пару `all|VALUE`. Символ `|` внутри
ключей и значений не экранируется.

## Структура конфигурационных файлов

### Основные конфигурационные файлы

#### 1. Корневые конфигурационные файлы

**site/config.php** - основная конфигурация для публичной части:
```php
<?php
defined('ROOT_PATH')   || define('ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
file_exists(ROOT_PATH . 'config.loc.php') && require_once 'config.loc.php';

defined('HOST_NAME')     || define('HOST_NAME', 'testsystem.active-court.com');
defined('BASE_HREF')     || define('BASE_HREF', 'https://'. HOST_NAME.'/');
defined('SESSION_COURT') || define('SESSION_COURT', 'testsystem_de');
defined('LOCAL_SERVER')  || define('LOCAL_SERVER', false);
defined('SHARED_PATH')   || define('SHARED_PATH', '/var/www/shared/main/');
```

**site/config.loc.php** - локальная конфигурация (переопределяет основную):
```php
<?php
defined('BASE_HREF')     || define('BASE_HREF', 'https://ac.loc/');
defined('SESSION_COURT') || define('SESSION_COURT', 'ac_loc');
defined('LOCAL_SERVER')  || define('LOCAL_SERVER', true);
defined('SHARED_PATH')   || define('SHARED_PATH', 'e:\work\web\base-ac\dev\\');
```

**dev/config.php** - конфигурация для разработческой части:
```php
<?php
defined('SHARED')      || define('SHARED', '/var/www/shared/');
defined('ROOT_PATH')   || define('ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
file_exists(SHARED_PATH . 'config.loc.php') && require_once SHARED_PATH .  'config.loc.php';

defined('HOST_NAME')     || define('HOST_NAME', 'base-active-court.de');
defined('BASE_HREF')     || define('BASE_HREF', 'https://'. HOST_NAME.'/');
```

#### 2. Конфигурационные файлы модулей (app/uses/)

**mysql.config.php** - основная конфигурация базы данных:
```php
<?php
defined('DB_DATABASE_NAME') || define('DB_DATABASE_NAME', 'at_testsystem');
defined('DB_TABLE_PREFIX') || define('DB_TABLE_PREFIX', 'at_testsystem_');
```

**mysql.config.loc.php** - локальная конфигурация базы данных:
```php
<?php
// Локальные настройки базы данных (обычно закомментированы для безопасности)
//defined('DB_HOST')          || define('DB_HOST', 'localhost');
//defined('DB_USERNAME')      || define('DB_USERNAME', '');
//defined('DB_PASSWORD')      || define('DB_PASSWORD', '');
//defined('DB_DATABASE_NAME') || define('DB_DATABASE_NAME', 'at_local');
```

## Функция uses() - автоматическая загрузка конфигурации

### Описание функции

Функция `uses()` обеспечивает автоматическую загрузку конфигурационных файлов с поддержкой локальных переопределений и приоритетов автозагрузчика.

**Расположение**: `core/main.php`

```php
function uses(): void
{
  foreach (func_get_args() as $lib) {
    foreach (array_merge(Service::autoloader()->getNamespace(),
      ['SharedUses' => pathAs((str_starts_with(SHARED_PATH, SHARED) ? SHARED : dirname(SHARED_PATH) . '/'))]) as $path) {
      foreach (['.loc', ''] as $filePrefix) {
        if ($file = Service::autoloader()->getPathFile(paths()->constantDir . $lib . $filePrefix, 'php', $path)) {
          Service::autoloader()->loadFile($file);
        }
      }
    }
  }
}
```

### Алгоритм работы

1. Аргументы `uses()` обрабатываются последовательно: сначала полностью ищется первый файл, затем второй и так далее.
2. В список поиска входят все пути из `Service::autoloader()->getNamespace()`, после них добавляется путь `SharedUses`.
3. Для каждого пути сначала проверяется `{path}/uses/{lib}.loc.php`, затем `{path}/uses/{lib}.php`.
4. Поиск не останавливается после первого совпадения. Через `require_once` загружаются все найденные файлы,
   поэтому разные `.loc.php` и `.php` могут быть подключены вместе.

Суффикс `.loc.php` означает порядок подключения, а не автоматическую замену основного файла. Локальные значения
получают приоритет, когда файлы используют защищённое объявление, например:

```php
defined('HOST_NAME') || define('HOST_NAME', 'example.local');
```

### Расположение SharedUses

`SharedUses` — дополнительный корневой путь, который проверяется после зарегистрированных путей автозагрузчика.
Итоговый каталог формируется добавлением `uses/`:

```php
$sharedUsesRoot = str_starts_with(SHARED_PATH, SHARED)
  ? SHARED
  : dirname(SHARED_PATH) . '/';

$sharedUsesDir = $sharedUsesRoot . 'uses/';
```

- Если `SHARED_PATH` находится внутри `SHARED`, каталог `uses` берётся из общего корня `SHARED`.
- Если `SHARED_PATH` не начинается с `SHARED`, каталог `uses` располагается в родительском каталоге
  относительно `SHARED_PATH`.

Для `uses('mysql.config')` в каждом поисковом пути проверяются:

```text
uses/mysql.config.loc.php
uses/mysql.config.php
```

### Примеры использования

```php
// В bootstrap.php
uses('defined', 'baseConstants', 'authorization');

// Эквивалентно поиску файлов:
// - defined.loc.php (подключается первым, если существует)
// - defined.php (подключается следом, если существует)
// - baseConstants.loc.php
// - baseConstants.php
// - authorization.loc.php
// - authorization.php
```

## Поддерживаемые типы конфигурационных файлов

### 1. Системные константы

**Типы файлов**:
- `defined.php` / `defined.loc.php` - основные системные константы
- `baseConstants.php` / `baseConstants.loc.php` - базовые константы приложения

### 2. Конфигурация базы данных

**Типы файлов**:
- `mysql.config.php` / `mysql.config.loc.php` - настройки MySQL
- `db.config.php` / `db.config.loc.php` - общие настройки БД

### 3. Конфигурация авторизации

**Типы файлов**:
- `authorization.php` / `authorization.loc.php` - настройки авторизации
- `session.config.php` / `session.config.loc.php` - настройки сессий

### 4. Конфигурация модулей

**Типы файлов**:
- `{module_name}.config.php` / `{module_name}.config.loc.php` - настройки конкретных модулей

## Основные константы системы

### Пути и директории

```php
// Корневые пути
defined('ROOT_PATH')     || define('ROOT_PATH', '/path/to/site/');
defined('SHARED_PATH')   || define('SHARED_PATH', '/path/to/shared/');

// Пространства имен
defined('SHARED_NAMESPACE') || define('SHARED_NAMESPACE', 'AC');
defined('ROOT_NAMESPACE')   || define('ROOT_NAMESPACE', '');
```

### Веб-конфигурация

```php
// Домен и URL
defined('HOST_NAME')     || define('HOST_NAME', 'example.com');
defined('BASE_HREF')     || define('BASE_HREF', 'https://example.com/');
defined('CDN_HREF')      || define('CDN_HREF', 'https://cdn.example.com/');

// Сессии
defined('SESSION_COURT') || define('SESSION_COURT', 'app_session');
defined('LOCAL_SERVER')  || define('LOCAL_SERVER', false);
```

### База данных

```php
// Подключение к БД
defined('DB_HOST')          || define('DB_HOST', 'localhost');
defined('DB_USERNAME')      || define('DB_USERNAME', 'username');
defined('DB_PASSWORD')      || define('DB_PASSWORD', 'password');
defined('DB_DATABASE_NAME') || define('DB_DATABASE_NAME', 'database');
defined('DB_TABLE_PREFIX')  || define('DB_TABLE_PREFIX', 'prefix_');
defined('DB_CHARSET')       || define('DB_CHARSET', 'utf8');
defined('DB_TYPE')          || define('DB_TYPE', 'mysql');
```

### Загрузка файлов

```php
// Пути для загрузки файлов
defined('UPLOAD_URL') || define('UPLOAD_URL', BASE_HREF . 'uploads/');
defined('UPLOAD_DIR') || define('UPLOAD_DIR', ROOT_PATH . 'uploads/');
```

## Среды выполнения

### Продакшен

**Характеристики**:
- Использует только основные конфигурационные файлы (.php)
- `LOCAL_SERVER = false`
- Реальные домены и пути
- Оптимизированные настройки

**Пример конфигурации**:
```php
defined('HOST_NAME')     || define('HOST_NAME', 'active-court.com');
defined('LOCAL_SERVER')  || define('LOCAL_SERVER', false);
defined('SHARED_PATH')   || define('SHARED_PATH', '/var/www/shared/main/');
```

### Локальная разработка

**Характеристики**:
- Использует локальные конфигурационные файлы (.loc.php)
- `LOCAL_SERVER = true`
- Локальные домены и пути
- Отладочные настройки

**Пример конфигурации**:
```php
defined('HOST_NAME')     || define('HOST_NAME', 'ac.loc');
defined('LOCAL_SERVER')  || define('LOCAL_SERVER', true);
defined('SHARED_PATH')   || define('SHARED_PATH', 'e:\work\web\base-ac\dev\\');
error_reporting(E_ERROR); // или E_ALL для полной отладки
```

### Тестирование

**Характеристики**:
- Смешанная конфигурация
- Тестовые домены
- Тестовые базы данных

**Пример конфигурации**:
```php
defined('HOST_NAME')     || define('HOST_NAME', 'testsystem.active-court.com');
defined('DB_DATABASE_NAME') || define('DB_DATABASE_NAME', 'at_testsystem');
```

## Интеграция с автозагрузчиком

### Поиск конфигурационных файлов

Система конфигурации полностью интегрирована с `Service::autoloader()`:

1. **Приоритетные пути** - поиск согласно приоритетам автозагрузчика
2. **Множественные namespace** - поддержка всех зарегистрированных пространств имен
3. **Автоматическое определение путей** - использует `getPathFile()` для поиска файлов

### Поддерживаемые пути

```php
// Приоритетный поиск в следующих директориях:
// 1. site/app/uses/ (основной приоритет)
// 2. dev/app/uses/ (разработка)
// 3. Другие зарегистрированные пути
```

## Лучшие практики

### Создание конфигурационных файлов

1. **Всегда использовать условное определение констант**:
```php
defined('CONSTANT_NAME') || define('CONSTANT_NAME', 'default_value');
```

2. **Группировать логически связанные константы**:
```php
// База данных
defined('DB_HOST') || define('DB_HOST', 'localhost');
defined('DB_USERNAME') || define('DB_USERNAME', 'user');

// URLs
defined('BASE_HREF') || define('BASE_HREF', 'https://example.com/');
defined('CDN_HREF') || define('CDN_HREF', 'https://cdn.example.com/');
```

3. **Комментировать назначение констант**:
```php
// Основной домен сайта
defined('HOST_NAME') || define('HOST_NAME', 'example.com');

// Префикс таблиц базы данных для мультитенантности
defined('DB_TABLE_PREFIX') || define('DB_TABLE_PREFIX', 'tenant_');
```

### Локальная разработка

1. **Создавать .loc.php файлы для локальных настроек**
2. **Не коммитить локальные конфигурационные файлы в репозиторий**
3. **Использовать .gitignore для исключения локальных файлов**:
```gitignore
*.loc.php
config.loc.php
mysql.config.loc.php
```

### Безопасность

1. **Никогда не сохранять пароли в основных конфигурационных файлах**
2. **Использовать переменные окружения для чувствительных данных**
3. **Комментировать чувствительные настройки в .loc.php файлах**:
```php
// Раскомментировать и заполнить для локальной разработки
//defined('DB_PASSWORD') || define('DB_PASSWORD', 'your_password_here');
```

## Примеры конфигурации

### Базовая конфигурация сайта

**site/config.php**:
```php
<?php
defined('ROOT_PATH')   || define('ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
file_exists(ROOT_PATH . 'config.loc.php') && require_once 'config.loc.php';

// Основные настройки
defined('HOST_NAME')     || define('HOST_NAME', 'active-court.com');
defined('BASE_HREF')     || define('BASE_HREF', 'https://'. HOST_NAME.'/');
defined('SESSION_COURT') || define('SESSION_COURT', 'active_court_main');
defined('LOCAL_SERVER')  || define('LOCAL_SERVER', false);

// Пути к shared ресурсам
defined('SHARED_PATH') || define('SHARED_PATH', '/var/www/shared/main/');

// Экспорт и аналитика
defined('EXPORT_COURT') || define('EXPORT_COURT', 'active_court');
```

**site/config.loc.php**:
```php
<?php
// Локальные настройки для разработки
defined('BASE_HREF')     || define('BASE_HREF', 'https://ac.loc/');
defined('SESSION_COURT') || define('SESSION_COURT', 'ac_loc');
defined('LOCAL_SERVER')  || define('LOCAL_SERVER', true);

// Локальный путь к разработческой версии
defined('SHARED_PATH') || define('SHARED_PATH', 'e:\work\web\base-ac\dev\\');
defined('CDN_HREF')    || define('CDN_HREF', 'https://cdn.base-ac/active-court/_main/');

// Отладка
error_reporting(E_ERROR);
date_default_timezone_set('Europe/Moscow');

// Отключение SMTP для локальной разработки
define('SMTP', false);
```

### Конфигурация базы данных

**app/uses/mysql.config.php**:
```php
<?php
// Основные настройки БД (без паролей)
//defined('DB_HOST')          || define('DB_HOST', 'localhost');
//defined('DB_USERNAME')      || define('DB_USERNAME', '');
//defined('DB_PASSWORD')      || define('DB_PASSWORD', '');
//defined('DB_CHARSET')       || define('DB_CHARSET', 'utf8');
//defined('DB_TYPE')          || define('DB_TYPE', 'mysql');

// Продакшен настройки
defined('DB_DATABASE_NAME') || define('DB_DATABASE_NAME', 'at_production');
defined('DB_TABLE_PREFIX') || define('DB_TABLE_PREFIX', 'at_production_');
```

**app/uses/mysql.config.loc.php**:
```php
<?php
// Локальные настройки БД
defined('DB_HOST')          || define('DB_HOST', 'localhost');
defined('DB_USERNAME')      || define('DB_USERNAME', 'local_user');
defined('DB_PASSWORD')      || define('DB_PASSWORD', 'local_password');
defined('DB_DATABASE_NAME') || define('DB_DATABASE_NAME', 'at_local');
defined('DB_TABLE_PREFIX')  || define('DB_TABLE_PREFIX', 'at_local_');
defined('DB_CHARSET')       || define('DB_CHARSET', 'utf8');
defined('DB_TYPE')          || define('DB_TYPE', 'mysql');
defined('DB_TIME_DEBUG')    || define('DB_TIME_DEBUG', true);
```

### Единичные запросы с расширенными правами

DDL-запросы, которым недоступны права обычного пользователя сайта, выполняются через `Query::sqlQueryWithExtendedPrivileges()`. Метод создаёт отдельное подключение из `DBConfig::getExtendedPrivilegesConfig()`, выполняет один запрос и не меняет основное подключение `Query` или его транзакцию.

Конфигурация расширенного подключения использует `DB_ROOT_USERNAME` и `DB_ROOT_PASSWORD`. Учётные данные задаются только серверной конфигурацией и не должны передаваться через HTTP-параметры.

## Отладка и мониторинг

### Проверка загруженных конфигураций

```php
// Получение всех определенных констант
$constants = get_defined_constants(true)['user'];

// Проверка конкретной константы
if (defined('DB_HOST')) {
    echo "DB_HOST: " . DB_HOST;
} else {
    echo "DB_HOST не определен";
}
```

### Логирование загрузки конфигурации

```php
// В функции uses() можно добавить логирование
if ($file = Service::autoloader()->getPathFile(paths()->constantDir . $lib . $filePrefix, 'php', $path)) {
    error_log("Loading config: " . $file);
    Service::autoloader()->loadFile($file);
}
```

## Заключение

Система конфигурационных файлов в проекте base-ac обеспечивает гибкое управление настройками для разных сред выполнения. Интеграция с автозагрузчиком и поддержка локальных переопределений позволяет легко адаптировать приложение под различные условия развертывания при сохранении безопасности и удобства разработки.
