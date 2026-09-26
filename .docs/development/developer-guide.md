# Руководство разработчика Active Court

## Введение

Данное руководство предназначено для разработчиков, работающих с системой Active Court. Оно содержит основные принципы, стандарты кодирования и лучшие практики для разработки и поддержки системы.

## Принципы разработки

### 1. SOLID принципы

- **S** - Single Responsibility Principle (Принцип единственной ответственности)
- **O** - Open/Closed Principle (Принцип открытости/закрытости)
- **L** - Liskov Substitution Principle (Принцип подстановки Лисков)
- **I** - Interface Segregation Principle (Принцип разделения интерфейса)
- **D** - Dependency Inversion Principle (Принцип инверсии зависимостей)

### 2. DRY (Don't Repeat Yourself)

Избегайте дублирования кода, создавайте переиспользуемые компоненты.

### 3. KISS (Keep It Simple, Stupid)

Пишите простой и понятный код, избегайте излишней сложности.

### 4. YAGNI (You Aren't Gonna Need It)

Не добавляйте функциональность, которая не нужна сейчас.

## Стандарты кодирования

### 1. PSR-12 Coding Standards

Следуйте стандартам PSR-12 для форматирования кода:

```php
<?php

declare(strict_types=1);

namespace AC\core\modules\example;

use AC\core\system\module\BaseModule;
use Service;

class ExampleModule extends BaseModule
{
    private const DEFAULT_VALUE = 'default';

    private string $name;
    private array $options;

    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->name = $options['name'] ?? self::DEFAULT_VALUE;
        $this->options = $options;
    }

    public function execute(): array
    {
        $result = $this->processData();

        return [
            'status' => 'success',
            'data' => $result,
        ];
    }

    private function processData(): array
    {
        // Логика обработки данных
        return [];
    }
}
```

### 2. Именование

#### Классы

```php
// ✅ Правильно
class UserController extends BaseController
class ReservationModel extends BaseModel
class PaymentService

// ❌ Неправильно
class user_controller
class reservationmodel
class payment_service
```

#### Методы

```php
// ✅ Правильно
public function getUserById(int $id): ?User
public function createReservation(array $data): bool
public function isUserLoggedIn(): bool

// ❌ Неправильно
public function get_user_by_id($id)
public function CreateReservation($data)
public function user_logged_in()
```

#### Переменные

```php
// ✅ Правильно
$userName = 'John';
$reservationData = [];
$isActive = true;

// ❌ Неправильно
$user_name = 'John';
$reservation_data = [];
$is_active = true;
```

### 3. Комментарии и документация

#### PHPDoc для классов

```php
/**
 * Модуль управления бронированиями
 *
 * Отвечает за создание, редактирование и отмену бронирований
 * спортивных кортов. Поддерживает различные типы кортов
 * и временные слоты.
 *
 * @package AC\core\modules\reservations
 * @author Developer Name <developer@example.com>
 * @since 1.0.0
 */
class ReservationsModule extends BaseModule
{
    // ...
}
```

#### PHPDoc для методов

```php
/**
 * Создает новое бронирование
 *
 * @param array $data Данные бронирования
 * @param int $clientId ID клиента
 * @return int|false ID созданного бронирования или false при ошибке
 * @throws InvalidArgumentException Если данные некорректны
 * @throws DatabaseException При ошибке базы данных
 */
public function createReservation(array $data, int $clientId)
{
    // Реализация
}
```

#### Inline комментарии

```php
// Проверяем доступность корта на указанное время
if (!$this->isCourtAvailable($courtId, $startTime, $endTime)) {
    throw new CourtNotAvailableException('Корт занят на указанное время');
}

// Применяем скидку для VIP клиентов
if ($client->isVip()) {
    $price = $price * 0.9; // 10% скидка
}
```

## Архитектурные паттерны

### 1. Service Locator

Используйте Service Locator для доступа к сервисам:

```php
// ✅ Правильно
$engines = Service::engines();
$clients = $engines->clients;
$user = $clients->getCurrentUser();

// ❌ Неправильно
$clients = new ClientsEngine();
$user = $clients->getCurrentUser();
```

### 2. Dependency Injection

Внедряйте зависимости через конструктор:

