# Модуль mailing

## Назначение
Управление почтовыми шаблонами для системных уведомлений (админ/клиент) с поддержкой нескольких языков и fallback на дефолтный/безъязыковой вариант.

## Структура данных
- Рекомендуемая таблица `letters_templates` с ключом `(mode, alias, language)`:
  - `mode` enum('0','1','2') — получатель (0 admin, 1/2 client и сервисные).
  - `alias` varchar(70) — код шаблона.
  - `title`, `subject` varchar(70), `content` mediumtext.
  - `language` varchar(5) — ISO-код (например `de`, `de-CH`).
  - Дополнительно можно добавить `is_active` tinyint(1) и `updated_at` timestamp для мягкого отключения и аудита (движок их не использует напрямую).
- Legacy-совместимость: если колонки `language` нет, используются записи без языка; движок делает fallback на такие строки.

## Логика языка и fallback
1. Если колонка `language` присутствует и передан язык (GET/POST `language` либо текущая локаль), ищется `(mode, alias, language)`.
2. Затем пробуется дефолтный язык `config('lang')->defaultLanguage`.
3. Финальный fallback — запись без языка `(mode, alias)`.

## API движка `reservation_dispatch`
- `getTemplate(int $mode, string $alias, &$title, &$subject, &$content, ?string $language = null): bool` — выбирает с учетом цепочки fallback.
- `setTemplate(...)` / `insert(...)` / `remove(...)` принимают `$language`, если колонка есть.
- `getTemplates(array &$templates, array $mode = [0,1], bool $asAlias = false, ?string $language = null)` — выборка с фильтром по языку или всех вариантов; при `$asAlias = true` ключ включает язык (`mode_alias_lang`).
- `dispatch($to, int $mode, string $alias, array $data, ?string $language = null)` — подстановка плейсхолдеров и отправка с учетом языка.

## Админ-интерфейс
- Список шаблонов отображает колонку языка и фильтр по языку.
- Редактирование/создание передает выбранный язык, чтобы управлять отдельными вариантами без перезаписи дефолтного.

## Пример цепочки
- Запрос на `dispatch(..., 'order_new_court', ['CLIENT_NAME' => 'Max'], 'de')`
  1) пытается `language = 'de'`,
  2) если нет — `config('lang')->defaultLanguage`,
  3) если нет — запись без языка.

