# Быстрые команды Active Court для PowerShell

## Модель работы

PowerShell-профиль устанавливается из любого обновлённого worktree Active Court, но не привязывает команды к этому проекту. При каждом вызове рабочий проект определяется заново из текущего каталога:

1. Выполняется `git rev-parse --show-toplevel`.
2. Проверяется наличие `config.php`, `app/` и `core/`.
3. Команда запускается из найденного worktree.
4. Если текущий каталог не относится к Active Court, выполнение прекращается с ошибкой.

Например, один профиль работает с несколькими worktree:

```powershell
Set-Location E:\work\web\base-ac\mc
actest
# AC_APP_ROOT: E:\work\web\base-ac\mc

Set-Location E:\work\web\base-ac\dev
actest
# AC_APP_ROOT: E:\work\web\base-ac\dev
```

Worktree, из которого выполнялся `install-profile.ps1`, является только источником версионируемого файла. Установщик копирует его в `%LOCALAPPDATA%\ActiveCourt\PowerShell\active-court-profile.ps1`, а пользовательский `$PROFILE` загружает нейтральную установленную копию. Worktree установки не становится проектом по умолчанию.

## Справка

Справка быстрых команд хранится в установленном локальном профиле. Поэтому `acprofile h`, `acprofile help`, `acprofile -h` и `acprofile --help` работают независимо от текущего каталога и наличия `install-profile.ps1` в выбранном worktree. Операции обновления и управления профилем по-прежнему используют установщик из текущего worktree. Язык справки определяется локальной настройкой профиля.

Все быстрые команды принимают одинаковые варианты вызова справки:

```powershell
actest h
acdocs help
acroot -h
acprofile --help
```

Поддерживаются `h`, `help`, `-h` и `--help`. Справка не выполняет целевое действие команды. Для прямых CLI-entrypoint доступны те же формы:

```powershell
php bin/actest h
php bin/check-documentation h
composer test -- h
composer docs:check -- h
```

## `actest`

`actest`:

1. Определяет текущий Active Court worktree.
2. Находит его `bin/actest`.
3. Добавляет аргумент `--app-root=<текущий-worktree>`.
4. Передаёт остальные аргументы без изменений.
5. Сохраняет exit code в `$LASTEXITCODE`.

```powershell
actest
actest unit
actest --skip-group lockers
actest integration --group area-prices-db
```

Папка тестов выбирается самим `bin/actest`: `.actest.loc.json`, `AC_TESTS_ROOT`, соседний `../tests/` или Composer-пакет. Папка тестов и тестируемый worktree являются разными значениями:

- `AC_TESTS_ROOT` указывает, откуда загружается тестовый код;
- `AC_APP_ROOT` указывает, какой проект тестируется.

## `acdocs`

`acdocs` запускает `php bin/check-documentation` текущего worktree:

```powershell
Set-Location E:\work\web\base-ac\mc
acdocs
```

Команда не проверяет документацию `dev`, если вызвана из `mc`.

## `acroot`

`acroot` переходит из любого подкаталога текущего worktree в его корень:

```powershell
Set-Location E:\work\web\base-ac\mc\core\modules
acroot
# E:\work\web\base-ac\mc
```

Вне Active Court worktree команда завершается ошибкой.

## `acprofile`

`acprofile` запускает `install-profile.ps1` текущего worktree. Без параметров команда эквивалентна `-Update`: сравнивает версии, копирует профиль в `%LOCALAPPDATA%` и сразу загружает его в текущий сеанс.

```powershell
Set-Location E:\work\web\base-ac\mc
acprofile
```

Доступные режимы:

| Режим | Поведение |
|---|---|
| `acprofile -Status`, `acprofile -s` | Показать исходную и установленную версии, пути и состояние обновления |
| `acprofile -Reload`, `acprofile -r` | Перечитать установленную копию в текущем сеансе без копирования из Git |
| `acprofile -Update`, `acprofile -u` | Установить версию текущего worktree и перечитать текущий сеанс |
| `acprofile -Update -Force`, `acprofile -u -f` | Разрешить downgrade или установку изменённого файла без повышения версии |
| `acprofile -Rollback`, `acprofile -b` | Восстановить резервную копию предыдущей установленной версии |
| `acprofile -Uninstall`, `acprofile -x` | Удалить loader и локальные установленные файлы |
| `acprofile h` | Показать режимы и параметры установщика |

### Язык профиля

Профиль содержит переводы `en`, `de` и `ru`; основной язык и fallback - английский. Выбор сохраняется отдельно от версии профиля в `%LOCALAPPDATA%\ActiveCourt\PowerShell\settings.json` и не сбрасывается при обновлении или rollback.

```powershell
acprofile -u -l en
acprofile -u -l de
acprofile -u -l ru
acprofile -u -l auto
```

Параметры `-Language` и `-l` принимают также корректный произвольный код локали, например `fr` или `pt-BR`. Пока для него нет встроенного перевода, сообщения используют английский fallback. Значение `auto` выбирает `en`, `de` или `ru` по `$PSUICulture`, иначе также использует английский.

Примеры:

```powershell
acprofile -Status
acprofile -Reload
acprofile -Update
acprofile -Rollback
acprofile -Uninstall
```

Установщик обновляет только блок между маркерами `Active Court CLI` и сохраняет остальное содержимое профиля. При установке старый блок `Active Court actest` удаляется, чтобы прежняя функция не переопределяла новые команды.

## Версии и открытые сеансы

Версия задаётся в исходнике:

```powershell
$global:ActiveCourtProfileVersion = '1.3.0'
```

При изменении поведения профиля версия повышается и фиксируется тем же Git-коммитом. Если исходная версия ниже установленной, обновление запрещено без `-Force`. Если версия совпадает, но SHA-256 содержимого отличается, установщик требует повысить версию или явно использовать `-Force` для разработки.

`-Update`, обычный `acprofile` и `-Rollback` загружают результат в текущий PowerShell-сеанс. Другие уже открытые сеансы сохраняют прежние функции в памяти. Для них выполните:

```powershell
acprofile -Reload
```

Новые сеансы автоматически загружают текущую установленную копию из `%LOCALAPPDATA%`.

## Автодополнение

После загрузки профиля PowerShell дополняет названия команд по `Tab`. Профиль дополнительно регистрирует варианты аргументов для Windows PowerShell 5 и PowerShell 7:

```powershell
ac<Tab>
acprofile -<Tab>
acprofile -l <Tab>
actest <Tab>
```

Для `acprofile` предлагаются полные и короткие параметры, для языка - `en`, `de`, `ru`, `auto`. Для `actest` список suite автоматически читается из `tests/*.suite.yml`, а группы и специальные команды - из `actest.json` выбранного репозитория тестов. Также предлагаются основные параметры launcher и Codeception.

`Tab` перебирает варианты, а `Ctrl+Space` показывает меню с локализованными пояснениями. В PowerShell 7 можно дополнительно включить прогнозирование команд из истории; профиль не меняет эту пользовательскую настройку автоматически:

```powershell
Set-PSReadLineOption -PredictionSource History
Set-PSReadLineOption -PredictionViewStyle ListView
```

## Диагностика

Проверить текущий каталог и выбранный worktree:

```powershell
Get-Location
git rev-parse --show-toplevel
Resolve-ActiveCourtProjectRoot
Resolve-ActiveCourtCommand -Name actest
Get-Command actest
acprofile -Status
```

Если результат указывает не на ожидаемый проект, сначала проверьте рабочий каталог терминала IDE. Команды намеренно не переключаются на другой worktree автоматически.
