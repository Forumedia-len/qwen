# Система переводов Active Court

## Обзор

Система переводов Active Court обеспечивает многоязычную поддержку для различных типов устройств и интерфейсов. Система построена на основе INI файлов и предоставляет гибкие возможности для локализации контента.

## Архитектура системы

### Основные компоненты

#### Классы системы переводов
- **`AC\core\system\language\Mess`** - основной класс для работы с переводами
- **`AC\core\system\language\IniFiles`** - класс для работы с INI файлами переводов

##### Локальные правки переводов
- `IniFiles::write()` отслеживает только изменённые пары `section/key` и не создаёт локальных копий без необходимости.
- `IniFiles::updateFile()` формирует локальный `message.{lang}.ini` (в `app/lang/{device}/{lang}`) исключительно из этих изменений. Базовый системный файл не копируется целиком, что упрощает поддержку и уменьшает диффы.
- При полном отсутствии локальных правок файл автоматически удаляется, и система использует общий (shared) вариант переводов.

#### Глобальная функция
- **`lang()`** - функция для получения переводов (определена в `dev/core/main.php`)

### Структура файлов

```
app/lang/
├── common/                   # Общие переводы для всех устройств
│   ├── de/                  # Немецкий язык
│   │   ├── de.ini          # Основные переводы
│   │   ├── message.de.ini  # Сообщения
│   │   ├── exception.de.ini # Сообщения об ошибках
│   │   ├── reservations.de.ini # Переводы для бронирований
│   │   ├── dictionary.de.ini # Словарь терминов
│   │   └── cookie.de.ini   # Переводы для cookies
│   ├── ru/                  # Русский язык
│   │   └── [аналогичные файлы]
│   └── en/                  # Английский язык
│       └── [аналогичные файлы]
├── site/                    # Переводы для веб-сайта
│   ├── de/
│   │   ├── de.ini
│   │   ├── message.de.ini
│   │   └── structure.de.ini
│   ├── ru/
│   │   └── [аналогичные файлы]
│   └── en/
│       └── [аналогичные файлы]
├── admin/                   # Переводы для админ-панели
├── touch/                   # Переводы для сенсорных экранов
└── display/                 # Переводы для информационных дисплеев
```

### Переводы в модулях

Для модульно-специфичных переводов используется структура внутри модулей:

```
core/modules/{module_name}/lang/
├── common/                  # Общие переводы модуля
│   ├── de/                 # Немецкий язык
│   │   ├── message.de.ini # Сообщения модуля
│   │   └── exception.de.ini # Ошибки модуля
│   ├── ru/                 # Русский язык
│   │   ├── message.ru.ini # Сообщения модуля
│   │   └── exception.ru.ini # Ошибки модуля
│   └── en/                 # Английский язык
│       ├── message.en.ini # Сообщения модуля
│       └── exception.en.ini # Ошибки модуля
├── site/                   # Переводы модуля для сайта
├── admin/                  # Переводы модуля для админки
└── [другие устройства]     # Переводы для других устройств
```

**Пример структуры для модуля `clients`:**
```
core/modules/clients/lang/
├── common/
│   ├── de/
│   │   ├── message.de.ini
│   │   └── exception.de.ini
│   ├── ru/
│   │   ├── message.ru.ini
│   │   └── exception.ru.ini
│   └── en/
│       ├── message.en.ini
│       └── exception.en.ini
├── site/
└── admin/
```

### Системные модули

Для системных модулей используется аналогичная структура:

```
core/system/module/{module_name}/lang/
├── common/                  # Общие переводы системного модуля
│   ├── de/                 # Немецкий язык
│   │   ├── message.de.ini # Сообщения модуля
│   │   └── exception.de.ini # Ошибки модуля
│   ├── ru/                 # Русский язык
│   │   ├── message.ru.ini # Сообщения модуля
│   │   └── exception.ru.ini # Ошибки модуля
│   └── en/                 # Английский язык
│       ├── message.en.ini # Сообщения модуля
│       └── exception.en.ini # Ошибки модуля
```

## Использование

### Функция lang()

```php
/**
 * Получить перевод по ключу
 * 
 * @param string $message Ключ перевода
 * @param string|null $group Группа переводов (common, site, admin, etc.)
 * @param array $params Параметры для подстановки
 * @param string|false $default Значение по умолчанию
 * @return string
 */
lang($message, $group = null, $params = [], $default = false)
```

### Примеры использования

#### Базовое использование
```php
// Получить перевод из группы common
echo lang('welcome_message', 'common');                    // "Willkommen"

// Получить перевод без указания группы (поиск во всех группах)
echo lang('button_save');                                  // "Speichern"
```

