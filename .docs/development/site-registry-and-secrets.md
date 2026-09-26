# Реестр сайтов, секреты и взаимодействие с `upd-list`

## Обзор

Для массовых операций (деплой файлов, миграции БД через `mapi/act.php`) нужен **единый список сайтов** с метаданными и **отдельное хранилище секретов** (FTP, пароли БД).

В проекте уже есть инструмент **`mapi/upd-list`**, который управляет списками сайтов через CSV. Документ описывает:

- как это работает сейчас;
- рекомендуемое разделение публичных данных и секретов;
- CRUD (добавление, изменение, удаление);
- связь с миграциями БД.

**Связанные материалы:**

- [Стратегия миграции БД](database-migration-strategy.md) — `compareDB`, rollout, `_schema_migrations`
- [Спецификации сайтов](../sites/README.md) — человекочитаемые кастомизации по доменам

---

## Текущая реализация: `mapi/upd-list`

### Назначение

`mapi/upd-list` — внутренний инструмент для:

- ведения списков сайтов;
- массовой выкладки файлов по FTP;
- SQL/PHP-обновлений на выбранных сайтах.

Точка входа: `/mapi/upd-list/`

### Файлы списков

```
mapi/upd-list/csv/
├── list.csv      # основной список (по умолчанию)
├── sites.csv     # дополнительный список
└── {name}.csv    # любой новый список = отдельный файл
```

Каждый `*.csv` — отдельная **группа сайтов** (prod, test, волна миграции и т.д.). Переключение — вкладками в UI (`?list=sites`).

### Формат CSV

Заголовок и пример строки:

```csv
site,base,prefix,ftp_host,ftp_user,ftp_pass,ftp_path,bd_user,bd_pass,bd_host,protocol
tennis-buchung.de,at_tennis_buchung,at_tennis_buchung2_,tennis-buchung.de,web2,***,/html/tennis-buchung.de,,,,http
```

| Колонка | Назначение |
|---------|------------|
| `site` | Домен / идентификатор сайта |
| `base` | Имя базы данных (`DB_DATABASE_NAME`) |
| `prefix` | Префикс таблиц (`DB_TABLE_PREFIX`) |
| `ftp_host` | FTP/SFTP хост |
| `ftp_user` | FTP логин |
| `ftp_pass` | FTP пароль |
| `ftp_path` | Путь к корню сайта на сервере |
| `bd_user` | Пользователь MySQL (опционально) |
| `bd_pass` | Пароль MySQL (опционально) |
| `bd_host` | Хост MySQL (опционально) |
| `protocol` | `http` / `https` для URL |

### Класс `MBaseList`

Расположение: `mapi/upd-list/model/classes/base_list.php`

| Метод | Действие |
|-------|----------|
| `load($list)` | Читает `{list}.csv` в `$arr_data` |
| `save()` | Перезаписывает весь CSV из `$arr_data` |
| `insert($params)` | Добавляет строку |
| `update($index, $params)` | Обновляет строку по индексу |
| `delete($index)` | Удаляет строку по индексу |
| `getLists($curr)` | Список всех `*.csv` для вкладок UI |

**Важно:** при любом изменении перезаписывается **весь файл** — атомарность на уровне файла, без построчного append.

### CRUD через веб-интерфейс

Контроллер: `mapi/upd-list/controller/classes/List.php`

| Операция | URL / действие | POST-параметры |
|----------|----------------|----------------|
| **Просмотр** | `GET /mapi/upd-list/?list=list` | — |
| **Добавление** | форма «Вставить» | `action=CList`, `insert=Y`, поля из заголовка CSV |
| **Изменение** | иконка карандаша в строке | `action=CList`, `update=Y`, `item_id=N`, поля строки |
| **Удаление** | иконка крестика | `action=CList`, `delete=Y`, `item_id=N` |

