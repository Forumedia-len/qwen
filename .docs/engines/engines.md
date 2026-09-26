# Движки системы (Engines)

## Обзор

Движки системы (`core/engines/`) представляют собой специализированные классы для работы с данными и бизнес-логикой. Они обеспечивают абстракцию над базой данных и предоставляют удобный API для работы с различными сущностями системы.

## Главный класс движков

### Engines.php

**Расположение**: `core/engines/Engines.php`

**Назначение**: Центральный класс, который инициализирует и предоставляет доступ ко всем движкам системы.

#### Основные свойства

```php
class Engines extends \reservation
{
    public $holidays;        // Управление праздниками
    public $bar;             // Управление баром
    public $clients;         // Управление клиентами
    public $areas;           // Управление зонами и кортами
    public $sports;          // Управление видами спорта
    public $stocks;          // Управление запасами
    public $specprice;       // Специальные цены
    public $blocks;          // Управление блоками
    public $tickets;         // Управление билетами
    public $door_code;       // Коды доступа
    public $statistics;      // Статистика
    public $webIo;           // Web I/O
    public $light;           // Управление освещением
    public $pp;              // Предоплата
    public $discount;        // Скидки
    public $extra;           // Дополнительные услуги
    public $nds;             // НДС
    public $coupon;          // Купоны
    public $news;            // Новости
    public $config;          // Конфигурация
    public $accounts;        // Аккаунты
    public $users;           // Пользователи
    public $tickets;         // Билеты
    public $text;            // Тексты
    public $mailing;         // Рассылки
    public $fitness;         // Фитнес
    public $membershipFees;  // Членские взносы
    public $reports;         // Отчеты
    public $payment;         // Платежи
}
```

#### Основные методы

```php
// Инициализация всех движков
public function initialize()

// Завершение работы движков
public function finalize()

// Создание бронирования
public function insertReservation($model, &$error_code = 0)

// Удаление бронирования
public function removeReservation($area_id, $mysql_datetime)

// Присоединение к бронированию
public function joinReservation($reservation_id, $status, $price, $client2_id, $client2_name)

// Отсоединение от бронирования
public function unjoinReservation($reservation_id, $status, $price)

// Получение данных бронирования
public function getReservationDataOrder($client_id, $date_start, $date_end, &$reservation_data = [], $date_finish = false, $type_id = false, $sport_id = false)
```

## Основные движки

### AreasEngine - Управление зонами и кортами

**Расположение**: `core/engines/AreasEngine.php`

**Назначение**: Управление спортивными кортами, зонами и их типами.

Все списочные выборки по умолчанию исключают площадки с заполненным `areas.archived_at`, независимо от фильтра активности. Проверка применяется только при наличии колонки, поэтому старые базы без неё продолжают работать. Параметр `archived=true` выбирает только архивные площадки, а `archived=null` отключает архивный фильтр. Получение площадки по ID отключает фильтр для истории и статистики.

При сохранении ценовых интервалов `00:00` остается началом суток и не преобразуется в `24:00`.

#### Основные методы

```php
// Получение всех активных кортов
public function getActiveAreas($type_id = null, $sport_id = null)

// Получение корта по ID
public function getAreaById($area_id)

// Получение типов кортов
public function getTypes()

// Получение алиаса типа корта
public function getAliasType($type_id)

// Проверка доступности корта
public function isAreaAvailable($area_id, $date, $time, $duration = 60)

// Получение расписания корта
public function getAreaSchedule($area_id, $date)

// Обновление корта
public function updateArea($area_id, $data)

// Создание корта
public function createArea($data)

// Удаление корта
public function deleteArea($area_id)
```

#### Структура данных корта

```php
$area = [
    'id' => 1,
    'name' => 'Корт 1',
    'type_id' => 1,
    'sport_id' => 1,
    'status' => 'active',
    'price_per_hour' => 100,
    'description' => 'Описание корта',
    'features' => ['lighting', 'roof'],
    'created_at' => '2024-01-01 00:00:00',
    'updated_at' => '2024-01-01 00:00:00'
];
```

### ClientsEngine - Управление клиентами

**Расположение**: `core/engines/ClientsEngine.php`

**Назначение**: Управление клиентами, их регистрацией, авторизацией и профилями.

#### Основные методы

```php
// Регистрация нового клиента
public function registerClient($data)

// Авторизация клиента
public function authenticateClient($email, $password)

// Получение клиента по ID
public function getClientById($client_id)

// Получение клиента по email
public function getClientByEmail($email)

// Обновление профиля клиента
public function updateClient($client_id, $data)

// Получение истории бронирований клиента
public function getClientReservations($client_id, $date_from = null, $date_to = null)

// Проверка существования клиента
public function clientExists($email)

// Восстановление пароля
public function resetPassword($email)

// Изменение пароля
public function changePassword($client_id, $old_password, $new_password)

// Блокировка/разблокировка клиента
public function toggleClientStatus($client_id, $status)
```

