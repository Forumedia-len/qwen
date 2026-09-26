# Сервис истории операций

## Обзор

Сервис истории операций (HistoryService) - это мощный инструмент для отслеживания всех взаимодействий и событий в рамках одной операции в системе Active Court. Сервис позволяет фиксировать запросы, ответы, ошибки и другие полезные сообщения, предоставляя полную картину выполнения операций.

## Основные возможности

- **Отслеживание операций** - автоматическое создание и управление жизненным циклом операций
- **Хранение в памяти** - все события сохраняются в массиве без записи в лог-файлы
- **Логирование по требованию** - возможность принудительного логирования всей истории
- **Критические ошибки** - автоматическое логирование критических ошибок в файл
- **Интеграция с modComm** - автоматическое отслеживание межмодульных запросов
- **HTTP мониторинг** - отслеживание HTTP запросов и ответов
- **База данных** - отслеживание SQL запросов с параметрами и временем выполнения
- **Обработка ошибок** - детальное отслеживание исключений и ошибок
- **Экспорт данных** - сохранение истории в различных форматах (JSON, TXT, HTML)
- **Статистика** - получение аналитики по событиям и операциям

## Архитектура

### Основные компоненты

1. **HistoryService** - основной сервис для управления историей операций
2. **HistoryTrait** - трейт для интеграции в другие классы
3. **Интеграция с Service Locator** - доступ через `Service::history()`

### Интеграция с системой

Сервис интегрирован в Service Locator и доступен через:

```php
$history = Service::history($operationId);
```

## Класс HistoryService

### Конструктор

```php
public function __construct(string $historyId = 'common', array $metadata = [])
```

**Параметры:**
- `$historyId` - уникальный идентификатор операции (по умолчанию 'common')
- `$metadata` - дополнительные метаданные для инициализации

**Описание:** Инициализирует сервис истории с указанным ID и создает уникальный идентификатор для истории.

### Основные методы

#### startOperation()

```php
public function startOperation(array $metadata = []): self
```

Начинает новую операцию, устанавливает флаг активности и инициализирует метаданные с временными метками.

**Параметры:**
- `$metadata` - метаданные операции (тип, пользователь, время начала и т.д.)

**Возвращает:** `self` для цепочки вызовов

#### endOperation()

```php
public function endOperation(array $resultData = []): self
```

Завершает операцию, вычисляет длительность, добавляет финальное событие и сбрасывает состояние.

**Параметры:**
- `$resultData` - данные результата операции

**Возвращает:** `self` для цепочки вызовов

#### addEvent()

```php
public function addEvent(string $type, string $description, array $data = [], string $level = 'info'): self
```

Добавляет событие в историю операции с временными метками, информацией о памяти и статусе активности.

**Параметры:**
- `$type` - тип события (например: 'user_action', 'system_event')
- `$description` - описание события на русском языке
- `$data` - дополнительные данные события
- `$level` - уровень важности ('info', 'warning', 'error', 'debug')

**Возвращает:** `self` для цепочки вызовов

### Специализированные методы логирования

#### addModCommRequest()

```php
public function addModCommRequest(string $module, string $action, array $requestData = [], array $requestMetadata = []): self
```

Логирует межмодульные коммуникации в системе.

**Параметры:**
- `$module` - модуль назначения
- `$action` - действие в модуле
- `$requestData` - данные запроса
- `$requestMetadata` - метаданные запроса

#### addHttpRequest() / addHttpResponse()

```php
public function addHttpRequest(string $method, string $url, array $headers = [], array $data = []): self
public function addHttpResponse(int $statusCode, array $headers = [], array $data = [], float $responseTime = 0): self
```

Логируют HTTP запросы и ответы с детальной информацией.

#### addDatabaseQuery()

```php
public function addDatabaseQuery(string $query, array $params = [], float $executionTime = 0, array $result = []): self
```

Логирует SQL запросы с параметрами, временем выполнения и предварительным просмотром результата.

#### addError() / addCriticalError()

```php
public function addError(string $errorMessage, array $errorData = [], ?Exception $exception = null): self
public function addCriticalError(string $errorMessage, array $errorData = [], ?Exception $exception = null): self
```

Логируют ошибки с детальной информацией. Критические ошибки дополнительно записываются в лог-файл.

### Методы получения данных

#### getHistory()

```php
public function getHistory(): array
```

Возвращает структурированную историю включающую метаданные, статистику и все события.

#### getStatistics()

```php
public function getStatistics(): array
```

Анализирует события и предоставляет статистику по типам и уровням важности.

#### getEventsByType() / getEventsByLevel()

```php
public function getEventsByType(string $type): array
public function getEventsByLevel(string $level): array
```