UI: `mapi/upd-list/view/views/blocks/block_list.php`, `block_add.php`  
AJAX: `mapi/upd-list/view/js/scripts.js`

### Использование FTP из списка

Классы `MFTP` / `ftp_php.php` читают из строки списка колонки:

- `ftp_host`, `ftp_user`, `ftp_pass`, `ftp_path`

и выполняют upload/download на выбранных сайтах (чекбоксы в таблице).

---

## Проблемы текущей схемы

| Проблема | Риск |
|----------|------|
| FTP и BD пароли в CSV | утечка при коммите, копировании, бэкапе |
| Один файл = публичное + секретное | нельзя безопасно хранить в git |
| CSV без экранирования | поломка при `,` и `"` в паролях/путях |
| Нет полей для миграций | нет `codebase`, `enabled`, `last_sync` |
| Нет аудита изменений | неизвестно, кто и когда менял строку |
| Секреты в `config.php` upd-list | `ADMIN_PASSWORD`, `DB_PASSWORD_ROOT` в коде |

Для 200+ сайтов CSV с открытыми паролями — рабочий прототип, но **не целевая архитектура**.

---

## Рекомендуемая архитектура: два слоя

### Слой 1 — публичный реестр (можно в git)

Только несекретные данные. Эволюция текущего CSV или переход на YAML:

```yaml
# database/migration-registry/sites.yaml (целевой формат)
registry_version: 1

sites:
  - id: tennis-buchung
    site: tennis-buchung.de
    base: at_tennis_buchung
    prefix: at_tennis_buchung2_
    protocol: https
    codebase: main
    ftp_path: /html/tennis-buchung.de
    enabled: true
    secrets_ref: tennis-buchung
```

| Поле | Описание |
|------|----------|
| `id` | Стабильный ключ (не меняется при смене домена) |
| `site` | Домен для URL и отображения |
| `base` / `prefix` | Подключение к MySQL |
| `codebase` | Ветка кода: `main`, `mc`, … |
| `enabled` | `false` — пропускать в mass-run (архив, проблемный сайт) |
| `secrets_ref` | Ключ в хранилище секретов (не сам пароль) |
| `ftp_path` | Путь на сервере (не секрет) |

### Слой 2 — секреты (не в git)

| Способ | Когда использовать |
|--------|-------------------|
| Файл на сервере `/var/www/shared/secrets/sites.json` | простой старт, один ops-сервер |
| `secrets.loc.php` / env на каждом хосте | мало серверов |
| Vault / 1Password / Bitwarden Secrets | корпоративная инфраструктура |
| Таблица `at_ops.site_secrets` с шифрованием | нужен аудит и UI |

Пример `sites.secrets.json` (только на сервере, `chmod 600`):

```json
{
  "tennis-buchung": {
    "ftp_host": "tennis-buchung.de",
    "ftp_user": "web2",
    "ftp_pass": "...",
    "bd_host": "localhost",
    "bd_user": "active_court",
    "bd_pass": "..."
  }
}
```

Связь: `secrets_ref` в реестре = ключ верхнего уровня в JSON.