#### С параметрами
```php
// Подстановка параметров в перевод
echo lang('user_greeting', 'site', ['name' => 'Max']);     // "Hallo Max"

// Множественные параметры
echo lang('booking_confirmation', 'reservations', [
    'court' => 'Tennisplatz 1',
    'date' => '15.12.2024',
    'time' => '14:00'
]);
```

#### С значением по умолчанию
```php
// Если перевод не найден, используется значение по умолчанию
echo lang('error_not_found', 'exception', [], 'Not found');

// Автоматическое форматирование ключа как fallback
echo lang('some_missing_key'); // "Some Missing Key"
```

#### В шаблонах
```php
<!-- В PHP шаблонах -->
<h1><?= lang('page_title', 'site') ?></h1>
<p><?= lang('welcome_message', 'common') ?></p>

<!-- С параметрами -->
<p><?= lang('user_greeting', 'site', ['name' => $userName]) ?></p>
```

## Файлы переводов

### Формат INI файлов

Переводы хранятся в INI файлах с поддержкой секций:

```ini
; Основные переводы (de.ini)
button_save = "Speichern"
button_cancel = "Abbruch"
welcome_message = "Willkommen"

[buttons]
save = "Speichern"
cancel = "Abbruch"

[messages]
welcome = "Willkommen"
goodbye = "Auf Wiedersehen"
```

### Типы файлов переводов

#### de.ini - Основные переводы
Содержит основные элементы интерфейса:
- Кнопки и элементы управления
- Общие термины
- Навигационные элементы

#### message.de.ini - Сообщения
Содержит информационные сообщения:
- Уведомления пользователей
- Сообщения об успешных операциях
- Информационные тексты

#### exception.de.ini - Ошибки
Содержит сообщения об ошибках:
- Сообщения валидации
- Ошибки системы
- Предупреждения

#### reservations.de.ini - Бронирования
Специализированные переводы для модуля бронирований:
- Термины бронирования
- Сообщения о статусе
- Описания услуг

#### dictionary.de.ini - Словарь
Словарь терминов и определений:
- Технические термины
- Определения понятий
- Специализированная лексика

## Группы переводов

### common/
Общие переводы, используемые во всех типах устройств:
- Базовые элементы интерфейса
- Общие термины
- Стандартные сообщения

### site/
Переводы для веб-сайта:
- Элементы навигации сайта
- Контент страниц
- Формы и интерфейсы

### admin/
Переводы для админ-панели:
- Элементы управления
- Административные функции
- Отчеты и статистика

### touch/
Переводы для сенсорных экранов:
- Крупные элементы интерфейса
- Простые инструкции
- Интерактивные элементы

### display/
Переводы для информационных дисплеев:
- Статическая информация
- Расписания
- Информационные сообщения

## Правила работы с переводами

### 1. Обязательное использование lang()
```php
// ✅ ПРАВИЛЬНО
echo lang('welcome_message', 'common');

// ❌ НЕПРАВИЛЬНО
echo 'Willkommen';
```

### 2. Не хардкодить тексты
```php
// ✅ ПРАВИЛЬНО
$errors['email'] = lang('field_required', 'validation', ['field' => lang('email', 'common')]);

// ❌ НЕПРАВИЛЬНО
$errors['email'] = 'Email field is required';
```

### 3. Использовать осмысленные ключи
```php
// ✅ ПРАВИЛЬНО
lang('user_registration_success', 'site')
lang('booking_confirmation_email', 'reservations')

// ❌ НЕПРАВИЛЬНО
lang('msg1', 'site')
lang('text_123', 'common')
```

### 4. Группировать переводы по функциональности
```php
// ✅ ПРАВИЛЬНО
lang('button_save', 'common')           // Общие элементы
lang('page_title', 'site')              // Элементы сайта
lang('admin_dashboard', 'admin')        // Элементы админки
lang('booking_form', 'reservations')    // Элементы бронирований
```

### 5. Добавлять параметры для динамических текстов
```php
// ✅ ПРАВИЛЬНО
lang('user_greeting', 'site', ['name' => $userName])
lang('booking_confirmation', 'reservations', [
    'court' => $courtName,
    'date' => $date,
    'time' => $time
])
```

### 6. Поддерживать все языки
При добавлении нового перевода обязательно добавлять его для всех поддерживаемых языков:
- Немецкий (de)
- Русский (ru)
- Английский (en)

### 7. Создавать переводы в соответствующих файлах
- Проверять существующие переводы перед созданием новых
- Добавлять переводы в соответствующие файлы и блоки
- Соблюдать структуру и названия файлов