```php
class ReservationController extends BaseController
{
    private ReservationsEngine $reservationsEngine;
    private PaymentService $paymentService;

    public function __construct(
        ReservationsEngine $reservationsEngine,
        PaymentService $paymentService
    ) {
        $this->reservationsEngine = $reservationsEngine;
        $this->paymentService = $paymentService;
    }

    public function book(): Response
    {
        $reservation = $this->reservationsEngine->createReservation($data);
        $payment = $this->paymentService->processPayment($reservation);

        return $this->jsonResponse(['success' => true]);
    }
}
```

### 3. Repository Pattern

Используйте репозитории для работы с данными:

```php
class UserRepository
{
    private Query $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    public function findById(int $id): ?User
    {
        $data = $this->query->table('users')
                           ->where('id', $id)
                           ->first();

        return $data ? new User($data) : null;
    }

    public function save(User $user): bool
    {
        if ($user->getId()) {
            return $this->update($user);
        }

        return $this->create($user);
    }
}
```

## Работа с базой данных

### 1. Query Builder

Используйте Query Builder для построения запросов:

```php
// Простой запрос
$users = Service::query()
    ->table('users')
    ->where('status', 'active')
    ->orderBy('created_at', 'DESC')
    ->get();

// Сложный запрос с JOIN
$reservations = Service::query()
    ->table('reservations')
    ->select([
        'reservations.*',
        'courts.name as court_name',
        'clients.name as client_name'
    ])
    ->join('courts', 'courts.id', '=', 'reservations.court_id')
    ->join('clients', 'clients.id', '=', 'reservations.client_id')
    ->where('reservations.status', 'confirmed')
    ->whereBetween('reservations.start_time', [$startDate, $endDate])
    ->get();
```

### 2. Транзакции

Используйте транзакции для критических операций:

```php
public function createReservationWithPayment(array $data): bool
{
    $query = Service::query();

    try {
        $query->beginTransaction();

        // Создание бронирования
        $reservationId = $query->table('reservations')->insert($data);

        // Обработка платежа
        $paymentData = [
            'reservation_id' => $reservationId,
            'amount' => $data['amount'],
            'status' => 'pending'
        ];
        $query->table('payments')->insert($paymentData);

        $query->commit();
        return true;

    } catch (Exception $e) {
        $query->rollback();
        throw $e;
    }
}
```

### 3. Миграции

Полная стратегия миграции 200+ сайтовых БД к эталону (base), `mapi/act.php`, реестр и rollout: **[database-migration-strategy.md](database-migration-strategy.md)**.

Создавайте миграции для изменений структуры БД:

```php
class CreateReservationsTable
{
    public function up(): void
    {
        $query = Service::query();

        $query->raw("
            CREATE TABLE reservations (
                id INT PRIMARY KEY AUTO_INCREMENT,
                client_id INT NOT NULL,
                court_id INT NOT NULL,
                start_time DATETIME NOT NULL,
                end_time DATETIME NOT NULL,
                status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
                total_price DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_client_id (client_id),
                INDEX idx_court_id (court_id),
                INDEX idx_start_time (start_time),
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
                FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $query = Service::query();
        $query->raw("DROP TABLE IF EXISTS reservations");
    }
}
```

## Обработка ошибок

### 1. Исключения

Создавайте специфичные исключения:

```php
class ReservationException extends Exception
{
    // Базовое исключение для бронирований
}

class CourtNotAvailableException extends ReservationException
{
    public function __construct(string $message = 'Корт недоступен')
    {
        parent::__construct($message);
    }
}

class InvalidReservationDataException extends ReservationException
{
    public function __construct(array $errors)
    {
        parent::__construct('Некорректные данные бронирования: ' . json_encode($errors));
    }
}
```

### 2. Обработка исключений

```php
public function createReservation(array $data): int
{
    try {
        // Валидация данных
        $this->validateReservationData($data);

        // Проверка доступности
        if (!$this->isCourtAvailable($data['court_id'], $data['start_time'], $data['end_time'])) {
            throw new CourtNotAvailableException();
        }

        // Создание бронирования
        return $this->insertReservation($data);

    } catch (ValidationException $e) {
        throw new InvalidReservationDataException($e->getErrors());
    } catch (DatabaseException $e) {
        // Логирование ошибки (ОБЯЗАТЕЛЬНО)
        Service::logger('database')->logException($e, [
            'action' => 'reservation_creation',
            'client_id' => $data['client_id'] ?? null,
            'court_id' => $data['court_id'] ?? null
        ]);
        throw $e;
    }
}
```

### 3. Логирование

**ВАЖНО: Использовать только встроенную систему логирования через `Service::logger()`**