Подробнее о шифровании и загрузке секретов в runtime — раздел [Шифрование и взаимодействие с секретами](#шифрование-и-взаимодействие-с-секретами).

---

## Шифрование и взаимодействие с секретами

В проекте **пока нет** готового класса для secrets — ниже целевая схема, совместимая с PHP 8.2 и `mapi/upd-list`.

### Цепочка `secrets_ref`

```
list.csv / sites.yaml          sites.secrets.enc.json (или Vault)
┌─────────────────────┐        ┌──────────────────────────────┐
│ site: tennis-...    │        │ "tennis-buchung": {          │
│ secrets_ref:        │───────►│   ftp_host, ftp_user,        │
│   tennis-buchung    │  ключ  │   ftp_pass, bd_*             │
└─────────────────────┘        └──────────────────────────────┘
         │
         ▼
   MFTP / orchestrator / updateBD
   $secrets = SecretsStore::get('tennis-buchung')
   ftp_login($secrets['ftp_host'], $secrets['ftp_user'], $secrets['ftp_pass'])
```

**Правило:** реестр хранит только `secrets_ref` (строку-ключ). Код **никогда** не ищет пароль в CSV — только через хранилище.

### Три уровня защиты (выбор по зрелости)

| Уровень | Хранение | Шифрование | Когда |
|---------|----------|------------|-------|
| **0** | `sites.secrets.json` на диске | нет (только chmod 600) | быстрый старт, один ops-сервер |
| **1** | `sites.secrets.enc` на диске | AES-256-GCM / libsodium | рекомендуемый минимум для prod |
| **2** | HashiCorp Vault / облачный secrets manager | на стороне Vault | несколько серверов, аудит, ротация |

Уровень 0 — переходный; в git не коммитить даже «временный» JSON с паролями.

---

### Вариант 1: зашифрованный файл на сервере

#### Что лежит на диске

Один файл, **весь blob зашифрован** (не шифруется каждое поле отдельно):

```
/var/www/shared/secrets/sites.secrets.enc   # бинарный или base64-текст
```

Внутри после расшифровки — обычный JSON:

```json
{
  "tennis-buchung": {
    "ftp_host": "tennis-buchung.de",
    "ftp_user": "web2",
    "ftp_pass": "secret",
    "bd_host": "localhost",
    "bd_user": "active_court",
    "bd_pass": "secret"
  }
}
```

#### Мастер-ключ (не в файле, не в git)

Хранится **только** вне репозитория:

```bash
# /etc/active-court/secrets.env  (chmod 600, root или deploy-user)
AC_SECRETS_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
AC_SECRETS_PATH=/var/www/shared/secrets/sites.secrets.enc
```

Ключ загружается при старте PHP-процесса из env (systemd `EnvironmentFile`, php-fpm pool, не в `config.php` в git).

#### Алгоритм шифрования (рекомендация для PHP 8.2)

**libsodium** (`sodium_crypto_secretbox`), если расширение доступно; иначе **OpenSSL AES-256-GCM**.

Схема secretbox (упрощённо):

```
ciphertext = secretbox( json_encode($allSecrets), nonce, masterKey )
on disk    = base64( nonce || ciphertext )
```

| Элемент | Назначение |
|---------|------------|
| `masterKey` | 32 байта, из `AC_SECRETS_KEY` (декодировать из base64) |
| `nonce` | 24 байта, **уникальный при каждом сохранении** файла |
| `ciphertext` | зашифрованный JSON всего хранилища |

Шифруется **весь файл целиком**, а не отдельные пароли: проще ротация и атомарная перезапись.

Псевдокод чтения:

```php
final class SecretsStore
{
    private static ?array $cache = null;

    public static function get(string $secretsRef): array
    {
        $all = self::loadAll(); // расшифровка один раз за запрос
        if (!isset($all[$secretsRef])) {
            throw new \RuntimeException("Unknown secrets_ref: {$secretsRef}");
        }
        return $all[$secretsRef];
    }

    private static function loadAll(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $path = getenv('AC_SECRETS_PATH');
        $key  = self::decodeMasterKey(getenv('AC_SECRETS_KEY'));
        $raw  = file_get_contents($path);
        $json = sodium_crypto_secretbox_open(/* nonce + cipher from $raw */, $key);
        self::$cache = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        return self::$cache;
    }
}
```

Псевдокод записи (CLI `bin/secrets.php set tennis-buchung ftp_pass '...'`):

```php
$all = SecretsStore::loadAllForWrite(); // или [] для нового файла
$all['tennis-buchung']['ftp_pass'] = $newPassword;
$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$cipher = sodium_crypto_secretbox(json_encode($all), $nonce, $masterKey);
file_put_contents($path, base64_encode($nonce . $cipher), LOCK_EX);
chmod($path, 0600);
self::$cache = null; // сброс кэша
```

#### Взаимодействие из `upd-list`

Текущий код читает `ftp_pass` из строки CSV. Целевой поток:

```php
// mapi/upd-list/model/classes/ftp_php.php (целевая доработка)
$row = MBaseList::$arr_data[$index];
$secrets = SecretsStore::get($row['secrets_ref']);
$host = $secrets['ftp_host'];
$user = $secrets['ftp_user'];
$pass = $secrets['ftp_pass'];
$dir  = $row['ftp_path']; // путь — в реестре, не секрет
```

Для массового FTP по чекбоксам — один `loadAll()` на запрос, затем lookup по `secrets_ref` для каждой строки.

#### Кэширование

- **В памяти на один HTTP-запрос** — да (static `$cache` в `SecretsStore`).
- **На диск / Redis** — не кэшировать расшифрованные данные.
- После `secrets set` — сброс кэша или завершение процесса.

---

### Вариант 2: Vault (или аналог)

Файла с паролями на диске **нет**. `secrets_ref` = путь или имя секрета в Vault.

```
secrets_ref: tennis-buchung
     │
     ▼
GET secret/active-court/sites/tennis-buchung
     │
     ▼
{ "ftp_host": "...", "ftp_pass": "..." }
```

Псевдокод:

```php
public static function get(string $secretsRef): array
{
    $token = getenv('VAULT_TOKEN'); // AppRole / короткоживущий токен
    $url   = getenv('VAULT_ADDR') . '/v1/secret/data/active-court/sites/' . $secretsRef;
    // HTTP GET + X-Vault-Token → json data.data
}
```

| | Файл `.enc` | Vault |
|--|-------------|-------|
| Мастер-ключ на сервере | да (`AC_SECRETS_KEY`) | нет (токен + политики Vault) |
| Офлайн-деплой | проще | нужен доступ к Vault |
| Аудит «кто читал» | только логи ОС | встроенный audit log Vault |
| Ротация пароля сайта | перезапись файла | версия секрета в Vault |

Для 200+ сайтов на одном ops-хосте часто достаточно **файла `.enc` + env-ключ**; Vault — при нескольких админах и серверах.

---

### CRUD секретов (кто и как меняет)

| Операция | Реестр (CSV/YAML) | Секреты |
|----------|-------------------|---------|
| **Добавить сайт** | `insert` в `MBaseList` + `secrets_ref` | CLI: `secrets set {ref} --from-json` или UI «Секреты» |
| **Сменить FTP-пароль** | не трогать | `secrets set {ref} ftp_pass '...'` |
| **Сменить домен** | `update` поля `site` | не трогать `secrets_ref` |
| **Удалить сайт** | `enabled: false` или `delete` | `secrets delete {ref}` (опционально) |

**Не делать** форму в `upd-list`, которая пишет пароли обратно в CSV.

Рекомендуемый CLI (планируемый):

```bash
php bin/secrets.php list
php bin/secrets.php get tennis-buchung --keys ftp_user   # без вывода пароля в лог по умолчанию
php bin/secrets.php set tennis-buchung ftp_pass 'new'
php bin/secrets.php export --encrypt -o sites.secrets.enc  # миграция с plain JSON
```

---

### Ротация мастер-ключа

1. Расшифровать файл старым `AC_SECRETS_KEY`.
2. Зашифровать тем же JSON новым ключом.
3. Атомарно заменить `sites.secrets.enc`.
4. Обновить env на всех хостах, где читают секреты.
5. Уничтожить старый ключ.

Пароли сайтов при этом **не меняются** — меняется только обёртка.

### Ротация пароля одного сайта

1. `secrets set tennis-buchung ftp_pass '...'`.
2. `secrets_ref` и реестр **без изменений**.
3. Следующий FTP-деплой подхватывает новый пароль из `loadAll()`.

---

### Что видит процесс в runtime

```
HTTP → upd-list → MBaseList::load('list')
              → для site[i]: secrets_ref = 'tennis-buchung'
              → SecretsStore::get('tennis-buchung')
                    → [первый вызов] read .enc → decrypt → cache
                    → return ['ftp_host'=>..., 'ftp_pass'=>...]
              → MFTP::connect(host, user, pass)
              → ... ftp_put ...
              → конец запроса, cache уничтожается
```

Для orchestrator миграций секреты **не нужны**, если вызывается только `https://{site}/mapi/act.php` (сайт сам знает свою БД). Секреты нужны для:

- FTP-деплоя (`upd-list`);
- прямого подключения к MySQL с ops-хоста (если не через HTTP).

---

### Ошибки и политика

| Ситуация | Поведение |
|----------|-----------|
| Нет `secrets_ref` в строке реестра | пропуск сайта + запись в лог |
| `secrets_ref` не найден в хранилище | ошибка, не fallback на CSV |
| Нет `AC_SECRETS_KEY` / битый файл | fail fast, не работать с пустыми паролями |
| Нет прав на файл | 500 + лог, без вывода пути в публичный ответ |

---

### Минимальный план внедрения

1. Класс `SecretsStore` (read + in-memory cache).
2. CLI `bin/secrets.php` (set/list/export/import).
3. Env: `AC_SECRETS_KEY`, `AC_SECRETS_PATH`.
4. Доработка `MFTP`: credentials из `SecretsStore::get($secrets_ref)`.
5. Миграция: скрипт CSV → plain JSON → `export --encrypt` → удалить пароли из CSV.
6. Опционально позже: адаптер `VaultSecretsStore` с тем же интерфейсом `get(string $ref): array`.

Интерфейс для смены backend без переписывания `upd-list`:

```php
interface SecretsProviderInterface {
    public function get(string $secretsRef): array;
}
// FileSecretsProvider, VaultSecretsProvider
```

---


### Вариант A — доработать `upd-list` (минимум изменений)

```
┌──────────────────────────────────────────────┐
│  UI mapi/upd-list                            │
│  CSV/YAML — только публичные колонки         │
│  Секреты — отдельная форма / файл вне git   │
└──────────────────────────────────────────────┘
```

**Добавление сайта:**

1. В UI: `site`, `base`, `prefix`, `ftp_path`, `protocol`, `codebase`, `secrets_ref`.
2. Пароли — через форму «Секреты» → запись в `sites.secrets.json`.
3. `MBaseList` / FTP-классы: при операции подставляют пароль по `secrets_ref`.

**Изменение:**

- публичные поля — как сейчас (update в CSV);
- пароли — только в secrets-файле.

**Удаление:**

- предпочтительно `enabled: false` (soft delete);
- физическое удаление строки — по согласованию;
- секреты удалять отдельно при деcommission.

### Вариант B — реестр в git, UI только для операций

- Список сайтов — YAML/JSON в репозитории, изменения через PR.
- `upd-list` читает реестр + secrets с диска.
- CRUD списка — редактор + code review (удобно при 200+ сайтах).

### Вариант C — всё в служебной БД `at_ops`

```sql
sites (id, site, document_root, codebase, enabled, secrets_ref, ...)
site_secrets (site_id, ftp_host, ftp_user, ftp_pass_enc, bd_host, bd_user, bd_pass_enc, ...)
migration_runs (site_id, action, status, started_at, ...)
```

`base` и `prefix` нужны в центральном реестре только для операций, которые напрямую подключаются к БД сайта. Если задача запускается через собственный bootstrap сайта, значения берутся из `DB_DATABASE_NAME` и `DB_TABLE_PREFIX` и в `at_ops.sites` не дублируются.

CRUD — через защищённый admin UI или CLI.

**Рекомендация:** начать с **варианта A**, затем при росте — **B** или **C**.

---

## Центральный cron для 200+ сайтов

Для периодических задач на сайтах, расположенных на одном сервере, рекомендуется один системный cron и один общий PHP-worker на каждую кодовую базу. Одинаковый `cron.php` не нужно копировать в корень каждого сайта, а отдельные записи системного cron для сайтов не создаются.

```text
Один системный cron
        │
        ▼
Центральный планировщик читает at_ops.sites
        │
        ▼
Выбирает enabled-сайты с наступившим next_run_at
        │
        ▼
Запускает общий site-worker.php отдельным PHP-процессом
        │
        ▼
Worker загружает bootstrap/config.php выбранного сайта
        │
        ▼
Задача использует DB_DATABASE_NAME, DB_TABLE_PREFIX и настройки сайта
```

Минимальные данные сайта для такого режима:

```text
id
site
document_root
codebase
enabled
cron_enabled
next_run_at
last_run_at
last_status
```

Поля `database_name` и `table_prefix` не обязательны: отдельный процесс загружает конфигурацию выбранного сайта, где эти значения уже определены константами. `document_root` также можно не хранить, если на сервере действует единое и строгое правило построения пути по домену; явное поле обычно надёжнее.

### Запуск общего worker

Пример команды планировщика:

```bash
php /var/www/shared/cron/site-worker.php \
  --site-root=/var/www/sites/aalen \
  --action=ical
```

Worker должен запускаться через `proc_open()` с командой-массивом, без сборки shell-строки из данных реестра:

```php
$process = proc_open(
    [PHP_BINARY, $workerPath, '--site-root=' . $siteRoot, '--action=' . $action],
    [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ],
    $pipes
);
```

Перед запуском планировщик обязан:

1. Получить `document_root` только из доверенного реестра.
2. Разрешить путь через `realpath()` и проверить, что он находится внутри разрешённого каталога сайтов.
3. Проверить допустимое значение `action` по фиксированному списку.
4. Установить блокировку от одновременного запуска одной задачи одного сайта.
5. Ограничить время выполнения, сохранить exit code, stdout/stderr и обновить `last_status`/`next_run_at`.

Каждый сайт запускается отдельным PHP-процессом. Это обязательно для Active Court: `HOST_NAME`, `DB_DATABASE_NAME`, `DB_TABLE_PREFIX` и другие параметры определяются константами и не могут безопасно переключаться между сайтами в одном процессе.

На сервере достаточно одного worker на кодовую базу:

```text
/var/www/shared/core/bin/site-worker.php
/var/www/shared/mc/bin/site-worker.php
```

Если все сайты используют одну кодовую базу, остаётся только один файл. Если сайты расположены на разных серверах и их `document_root` недоступен планировщику, вместо локального PHP-процесса применяется защищённый HTTP endpoint с HMAC-подписью, timestamp, timeout и защитой от повторного запуска.

### Определение необходимости конкретной задачи

Центральный реестр определяет, когда следует проверить сайт, а окончательное решение принимает сам сайт после загрузки конфигурации. Например, iCal-задача выполняется только при включённой FTP/iCal-настройке и наличии активного WebIO Output для площадки или ячейки шкафчика. Если целей нет, worker завершается успешно без генерации файлов.

Такой подход не требует списка площадок в центральном cron: площадки и ячейки выбираются из локальных таблиц сайта. Центральный реестр содержит только сайты и состояние их задач.

---

## Что куда хранить

| Данные | Хранилище | В git? |
|--------|-----------|--------|
| домен, `document_root`, codebase, enabled | реестр | да |
| имя БД, префикс | `config.php` сайта; в реестре только для прямых DB-операций | нет / опционально |
| ftp_path, protocol | реестр | да |
| **ftp_host, ftp_user, ftp_pass** | secrets | **нет** |
| **bd_user, bd_pass, bd_host** | secrets | **нет** |
| состояние миграций | `_schema_migrations` в БД сайта | нет |
| журнал прогонов | `at_ops.migration_runs` | нет |
| кастомизации | `.docs/sites/{id}/` | да |
| платёжные ключи, SMTP | `*.loc.php` на самом сайте | нет |

### FTP для миграций БД

Для **миграции схемы** FTP не обязателен — достаточно HTTP:

```
GET https://{site}/mapi/act.php?view=1
GET https://{site}/mapi/act.php?run=1
```

FTP нужен для **выкладки кода** через `upd-list` → `MFTP`.

---

## Связка компонентов

```
mapi/upd-list/csv/list.csv  (или sites.yaml)
         │
         ├──► secrets по secrets_ref
         │         └──► MFTP — деплой файлов
         │
         └──► orchestrator миграций
                   └──► mapi/act.php?view|run
                   └──► _schema_migrations (site DB)
                   └──► migration_runs (at_ops)
```

Общий ключ: `id` / `site` / `secrets_ref` — одинаковый во всех слоях.

Связь с документацией сайта: `id: tennis-buchung` ↔ `.docs/sites/tennishalle-offenburg/` (slug по соглашению в команде).

---

## Безопасность

### Обязательные меры

1. **Не коммитить** `ftp_pass`, `bd_pass` в CSV и репозиторий.
2. Вынести из `mapi/upd-list/config.php` в env / файл вне git:
   - `ADMIN_PASSWORD`
   - `DB_PASSWORD_USER`, `DB_PASSWORD_ROOT`
3. `mapi/upd-list` — доступ только VPN / IP whitelist / сильная аутентификация.
4. Secrets-файл — путь **вне DocumentRoot**, права `600`.
5. Ротация паролей — менять только в secrets; `secrets_ref` не трогать.

### Пример `.gitignore`

```
mapi/upd-list/csv/*.secrets.json
/var/www/shared/secrets/
*.secrets.json
.env
```

### Пример шаблона без секретов

`database/migration-registry/sites.secrets.example.json` — структура полей без реальных значений, для документации в git.

---

## Миграция с текущего CSV

Пошагово:

1. Добавить колонку `secrets_ref` в CSV (или перейти на YAML).
2. Вынести `ftp_pass`, `bd_pass` в `sites.secrets.json` на сервере.
3. Очистить пароли из существующих CSV (заменить на пустые или удалить колонки).
4. Доработать `MFTP` / `updateBD` / `replase`: загрузка секретов по `secrets_ref`.
5. Добавить в реестр: `codebase`, `enabled`.
6. Подключить orchestrator миграций к полям `site` + `protocol` (см. [database-migration-strategy.md](database-migration-strategy.md)).

---

## План развития

| Этап | Задача |
|------|--------|
| 1 | Secrets вне CSV; `secrets_ref` в списке |
| 2 | Убрать пароли из git; ротация admin upd-list |
| 3 | Поля `codebase`, `enabled` в реестре |
| 4 | Orchestrator: реестр → `act.php` → лог |
| 5 | Центральный cron: `at_ops.sites` → общий PHP-worker → отдельный процесс сайта |
| 6 | Опционально: YAML в git + PR workflow или `at_ops` |

---

## Глоссарий

| Термин | Значение |
|--------|----------|
| **Реестр** | Список сайтов с публичными метаданными |
| **secrets_ref** | Идентификатор записи в хранилище секретов |
| **SecretsStore** | Класс загрузки и расшифровки секретов по `secrets_ref` |
| **Мастер-ключ** | `AC_SECRETS_KEY` — ключ шифрования файла `.enc`, только в env |
| **upd-list** | `mapi/upd-list` — UI и CSV для массовых операций |
| **MBaseList** | Класс CRUD для CSV-списков |
| **Soft delete** | `enabled: false` без удаления строки |

---

*Документ описывает текущий механизм `mapi/upd-list` и целевое разделение реестра и секретов для мультисайтовой инфраструктуры Active Court.*