### 8. Создавать переводы в модулях для специфичных переводов
- Использовать структуру `core/modules/{module_name}/lang/`
- Соблюдать ту же структуру файлов и блоков
- Создавать переводы на всех трех языках

## Создание новых переводов

### Процесс создания переводов

#### 1. Определение места для переводов

```php
// Если перевод специфичен для модуля
lang('module_specific_message', 'modComm');

// Если перевод общий
lang('general_message', 'common');

// Если перевод для конкретного устройства
lang('site_specific_message', 'site');
```

#### 2. Создание переводов в файлах

**Для модуля `modComm` (специфичные переводы):**
```
core/modules/clients/lang/common/de/message.de.ini:
module_specific_message = "Modulspezifische Nachricht"

core/modules/clients/lang/common/ru/message.ru.ini:
module_specific_message = "Модульное сообщение"

core/modules/clients/lang/common/en/message.en.ini:
module_specific_message = "Module specific message"
```

**Для общих переводов:**
```
app/lang/common/de/de.ini:
general_message = "Allgemeine Nachricht"

app/lang/common/ru/ru.ini:
general_message = "Общее сообщение"

app/lang/common/en/en.ini:
general_message = "General message"
```

#### 3. Структура INI файлов

```ini
; Основные переводы (de.ini)
button_save = "Speichern"
welcome_message = "Willkommen"

; Сообщения (message.de.ini)
success_operation = "Operation erfolgreich abgeschlossen"
error_occurred = "Ein Fehler ist aufgetreten"

; Ошибки (exception.de.ini)
validation_error = "Validierungsfehler"
not_found_error = "Nicht gefunden"

; Секции в файлах
[buttons]
save = "Speichern"
cancel = "Abbruch"

[messages]
welcome = "Willkommen"
goodbye = "Auf Wiedersehen"
```

### Правила создания переводов

1. **ОБЯЗАТЕЛЬНО СОЗДАВАТЬ НА ТРЕХ ЯЗЫКАХ** (de, ru, en)
2. **ПРОВЕРЯТЬ СУЩЕСТВУЮЩИЕ ПЕРЕВОДЫ** перед созданием новых
3. **СОБЛЮДАТЬ СТРУКТУРУ ФАЙЛОВ** и названия блоков
4. **ИСПОЛЬЗОВАТЬ ОСМЫСЛЕННЫЕ КЛЮЧИ** для переводов
5. **СОЗДАВАТЬ ПЕРЕВОДЫ В МОДУЛЯХ** для специфичных переводов
6. **СОБЛЮДАТЬ ЕДИНООБРАЗИЕ** терминологии

## Интеграция с другими системами

### Логирование
```php
// Логирование с переводами
Service::logger('modComm')->info(lang('transaction_success', 'common'), [
    'action' => 'deposit',
    'client_id' => $clientId
]);

// Логирование ошибок с переводами
Service::logger('modComm')->logException($e, lang('error_processing_deposit', 'exception'), [
    'action' => 'deposit',
    'client_id' => $clientId
]);
```

### Валидация
```php
// Сообщения валидации с переводами
public function validate($data)
{
    $errors = [];
    
    if (empty($data['email'])) {
        $errors['email'] = lang('field_required', 'validation', [
            'field' => lang('email', 'common')
        ]);
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = lang('invalid_email_format', 'validation');
    }
    
    return $errors;
}
```

### Исключения
```php
// Исключения с переводами
throw new ValidationException(lang('invalid_data', 'exception'));
throw new NotFoundException(lang('user_not_found', 'exception', ['id' => $userId]));
```

## Лучшие практики

### 1. Планирование переводов
- Создавайте ключи переводов заранее
- Используйте единообразную структуру ключей
- Документируйте сложные переводы

### 2. Тестирование
- Проверяйте переводы на всех поддерживаемых языках
- Тестируйте с длинными текстами
- Проверяйте корректность подстановки параметров

### 3. Производительность
- Кэширование переводов
- Минимизация обращений к файлам переводов
- Оптимизация поиска переводов

### 4. Поддержка
- Регулярно обновляйте переводы
- Следите за консистентностью терминологии
- Ведите документацию изменений

## Расширение системы

### Добавление нового языка
1. Создать папку для нового языка в каждой группе переводов
2. Создать файлы переводов с соответствующими суффиксами
3. Обновить конфигурацию системы
4. Добавить поддержку в класс Mess

### Добавление новой группы переводов
1. Создать папку для новой группы
2. Добавить поддержку в класс Mess
3. Обновить автозагрузку переводов
4. Документировать новую группу

---

**Примечание**: Система переводов является критически важной частью проекта и требует тщательного планирования и поддержки для обеспечения качественной локализации. 