```php
// Логирование ошибок (ОБЯЗАТЕЛЬНО для всех ошибок)
Service::logger('database')->logError('Ошибка подключения к БД', [
    'error' => $exception->getMessage(),
    'connection' => 'main_db',
    'client_id' => $clientId
]);

// Логирование исключений (ПРЕДПОЧТИТЕЛЬНО для исключений)
Service::logger('auth')->logException($exception, [
    'user_id' => $userId,
    'action' => 'login_attempt',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

// Логирование информационных сообщений
Service::logger('reservations')->info('Reservation created successfully', [
    'reservation_id' => $reservationId,
    'client_id' => $clientId,
    'court_id' => $courtId
]);

// Логирование предупреждений
Service::logger('validation')->warning('Invalid user data', [
    'user_id' => $userId,
    'field' => 'email',
    'value' => 'invalid_email'
]);
```

#### Правила логирования

1. **ОБЯЗАТЕЛЬНО логировать все ошибки** через `logError()` или `logException()`
2. **Использовать именованные логгеры** для категоризации (`database`, `auth`, `reservations`, etc.)
3. **Включать контекстную информацию** в логи (ID пользователя, ID записи, параметры запроса)
4. **НЕ логировать чувствительные данные** (пароли, токены, персональные данные)
5. **Использовать структурированные данные** в массивах для лучшей читаемости
6. **Использовать короткий импорт Service** - `use Service;` без полного пути
7. **ИСПОЛЬЗОВАТЬ ФУНКЦИЮ lang()** для всех текстов - не хардкодить строки

## Система переводов

### Основные принципы

1. **ИСПОЛЬЗОВАТЬ ТОЛЬКО ФУНКЦИЮ lang()** для получения переводов
2. **НЕ ХАРДКОДИТЬ ТЕКСТЫ** в коде - все тексты должны быть в переводах
3. **ИСПОЛЬЗОВАТЬ ОСМЫСЛЕННЫЕ КЛЮЧИ** для переводов
4. **ГРУППИРОВАТЬ ПЕРЕВОДЫ** по функциональности
5. **ОБЯЗАТЕЛЬНО СОЗДАВАТЬ ПЕРЕВОДЫ НА ТРЕХ ЯЗЫКАХ** (немецкий, русский, английский)
6. **ДОБАВЛЯТЬ ПЕРЕВОДЫ В СООТВЕТСТВУЮЩИЕ ФАЙЛЫ** если таких переводов еще нет
7. **СОЗДАВАТЬ ПЕРЕВОДЫ В МОДУЛЯХ** для специфичных переводов модуля

### Использование функции lang()

```php
// Базовое использование
echo lang('welcome_message');                    // "Willkommen"

// С указанием группы
echo lang('button_save', 'common');              // "Speichern"

// С параметрами
echo lang('user_greeting', 'site', ['name' => 'Max']); // "Hallo Max"

// С значением по умолчанию
echo lang('error_not_found', 'exception', [], 'Not found');

// В шаблонах
<?= lang('booking_title') ?>
<?= lang('price_label', 'common', ['amount' => '50']) ?>
```

### Структура файлов переводов

#### Основные переводы
```
app/lang/
├── common/                   # Общие переводы
│   ├── de/                  # Немецкий язык
│   │   ├── de.ini          # Основные переводы
│   │   ├── message.de.ini  # Сообщения
│   │   ├── exception.de.ini # Ошибки
│   │   └── reservations.de.ini # Бронирования
│   ├── ru/                  # Русский язык
│   │   └── [аналогичные файлы]
│   └── en/                  # Английский язык
│       └── [аналогичные файлы]
├── site/                    # Переводы для сайта
├── admin/                   # Переводы для админки
├── touch/                   # Переводы для сенсорных экранов
└── display/                 # Переводы для дисплеев
```

#### Переводы в модулях
```
core/modules/{module_name}/lang/
├── common/                  # Общие переводы модуля
│   ├── de/                 # Немецкий язык
│   │   ├── de.ini         # Основные переводы модуля
│   │   ├── message.de.ini # Сообщения модуля
│   │   └── exception.de.ini # Ошибки модуля
│   ├── ru/                 # Русский язык
│   │   └── [аналогичные файлы]
│   └── en/                 # Английский язык
│       └── [аналогичные файлы]
├── site/                   # Переводы модуля для сайта
├── admin/                  # Переводы модуля для админки
└── [другие устройства]     # Переводы для других устройств
```

