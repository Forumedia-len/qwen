# Краткий справочник по движкам системы

## Обзор

Движки системы (`core/engines/`) предоставляют специализированные классы для работы с данными и бизнес-логикой. Все движки доступны через `Service::engines()`.

## Основные движки

### Engines.php - Главный класс

**Доступ**: `Service::engines()`

```php
$engines = Service::engines();

// Доступ к конкретным движкам
$areas = $engines->areas;
$clients = $engines->clients;
$reservations = $engines->reservations;
```

### AreasEngine - Управление зонами и кортами

**Доступ**: `Service::engines()->areas`

#### Основные методы

```php
// Получение кортов
$areas->getActiveAreas($type_id = null, $sport_id = null)
$areas->getAreaById($area_id)

// Типы кортов
$areas->getTypes()
$areas->getAliasType($type_id)

// Проверка доступности
$areas->isAreaAvailable($area_id, $date, $time, $duration = 60)

// CRUD операции
$areas->createArea($data)
$areas->updateArea($area_id, $data)
$areas->deleteArea($area_id)
```

#### Примеры использования

```php
// Получить все активные корты
$activeAreas = Service::engines()->areas->getActiveAreas();

// Проверить доступность корта
$isAvailable = Service::engines()->areas->isAreaAvailable(1, '2024-01-01', '10:00:00');

// Получить информацию о корте
$area = Service::engines()->areas->getAreaById(1);
```

### ClientsEngine - Управление клиентами

**Доступ**: `Service::engines()->clients`

#### Основные методы

```php
// Регистрация и авторизация
$clients->registerClient($data)
$clients->authenticateClient($email, $password)

// Получение клиентов
$clients->getClientById($client_id)
$clients->getClientByEmail($email)

// Обновление данных
$clients->updateClient($client_id, $data)

// История и статистика
$clients->getClientReservations($client_id, $date_from = null, $date_to = null)

// Управление статусом
$clients->toggleClientStatus($client_id, $status)
```

#### Примеры использования

```php
// Регистрация нового клиента
$clientData = [
    'email' => 'client@example.com',
    'password' => 'password123',
    'first_name' => 'Иван',
    'last_name' => 'Иванов'
];
$client = Service::engines()->clients->registerClient($clientData);

// Получить клиента по email
$client = Service::engines()->clients->getClientByEmail('client@example.com');

// Получить историю бронирований клиента
$reservations = Service::engines()->clients->getClientReservations(1, '2024-01-01', '2024-12-31');
```

### ReservationsEngine - Управление бронированиями

**Доступ**: `Service::engines()->reservations`

#### Основные методы

```php
// Создание и управление
$reservations->createReservation($data)
$reservations->cancelReservation($reservation_id, $reason = null)
$reservations->updateReservation($reservation_id, $data)

// Получение данных
$reservations->getReservationById($reservation_id)
$reservations->getReservationsByDate($date, $area_id = null)

// Проверка доступности
$reservations->isTimeAvailable($area_id, $date, $time, $duration = 60)

// Расчет стоимости
$reservations->calculatePrice($area_id, $date, $time, $duration, $client_id = null)

// Групповые операции
$reservations->createGroupReservation($data)
$reservations->joinReservation($reservation_id, $client_id)
$reservations->leaveReservation($reservation_id, $client_id)
```

#### Примеры использования

```php
// Создать бронирование
$reservationData = [
    'area_id' => 1,
    'client_id' => 1,
    'date' => '2024-01-01',
    'time' => '10:00:00',
    'duration' => 60
];
$reservation = Service::engines()->reservations->createReservation($reservationData);

// Проверить доступность времени
$isAvailable = Service::engines()->reservations->isTimeAvailable(1, '2024-01-01', '10:00:00');

// Рассчитать стоимость
$price = Service::engines()->reservations->calculatePrice(1, '2024-01-01', '10:00:00', 60, 1);

// Получить бронирования на дату
$reservations = Service::engines()->reservations->getReservationsByDate('2024-01-01');
```

### UsersEngine - Управление пользователями

**Доступ**: `Service::engines()->users`

#### Основные методы

```php
// CRUD операции
$users->createUser($data)
$users->getUserById($user_id)
$users->getUserByEmail($email)
$users->updateUser($user_id, $data)
$users->deleteUser($user_id)

// Управление паролями
$users->changePassword($user_id, $new_password)

// Права доступа
$users->hasPermission($user_id, $permission)
$users->getUserRoles($user_id)
$users->assignRole($user_id, $role_id)
$users->removeRole($user_id, $role_id)
```

#### Примеры использования

```php
// Создать пользователя
$userData = [
    'email' => 'admin@example.com',
    'password' => 'admin123',
    'first_name' => 'Администратор',
    'role' => 'admin'
];
$user = Service::engines()->users->createUser($userData);

// Проверить права доступа
$hasPermission = Service::engines()->users->hasPermission(1, 'manage_users');

// Получить роли пользователя
$roles = Service::engines()->users->getUserRoles(1);
```

### AccountsEngine - Управление аккаунтами

**Доступ**: `Service::engines()->accounts`