#### Структура данных клиента

```php
$client = [
    'id' => 1,
    'email' => 'client@example.com',
    'password' => 'hashed_password',
    'first_name' => 'Иван',
    'last_name' => 'Иванов',
    'phone' => '+7 999 123-45-67',
    'status' => 'active',
    'registration_date' => '2024-01-01 00:00:00',
    'last_login' => '2024-01-01 00:00:00',
    'preferences' => [
        'notifications' => true,
        'language' => 'ru'
    ]
];
```

### ReservationsEngine - Управление бронированиями

**Расположение**: `core/engines/ReservationsEngine.php`

**Назначение**: Управление бронированиями кортов, проверка доступности и расчет цен.

#### Основные методы

```php
// Создание бронирования
public function createReservation($data)

// Отмена бронирования
public function cancelReservation($reservation_id, $reason = null)

// Изменение бронирования
public function updateReservation($reservation_id, $data)

// Получение бронирования по ID
public function getReservationById($reservation_id)

// Получение бронирований по дате
public function getReservationsByDate($date, $area_id = null)

// Проверка доступности времени
public function isTimeAvailable($area_id, $date, $time, $duration = 60)

// Расчет стоимости бронирования
public function calculatePrice($area_id, $date, $time, $duration, $client_id = null)

// Получение расписания корта
public function getAreaSchedule($area_id, $date)

// Групповые бронирования
public function createGroupReservation($data)

// Присоединение к бронированию
public function joinReservation($reservation_id, $client_id)

// Отсоединение от бронирования
public function leaveReservation($reservation_id, $client_id)
```

#### Структура данных бронирования

```php
$reservation = [
    'id' => 1,
    'area_id' => 1,
    'client_id' => 1,
    'date' => '2024-01-01',
    'time' => '10:00:00',
    'duration' => 60,
    'price' => 100.00,
    'status' => 'confirmed',
    'payment_status' => 'paid',
    'created_at' => '2024-01-01 00:00:00',
    'updated_at' => '2024-01-01 00:00:00',
    'notes' => 'Дополнительные заметки'
];
```

### UsersEngine - Управление пользователями

**Расположение**: `core/engines/UsersEngine.php`

**Назначение**: Управление системными пользователями, правами доступа и ролями.

#### Основные методы

```php
// Создание пользователя
public function createUser($data)

// Получение пользователя по ID
public function getUserById($user_id)

// Получение пользователя по email
public function getUserByEmail($email)

// Обновление пользователя
public function updateUser($user_id, $data)

// Удаление пользователя
public function deleteUser($user_id)

// Изменение пароля
public function changePassword($user_id, $new_password)

// Проверка прав доступа
public function hasPermission($user_id, $permission)

// Получение ролей пользователя
public function getUserRoles($user_id)

// Назначение роли пользователю
public function assignRole($user_id, $role_id)

// Удаление роли у пользователя
public function removeRole($user_id, $role_id)

// Получение всех пользователей
public function getAllUsers($filters = [])
```

#### Структура данных пользователя

```php
$user = [
    'id' => 1,
    'email' => 'admin@example.com',
    'password' => 'hashed_password',
    'first_name' => 'Администратор',
    'last_name' => 'Системы',
    'role' => 'admin',
    'status' => 'active',
    'permissions' => ['manage_users', 'manage_reservations', 'view_reports'],
    'created_at' => '2024-01-01 00:00:00',
    'last_login' => '2024-01-01 00:00:00'
];
```

### AccountsEngine - Управление аккаунтами

**Расположение**: `core/engines/AccountsEngine.php`

**Назначение**: Управление финансовыми операциями, счетами и платежами.

#### Основные методы

```php
// Создание счета
public function createInvoice($data)

// Получение счета по ID
public function getInvoiceById($invoice_id)

// Обновление статуса счета
public function updateInvoiceStatus($invoice_id, $status)

// Создание платежа
public function createPayment($data)

// Получение платежа по ID
public function getPaymentById($payment_id)

// Получение истории платежей клиента
public function getClientPayments($client_id, $date_from = null, $date_to = null)

// Расчет баланса клиента
public function getClientBalance($client_id)

// Пополнение баланса
public function addFunds($client_id, $amount, $payment_method = 'cash')

// Списание средств
public function deductFunds($client_id, $amount, $reason)

// Получение финансовой отчетности
public function getFinancialReport($date_from, $date_to, $filters = [])
```

#### Структура данных счета