### Примеры в коде

```php
// В контроллерах
public function index()
{
    $data = [
        'title' => lang('page_title', 'site'),
        'welcome' => lang('welcome_message', 'common'),
        'user_name' => lang('user_greeting', 'site', ['name' => $userName])
    ];
    
    return view('index', $data);
}

// В валидации
public function validate($data)
{
    $errors = [];
    
    if (empty($data['email'])) {
        $errors['email'] = lang('field_required', 'validation', ['field' => lang('email', 'common')]);
    }
    
    return $errors;
}

// В исключениях
throw new ValidationException(lang('invalid_data', 'exception'));
```

### Создание новых переводов

#### Правила создания переводов

1. **ОБЯЗАТЕЛЬНО СОЗДАВАТЬ НА ТРЕХ ЯЗЫКАХ** (de, ru, en)
2. **ПРОВЕРЯТЬ СУЩЕСТВУЮЩИЕ ПЕРЕВОДЫ** перед созданием новых
3. **СОБЛЮДАТЬ СТРУКТУРУ ФАЙЛОВ** и названия блоков
4. **ИСПОЛЬЗОВАТЬ ОСМЫСЛЕННЫЕ КЛЮЧИ** для переводов

#### Пример создания переводов

**Шаг 1: Определить место для переводов**
```php
// Если перевод специфичен для модуля
lang('module_specific_message', 'modComm');

// Если перевод общий
lang('general_message', 'common');
```

**Шаг 2: Создать переводы в соответствующих файлах**

Для модуля `modComm`:
```
core/modules/clients/lang/common/de/de.ini:
module_specific_message = "Modulspezifische Nachricht"

core/modules/clients/lang/common/ru/de.ini:
module_specific_message = "Модульное сообщение"

core/modules/clients/lang/common/en/de.ini:
module_specific_message = "Module specific message"
```

Для общих переводов:
```
app/lang/common/de/de.ini:
general_message = "Allgemeine Nachricht"

app/lang/common/ru/de.ini:
general_message = "Общее сообщение"

app/lang/common/en/de.ini:
general_message = "General message"
```

#### Структура INI файлов

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

## Валидация данных

### 1. Валидаторы

Создавайте валидаторы для проверки данных:

```php
class ReservationValidator
{
    public function validate(array $data): array
    {
        $errors = [];

        // Проверка обязательных полей
        if (empty($data['court_id'])) {
            $errors['court_id'] = 'ID корта обязателен';
        }

        if (empty($data['start_time'])) {
            $errors['start_time'] = 'Время начала обязательно';
        }

        if (empty($data['end_time'])) {
            $errors['end_time'] = 'Время окончания обязательно';
        }

        // Проверка времени
        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            $startTime = strtotime($data['start_time']);
            $endTime = strtotime($data['end_time']);

            if ($startTime >= $endTime) {
                $errors['time'] = 'Время окончания должно быть позже времени начала';
            }

            if ($startTime < time()) {
                $errors['start_time'] = 'Время начала не может быть в прошлом';
            }
        }

        return $errors;
    }
}
```

### 2. Использование валидаторов

```php
public function createReservation(array $data): int
{
    $validator = new ReservationValidator();
    $errors = $validator->validate($data);

    if (!empty($errors)) {
        throw new ValidationException($errors);
    }

    // Создание бронирования
    return $this->insertReservation($data);
}
```

## Тестирование

Актуальная инфраструктура — соседний репозиторий `tests/` (Codeception unit suite); код под тестом — из **worktree** приложения, открытого в IDE.  
См. **[development/testing.md](testing.md)** и **`../tests/README.md`** в соседнем репозитории тестов.

Ниже — иллюстративные примеры на PHPUnit для целевого стиля тестов в коде приложения.

### 1. Unit тесты

```php
class ReservationTest extends TestCase
{
    private ReservationsEngine $engine;
    private MockObject $queryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryMock = $this->createMock(Query::class);
        $this->engine = new ReservationsEngine($this->queryMock);
    }

    public function testCreateReservationSuccess(): void
    {
        // Arrange
        $data = [
            'court_id' => 1,
            'client_id' => 1,
            'start_time' => '2024-01-15 14:00:00',
            'end_time' => '2024-01-15 16:00:00'
        ];

        $this->queryMock->expects($this->once())
            ->method('table')
            ->with('reservations')
            ->willReturnSelf();

        $this->queryMock->expects($this->once())
            ->method('insert')
            ->with($data)
            ->willReturn(123);

        // Act
        $result = $this->engine->createReservation($data);

        // Assert
        $this->assertEquals(123, $result);
    }

    public function testCreateReservationWithInvalidData(): void
    {
        // Arrange
        $data = [
            'court_id' => 1,
            'start_time' => '2024-01-15 16:00:00',
            'end_time' => '2024-01-15 14:00:00' // Неправильное время
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->engine->createReservation($data);
    }
}
```