#### Основные методы

```php
// Управление счетами
$accounts->createInvoice($data)
$accounts->getInvoiceById($invoice_id)
$accounts->updateInvoiceStatus($invoice_id, $status)

// Управление платежами
$accounts->createPayment($data)
$accounts->getPaymentById($payment_id)

// Финансовые операции
$accounts->getClientBalance($client_id)
$accounts->addFunds($client_id, $amount, $payment_method = 'cash')
$accounts->deductFunds($client_id, $amount, $reason)

// Отчетность
$accounts->getFinancialReport($date_from, $date_to, $filters = [])
```

#### Примеры использования

```php
// Создать счет
$invoiceData = [
    'client_id' => 1,
    'reservation_id' => 1,
    'amount' => 100.00,
    'payment_method' => 'card'
];
$invoice = Service::engines()->accounts->createInvoice($invoiceData);

// Получить баланс клиента
$balance = Service::engines()->accounts->getClientBalance(1);

// Пополнить баланс
Service::engines()->accounts->addFunds(1, 50.00, 'cash');

// Получить финансовый отчет
$report = Service::engines()->accounts->getFinancialReport('2024-01-01', '2024-12-31');
```

## Специализированные движки

### SportsEngine - Управление видами спорта

```php
$sports = Service::engines()->sports;
$sportsTypes = $sports->getSports();
```

### HolidaysEngine - Управление праздниками

```php
$holidays = Service::engines()->holidays;
$isHoliday = $holidays->isHoliday('2024-01-01');
```

### DiscountsEngine - Управление скидками

```php
$discounts = Service::engines()->discount;
$discount = $discounts->calculateDiscount($client_id, $amount);
```

### CouponEngine - Управление купонами

```php
$coupons = Service::engines()->coupon;
$isValid = $coupons->validateCoupon($code);
```

### ReportsEngine - Генерация отчетов

```php
$reports = Service::engines()->reports;
$report = $reports->generateReport($type, $params);
```

## Интеграция движков

### Комплексные операции

```php
// Создание бронирования с проверкой доступности и расчетом цены
public function createReservationWithValidation($data)
{
    $engines = Service::engines();

    // Проверка доступности корта
    if (!$engines->areas->isAreaAvailable($data['area_id'], $data['date'], $data['time'])) {
        throw new Exception('Корт недоступен в указанное время');
    }

    // Расчет стоимости
    $price = $engines->reservations->calculatePrice(
        $data['area_id'],
        $data['date'],
        $data['time'],
        $data['duration'],
        $data['client_id']
    );

    // Применение скидок
    $discount = $engines->discount->calculateDiscount($data['client_id'], $price);
    $finalPrice = $price - $discount;

    // Создание бронирования
    $reservation = $engines->reservations->createReservation(array_merge($data, [
        'price' => $finalPrice
    ]));

    // Создание счета
    $engines->accounts->createInvoice([
        'client_id' => $data['client_id'],
        'reservation_id' => $reservation['id'],
        'amount' => $finalPrice
    ]);

    return $reservation;
}
```

### Обработка ошибок

```php
try {
    $reservation = Service::engines()->reservations->createReservation($data);
} catch (Exception $e) {
    // Логирование ошибки (ОБЯЗАТЕЛЬНО)
Service::logger('reservations')->logException($e, [
    'action' => 'create_reservation',
    'client_id' => $clientId,
    'court_id' => $courtId
]);

    // Возврат ошибки пользователю
    throw new ReservationException('Не удалось создать бронирование');
}
```

## Производительность

### Кэширование

```php
// Кэширование результатов движков
$cacheKey = "areas_active_" . md5(serialize($filters));
$areas = Service::cache()->remember($cacheKey, 3600, function() use ($filters) {
    return Service::engines()->areas->getActiveAreas($filters);
});
```

### Пакетные операции

```php
// Пакетное получение данных
$areaIds = [1, 2, 3, 4, 5];
$areas = Service::engines()->areas->getAreasByIds($areaIds);
```

## Мониторинг

### Логирование операций

**ВАЖНО: Использовать только встроенную систему логирования через `Service::logger()`**

```php
// Логирование операций движков
Service::logger('reservations')->info('Создание бронирования', [
    'area_id' => $areaId,
    'client_id' => $clientId,
    'date' => $date,
    'time' => $time
]);

// Логирование ошибок (ОБЯЗАТЕЛЬНО)
Service::logger('reservations')->logError('Ошибка создания бронирования', [
    'error' => $e->getMessage(),
    'area_id' => $areaId,
    'client_id' => $clientId
]);

// Логирование исключений (ПРЕДПОЧТИТЕЛЬНО)
Service::logger('reservations')->logException($exception, [
    'action' => 'create_reservation',
    'area_id' => $areaId
]);
```

### Метрики производительности

```php
// Отслеживание времени выполнения
$startTime = microtime(true);
$result = Service::engines()->reservations->createReservation($data);
$executionTime = microtime(true) - $startTime;

Service::logger('performance')->info('Время создания бронирования', [
    'execution_time' => $executionTime,
    'reservation_id' => $result['id']
]);
```