Фильтруют события по типу или уровню важности для анализа.

### Методы экспорта

#### export()

```php
public function export(string $format = 'json'): string
```

Поддерживает экспорт в JSON, текстовом и HTML форматах для различных целей анализа и отчетности.

#### saveToFile()

```php
public function saveToFile(string $format = 'json'): void
```

Сохраняет экспортированную историю в файл в указанном формате.

## Трейт HistoryTrait

### Обзор

`HistoryTrait` предоставляет методы для автоматического логирования событий в различных компонентах системы. Позволяет легко интегрировать функциональность отслеживания истории в любой класс.

### Основные методы

#### initHistory()

```php
protected function initHistory(?string $operationId = null): self
```

Создает новый экземпляр HistoryService и инициализирует его с указанным ID операции.

#### startOperationInHistory() / endOperationInHistory()

```php
protected function startOperationInHistory(array $metadata = []): self
protected function endOperationInHistory(array $resultData = []): self
```

Управляют жизненным циклом операции в истории.

#### addHistoryEvent()

```php
protected function addHistoryEvent(string $type, string $description, array $data = [], string $level = 'info'): self
```

Создает новое событие с указанным типом, описанием и данными.

#### Специализированные методы логирования

- `logHttpRequest()` - логирует HTTP запросы
- `logHttpResponse()` - логирует HTTP ответы
- `logDatabaseQuery()` - логирует запросы к базе данных
- `addErrorInHistory()` - логирует ошибки
- `logCriticalError()` - логирует критические ошибки с записью в файл
- `logCustomEvent()` - логирует пользовательские события

#### Методы получения данных

- `getHistory()` - получает полную историю операций
- `getOperationId()` - получает ID текущей операции
- `isOperationActive()` - проверяет активность операции
- `getOperationStatistics()` - получает статистику по операциям

#### Методы экспорта

- `saveOperationHistory()` - сохраняет историю операции в файл
- `exportOperationHistory()` - экспортирует историю в выбранном формате
- `logOperationHistory()` - логирует всю историю операции в файл

## Использование

### Базовое использование

```php
// Получение сервиса
$history = Service::history();

// Начало операции
$history->startOperation([
    'operation_type' => 'user_registration',
    'user_email' => 'user@example.com'
]);

// Добавление событий
$history->addEvent('validation_started', 'Начата валидация данных');
$history->addEvent('database_insert', 'Пользователь добавлен в БД', [
    'user_id' => 123
]);

// Завершение операции
$history->endOperation([
    'result' => 'success',
    'user_id' => 123
]);

// Сохранение в файл
$history->saveToFile('json');
```

### Использование трейта

```php
class MyController extends BaseController
{
    use HistoryTrait;
    
    public function processRequest()
    {
        $this->startOperationInHistory(['type' => 'data_processing']);
        
        $this->addHistoryEvent('data_loaded', 'Данные загружены');
        $this->logHttpRequest('POST', '/api/process', [], ['data' => 'example']);
        $this->logDatabaseQuery('SELECT * FROM users', [], 0.002, ['count' => 100]);
        
        $this->endOperationInHistory(['status' => 'success']);
    }
}
```

### Логирование ошибок

```php
try {
    // Код, который может вызвать ошибку
    $result = $this->processData();
    $this->addHistoryEvent('data_processed', 'Данные успешно обработаны', $result);
} catch (Exception $e) {
    $this->addErrorInHistory('Ошибка обработки данных', [
        'input_data' => $inputData
    ], $e);
    
    // Для критических ошибок
    $this->logCriticalError('Критическая ошибка обработки', [
        'input_data' => $inputData
    ], $e);
}
```

### HTTP мониторинг

```php
// Логирование запроса
$this->logHttpRequest('POST', '/api/users', [
    'Content-Type' => 'application/json'
], ['email' => 'user@example.com']);

// Логирование ответа
$this->logHttpResponse(200, [
    'Content-Type' => 'application/json'
], ['user_id' => 123], 0.15);
```

### Мониторинг базы данных

```php
$startTime = microtime(true);
$result = $this->db->query($sql, $params);
$executionTime = microtime(true) - $startTime;

$this->logDatabaseQuery($sql, $params, $executionTime, $result);
```

## Типы событий

### Основные события

- `operation_started` - начало операции
- `operation_ended` - завершение операции
- `custom_*` - пользовательские события

### Системные события

- `modComm_request` - межмодульные запросы
- `http_request` - HTTP запросы
- `http_response` - HTTP ответы
- `database_query` - запросы к базе данных
- `error` - ошибки
- `critical_error` - критические ошибки

### Уровни важности

- `info` - информационные сообщения
- `warning` - предупреждения
- `error` - ошибки
- `debug` - отладочная информация