### 2. Интеграционные тесты

```php
class ReservationIntegrationTest extends TestCase
{
    public function testFullReservationFlow(): void
    {
        // Создание тестовых данных
        $client = $this->createTestClient();
        $court = $this->createTestCourt();

        // Создание бронирования
        $reservationData = [
            'client_id' => $client->getId(),
            'court_id' => $court->getId(),
            'start_time' => '2024-01-15 14:00:00',
            'end_time' => '2024-01-15 16:00:00'
        ];

        $engines = Service::engines();
        $reservationId = $engines->reservations->createReservation($reservationData);

        // Проверка результата
        $this->assertGreaterThan(0, $reservationId);

        // Проверка в базе данных
        $reservation = $engines->reservations->getReservation($reservationId);
        $this->assertEquals($client->getId(), $reservation['client_id']);
        $this->assertEquals($court->getId(), $reservation['court_id']);
    }
}
```

## Производительность

### 1. Кэширование

```php
// Кэширование запросов
public function getAvailableCourts(): array
{
    $cacheKey = 'available_courts_' . date('Y-m-d');

    return Service::cache()->remember($cacheKey, 3600, function () {
        return Service::query()
            ->table('courts')
            ->where('status', 'active')
            ->get();
    });
}
```

### 2. Оптимизация запросов

```php
// Использование индексов
$reservations = Service::query()
    ->table('reservations')
    ->where('court_id', $courtId)
    ->where('start_time', '>=', $startDate)
    ->where('start_time', '<=', $endDate)
    ->get();

// Избегание N+1 проблемы
$reservations = Service::query()
    ->table('reservations')
    ->select(['reservations.*', 'courts.name as court_name'])
    ->join('courts', 'courts.id', '=', 'reservations.court_id')
    ->where('client_id', $clientId)
    ->get();
```

### 3. Пагинация

```php
public function getReservations(int $page = 1, int $perPage = 20): array
{
    $offset = ($page - 1) * $perPage;

    $reservations = Service::query()
        ->table('reservations')
        ->orderBy('created_at', 'DESC')
        ->limit($perPage)
        ->offset($offset)
        ->get();

    $total = Service::query()
        ->table('reservations')
        ->count();

    return [
        'data' => $reservations,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => ceil($total / $perPage)
        ]
    ];
}
```

## Безопасность

### 1. Валидация входных данных

```php
public function processUserInput(array $data): array
{
    // Очистка данных
    $cleanData = [];

    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $cleanData[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        } else {
            $cleanData[$key] = $value;
        }
    }

    return $cleanData;
}
```

### 2. Защита от SQL-инъекций

```php
// ✅ Правильно - использование параметризованных запросов
$users = Service::query()
    ->table('users')
    ->where('email', $email)
    ->where('status', $status)
    ->get();

// ❌ Неправильно - прямая конкатенация
$query = "SELECT * FROM users WHERE email = '$email' AND status = '$status'";
```

### 3. CSRF защита

```php
// Генерация CSRF токена
$csrfToken = Service::session()->get('csrf_token');

// Проверка CSRF токена
if (!hash_equals($csrfToken, $_POST['csrf_token'])) {
    throw new SecurityException('Invalid CSRF token');
}
```

## Документация кода

### 1. README файлы

Каждый модуль должен содержать README.md с описанием:

- Назначения модуля
- Основных функций
- Примеров использования
- Конфигурационных параметров

### 2. API документация

Документируйте публичные API:

```php
/**
 * API для работы с бронированиями
 *
 * @api
 */
class ReservationsAPI
{
    /**
     * Создает новое бронирование
     *
     * @api POST /api/reservations
     * @param array $data Данные бронирования
     * @return array Результат операции
     */
    public function createReservation(array $data): array
    {
        // Реализация
    }
}
```

## Заключение

Следование этим принципам и стандартам поможет создать качественный, поддерживаемый и масштабируемый код. Регулярно обновляйте знания и следите за новыми практиками разработки.