```php
$invoice = [
    'id' => 1,
    'client_id' => 1,
    'reservation_id' => 1,
    'amount' => 100.00,
    'tax_amount' => 18.00,
    'total_amount' => 118.00,
    'status' => 'pending',
    'payment_method' => 'card',
    'due_date' => '2024-01-15',
    'paid_date' => null,
    'created_at' => '2024-01-01 00:00:00',
    'items' => [
        [
            'description' => 'Бронирование корта 1',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total' => 100.00
        ]
    ]
];
```

## Специализированные движки

### SportsEngine - Управление видами спорта

**Расположение**: `core/engines/SportsEngine.php`

**Функциональность**:

- Управление видами спорта
- Настройка правил для каждого вида спорта
- Специфические параметры кортов

### HolidaysEngine - Управление праздниками

**Расположение**: `core/engines/HolidaysEngine.php`

**Функциональность**:

- Управление праздничными днями
- Специальные цены в праздники
- Ограничения бронирования

### DiscountsEngine - Управление скидками

**Расположение**: `core/engines/DiscountsEngine.php`

**Функциональность**:

- Создание и управление скидками
- Применение скидок к бронированиям
- Расчет итоговой стоимости

### CouponEngine - Управление купонами

**Расположение**: `core/engines/CouponEngine.php`

**Функциональность**:

- Создание купонов
- Валидация купонов
- Применение купонов к бронированиям

### ReportsEngine - Генерация отчетов

**Расположение**: `core/modules/reports/engines/ReportsEngine.php`

**Функциональность**:

- Генерация различных отчетов
- Экспорт данных
- Аналитика и статистика

`getReservations()` и `getSeasonTickets()` включают только записи площадок из текущего
справочника `DataService::areas()`. Если SQL возвращает бронирование или абонемент
неактивной, архивной либо недоступной в текущем контексте площадки, запись пропускается
до чтения её периода и формирования строки отчёта.

`ReservationsAsTableReport` строит сетку активных неархивных площадок. Записи без `AreaDto`
и блокировки отсутствующих в справочнике площадок пропускаются. Отсутствующее расписание
дня и список блокировок считаются пустыми; отчёт без площадок возвращает пустую сетку.

## Интеграция движков

### Использование через Service Locator

```php
// Получение движков
$engines = Service::engines();

// Работа с клиентами
$client = $engines->clients->getClientById(1);

// Работа с кортами
$areas = $engines->areas->getActiveAreas();

// Создание бронирования
$reservation = $engines->reservations->createReservation([
    'area_id' => 1,
    'client_id' => 1,
    'date' => '2024-01-01',
    'time' => '10:00:00',
    'duration' => 60
]);
```

### Взаимодействие между движками

```php
// Создание бронирования с проверкой доступности
public function createReservationWithValidation($data)
{
    // Проверка доступности корта
    if (!$this->areas->isAreaAvailable($data['area_id'], $data['date'], $data['time'])) {
        throw new Exception('Корт недоступен в указанное время');
    }

    // Расчет стоимости
    $price = $this->reservations->calculatePrice(
        $data['area_id'],
        $data['date'],
        $data['time'],
        $data['duration'],
        $data['client_id']
    );

    // Применение скидок
    $discount = $this->discounts->calculateDiscount($data['client_id'], $price);
    $finalPrice = $price - $discount;

    // Создание бронирования
    $reservation = $this->reservations->createReservation(array_merge($data, [
        'price' => $finalPrice
    ]));

    // Создание счета
    $this->accounts->createInvoice([
        'client_id' => $data['client_id'],
        'reservation_id' => $reservation['id'],
        'amount' => $finalPrice
    ]);

    return $reservation;
}
```

## Расширение движков

### Создание нового движка

```php
class CustomEngine extends BaseEngine
{
    public function __construct()
    {
        parent::__construct();
        // Инициализация движка
    }

    public function customMethod($param)
    {
        // Логика метода
        return $result;
    }
}
```

### Регистрация движка

```php
// В Engines.php
public $custom;

public function initialize()
{
    // ... другие инициализации

    $this->custom = new CustomEngine();
}
```

## Производительность

### Оптимизации

1. **Кэширование** - Кэширование часто запрашиваемых данных
2. **Ленивая загрузка** - Загрузка данных по требованию
3. **Пакетные операции** - Группировка операций для оптимизации
4. **Индексы БД** - Оптимизация запросов к базе данных

### Мониторинг

- Отслеживание времени выполнения операций
- Мониторинг использования памяти
- Логирование медленных запросов
- Метрики производительности

## Безопасность

### Меры безопасности

1. **Валидация данных** - Проверка всех входных параметров
2. **Подготовленные запросы** - Защита от SQL-инъекций
3. **Проверка прав доступа** - Контроль доступа к данным
4. **Логирование операций** - Аудит всех изменений
5. **Шифрование чувствительных данных** - Защита паролей и персональных данных