## Форматы экспорта

### JSON

```php
$jsonData = $history->export('json');
// Возвращает JSON строку с историей операций
```

### Текст

```php
$textData = $history->export('txt');
// Возвращает читаемый текстовый отчет
```

### HTML

```php
$htmlData = $history->export('html');
// Возвращает HTML страницу с историей операций
```

## Интеграция с системой

### Service Locator

Сервис доступен через Service Locator:

```php
$history = Service::history('unique_operation_id');
```

### Автоматическое логирование

При использовании трейта `HistoryTrait` история автоматически инициализируется и управляется:

```php
class MyService
{
    use HistoryTrait;
    
    public function __construct()
    {
        $this->initHistory('my_service_operation');
    }
}
```

### Логирование критических ошибок

Критические ошибки автоматически записываются в лог-файлы через интегрированный Logger:

```php
$this->logCriticalError('Критическая ошибка', $context, $exception);
// Автоматически записывается в файл через Logger
```

## Лучшие практики

### 1. Инициализация истории

Всегда инициализируйте историю в начале операции:

```php
$this->initHistory('meaningful_operation_name');
$this->startOperationInHistory(['type' => 'operation_type']);
```

### 2. Структурированные события

Используйте понятные типы событий и описания:

```php
// Хорошо
$this->addHistoryEvent('user_registration_started', 'Начата регистрация пользователя', [
    'email' => $email,
    'source' => 'web_form'
]);

// Плохо
$this->addHistoryEvent('event', 'Something happened', []);
```

### 3. Обработка ошибок

Всегда логируйте ошибки с контекстом:

```php
try {
    $result = $this->processData($input);
    $this->addHistoryEvent('data_processed', 'Данные успешно обработаны', $result);
} catch (ValidationException $e) {
    $this->addErrorInHistory('Ошибка валидации', ['input' => $input], $e);
} catch (Exception $e) {
    $this->logCriticalError('Неожиданная ошибка', ['input' => $input], $e);
}
```

### 4. Завершение операций

Всегда завершайте операции для корректного расчета статистики:

```php
try {
    $this->startOperationInHistory(['type' => 'data_processing']);
    // ... выполнение операции ...
    $this->endOperationInHistory(['status' => 'success', 'result' => $result]);
} catch (Exception $e) {
    $this->endOperationInHistory(['status' => 'error', 'error' => $e->getMessage()]);
    throw $e;
}
```

### 5. Экспорт и сохранение

Используйте экспорт для анализа и отладки:

```php
// Сохранение в файл для анализа
$this->saveOperationHistory('json');

// Экспорт для передачи
$exportedData = $this->exportOperationHistory('html');
```

## Производительность

### Оптимизация памяти

- События хранятся в памяти до завершения операции
- Автоматическая очистка после завершения операции
- Возможность ограничения количества событий

### Ленивая загрузка

- История инициализируется только при необходимости
- Логирование происходит только при активной истории

## Безопасность

### Логирование чувствительных данных

Избегайте логирования паролей, токенов и других чувствительных данных:

```php
// Хорошо
$this->addHistoryEvent('user_login', 'Пользователь вошел в систему', [
    'user_id' => $userId,
    'ip_address' => $ipAddress
]);

// Плохо
$this->addHistoryEvent('user_login', 'Пользователь вошел в систему', [
    'password' => $password, // НЕ ЛОГИРОВАТЬ!
    'token' => $token        // НЕ ЛОГИРОВАТЬ!
]);
```

### Ограничение доступа

История операций доступна только авторизованным пользователям и администраторам.

## Мониторинг и отладка

### Просмотр активных операций

```php
if ($this->isOperationActive()) {
    $operationId = $this->getOperationId();
    $statistics = $this->getOperationStatistics();
}
```

### Анализ производительности

```php
$history = $this->getHistory();
$duration = $history['duration'];
$memoryUsage = $history['memory_usage'];
$totalEvents = $history['total_events'];
```

### Отладка проблем

```php
// Получение событий определенного типа
$errors = $this->getEventsByType('error');

// Получение критических событий
$criticalEvents = $this->getEventsByLevel('error');

// Экспорт для анализа
$this->exportOperationHistory('html');
```

## Заключение

Сервис истории операций предоставляет мощный инструмент для отслеживания, отладки и аудита операций в системе Active Court. Благодаря простой интеграции через трейт и гибким возможностям экспорта, он становится незаменимым помощником для разработчиков и администраторов системы.

Используйте сервис для:
- Отладки сложных операций
- Мониторинга производительности
- Аудита действий пользователей
- Анализа ошибок и проблем
- Создания отчетов о работе системы 