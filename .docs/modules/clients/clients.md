# Модуль клиентов (Clients)

## Обзор

Модуль клиентов отвечает за управление клиентской базой системы Active Court. Модуль обеспечивает регистрацию, авторизацию, управление профилями и историей клиентов, а также интеграцию с системой бронирований. Включает в себя систему управления личными счетами (PrivateAccount) и API для межмодульного общения через modComm.

## HTML-представление имён

Имена в существующих записях и сессиях могут быть исходным текстом или уже содержать HTML-сущности.
`ClientsEngine::loginBar()` сохраняет гостевые имя и фамилию после `StringHelper::shield()`;
модель регистрации также может включать `StringValidator::useShielding` для этих полей.

Для вывода таких значений используется:

```php
StringHelper::shield($name, doubleEncode: false);
```

Четвёртый необязательный параметр `doubleEncode` добавлен с сохранением прежнего значения по умолчанию `true`.
Первые три параметра, обработка пробелов, кодировка и флаги старых вызовов не изменены.
Стандартные флаги helper поддерживают HTML5, поэтому `&apos;` не превращается в `&amp;apos;` при отключённом повторном кодировании.
Это исправляет отображение `O'Brian`, `Anne & Marie` и повторную отправку гостевой формы.

Режим применяется в шапке, общей форме входа, выборе игроков site/touch и подготовке имён для расписания.
Исходный HTML при этом экранируется: содержимое имени выводится текстом и не создаёт элементы страницы.
Для JavaScript, URL и других контекстов нужны соответствующие способы кодирования.

Записи БД и контракт гостевых сессий не мигрируются. Уже накопленные несколько слоёв кодирования не декодируются рекурсивно.
Передача существующих значений в платежные DTO не меняется этим исправлением.
Результаты проверки: [имена и дата бронирования](../../archive/reports/booking-names-date-fix-report.md).

## Основные возможности

### Таблицы бронирований клиента

`clients_reservations.php` используется сайтом и виджетом. Таблицы бронирований и абонементов находятся
в отдельных контейнерах `.table-adaptive` с горизонтальной прокруткой и доступом с клавиатуры.
Минимальная ширина таблицы бронирований — 900 px, абонементов — 720 px; при достаточной ширине они заполняют контейнер.
Даты и интервалы времени не переносятся, длинные описания переносятся внутри своих колонок.
Высота строк зависит от содержимого. Прокрутка остаётся внутри таблицы и не расширяет страницу.
Правила ограничены `.fm-client-reservations` и не меняют другие таблицы личного кабинета.
Источник общих стилей — CDN `sass/site/content/pages/_client_reservations.scss`; widget импортирует этот partial.
Для площадок с полным локальным CSS тот же partial включается в локальную сборку `default.min.css`.

### 👤 Управление профилями

- Регистрация новых клиентов
- Редактирование профилей
- Управление личными данными
- Загрузка аватаров

### 🔐 Авторизация и безопасность

- Система входа/выхода
- Восстановление паролей
- Двухфакторная аутентификация
- Управление сессиями

### 📊 История и аналитика

- История бронирований
- Статистика посещений
- Предпочтения клиентов
- Рейтинг активности

### 💳 Финансовые операции

- История платежей
- Баланс клиента
- Система кредитов
- Скидки и бонусы

### 🔌 API и межмодульное общение

- modComm контроллеры для API
- Стандартизированные ответы
- Валидация и авторизация
- Интеграция с другими модулями

## Структура модуля

```
core/modules/clients/
├── ClientsModule.php           # Основной класс модуля
├── controllers/                # Контроллеры
│   ├── modComm/               # Контроллеры межмодульного общения
│   │   └── PrivateAccountController.php
│   ├── admin/                 # Административные контроллеры
│   ├── ClientsController.php
│   ├── ClientsAuthController.php
│   └── ClientsControllerTouch.php
├── models/                     # Модели данных
│   ├── ClientsModel.php
│   ├── ClientsRegistrationModel.php
│   └── PrivateAccountTransactionModel.php
├── engines/                    # Движки модуля
│   └── PrivateAccountTransactionEngine.php
├── dto/                        # Объекты передачи данных
│   └── PrivateAccountTransactionDto.php
├── views/                      # Представления
│   ├── profile.php
│   ├── history.php
│   └── settings.php
├── config/                     # Конфигурация
├── lang/                       # Переводы
├── tables/                     # Структуры таблиц БД
└── sql/                        # SQL скрипты
```

## Основной класс модуля

### ClientsModule

```php
class ClientsModule extends BaseModule
{
    public function exec($action = null, $key = null, $controllerName = null)
    {
        // Проверка авторизации
        if (!$this->checkAuth()) {
            return Service::redirect()->to('/login');
        }

        return parent::exec($action, $key, $controllerName);
    }

    private function checkAuth()
    {
        $engines = Service::engines();
        return $engines->clients->isLoggedIn();
    }
}
```

## Движок клиентов

### ClientsEngine

```php
class ClientsEngine
{
    public $current_client_data = [];

    public function __construct()
    {
        $this->loadCurrentClient();
    }

    public function logIn($username, $password)
    {
        $query = Service::query();

        $client = $query->table('clients')
                       ->where('email', $username)
                       ->orWhere('phone', $username)
                       ->first();

        if ($client && password_verify($password, $client['password'])) {
            $this->setCurrentClient($client);
            $this->logLogin($client['id']);
            return $client;
        }

        return false;
    }

    public function logOut()
    {
        $session = Service::session();
        $session->delete('client_id');
        $session->delete('client_data');
        $this->current_client_data = [];
    }

    public function register($data)
    {
        // Валидация данных
        if (!$this->validateRegistrationData($data)) {
            return false;
        }

        // Проверка уникальности email/телефона
        if ($this->clientExists($data['email'], $data['phone'])) {
            return false;
        }

        // Создание клиента
        $query = Service::query();
        $clientId = $query->table('clients')->insert([
            'name' => $data['name'],
            'surname' => $data['surname'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if ($clientId) {
            $this->sendWelcomeEmail($data['email']);
            return $clientId;
        }

        return false;
    }

    public function updateProfile($clientId, $data)
    {
        $query = Service::query();

        $updateData = [
            'name' => $data['name'],
            'surname' => $data['surname'],
            'phone' => $data['phone'],
            'birth_date' => $data['birth_date'],
            'gender' => $data['gender'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Обновление аватара
        if (isset($data['avatar'])) {
            $avatarPath = $this->uploadAvatar($data['avatar']);
            $updateData['avatar'] = $avatarPath;
        }

        return $query->table('clients')
                    ->where('id', $clientId)
                    ->update($updateData);
    }

    public function getClientHistory($clientId, $filters = [])
    {
        $query = Service::query();

        $query->table('reservations')
              ->where('client_id', $clientId)
              ->orderBy('start', 'DESC');

        // Применение фильтров
        if (isset($filters['date_from'])) {
            $query->where('start', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('start', '<=', $filters['date_to']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }

    public function getClientStatistics($clientId)
    {
        $query = Service::query();

        // Общее количество бронирований
        $totalReservations = $query->table('reservations')
                                  ->where('client_id', $clientId)
                                  ->count();

        // Общая сумма потраченная
        $totalSpent = $query->table('reservations')
                           ->where('client_id', $clientId)
                           ->where('status', 'completed')
                           ->sum('total_price');

        // Любимый корт
        $favoriteCourt = $query->table('reservations')
                              ->select('court_id, COUNT(*) as count')
                              ->where('client_id', $clientId)
                              ->groupBy('court_id')
                              ->orderBy('count', 'DESC')
                              ->first();

        return [
            'total_reservations' => $totalReservations,
            'total_spent' => $totalSpent,
            'favorite_court' => $favoriteCourt,
            'average_rating' => $this->getAverageRating($clientId)
        ];
    }

    public function changePassword($clientId, $oldPassword, $newPassword)
    {
        $query = Service::query();

        $client = $query->table('clients')
                       ->where('id', $clientId)
                       ->first();

        if (!$client || !password_verify($oldPassword, $client['password'])) {
            return false;
        }

        return $query->table('clients')
                    ->where('id', $clientId)
                    ->update([
                        'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
    }

    public function recoverPassword($email)
    {
        $query = Service::query();

        $client = $query->table('clients')
                       ->where('email', $email)
                       ->first();

        if (!$client) {
            return false;
        }

        // Генерация токена восстановления
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $query->table('password_resets')->insert([
            'email' => $email,
            'token' => $token,
            'expires_at' => $expires
        ]);

        // Отправка email
        $this->sendPasswordResetEmail($email, $token);

        return true;
    }

    public function resetPassword($token, $newPassword)
    {
        $query = Service::query();

        $reset = $query->table('password_resets')
                      ->where('token', $token)
                      ->where('expires_at', '>', date('Y-m-d H:i:s'))
                      ->first();

        if (!$reset) {
            return false;
        }

        // Обновление пароля
        $result = $query->table('clients')
                       ->where('email', $reset['email'])
                       ->update([
                           'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                           'updated_at' => date('Y-m-d H:i:s')
                       ]);

        if ($result) {
            // Удаление использованного токена
            $query->table('password_resets')
                  ->where('token', $token)
                  ->delete();
        }

        return $result;
    }
}
```

## Контроллеры

### ClientsController

```php
class ClientsController extends BaseController
{
    public function index()
    {
        $engines = Service::engines();
        $clientData = $engines->clients->current_client_data;

        $view = Service::view();
        $view->setData('client', $clientData);
        return $view->render('clients/dashboard');
    }

    public function profile()
    {
        $engines = Service::engines();
        $clientId = $engines->clients->current_client_data['client_id'];

        $view = Service::view();
        $view->setData('client', $engines->clients->current_client_data);
        return $view->render('clients/profile');
    }

    public function updateProfile()
    {
        $engines = Service::engines();
        $clientId = $engines->clients->current_client_data['client_id'];
        $data = Service::request()->getPost();

        $result = $engines->clients->updateProfile($clientId, $data);

        if ($result) {
            Service::session()->setFlash('success', 'Профиль обновлен');
        } else {
            Service::session()->setFlash('error', 'Ошибка обновления профиля');
        }

        return Service::redirect()->to('/clients/profile');
    }

    public function history()
    {
        $engines = Service::engines();
        $clientId = $engines->clients->current_client_data['client_id'];

        $filters = Service::request()->getGet();
        $history = $engines->clients->getClientHistory($clientId, $filters);

        $view = Service::view();
        $view->setData('history', $history);
        return $view->render('clients/history');
    }

    public function statistics()
    {
        $engines = Service::engines();
        $clientId = $engines->clients->current_client_data['client_id'];

        $stats = $engines->clients->getClientStatistics($clientId);

        $view = Service::view();
        $view->setData('statistics', $stats);
        return $view->render('clients/statistics');
    }
}
```

### AuthController

```php
class AuthController extends BaseController
{
    public function login()
    {
        if (Service::request()->getMethod() === 'POST') {
            $data = Service::request()->getPost();

            $engines = Service::engines();
            $result = $engines->clients->logIn($data['username'], $data['password']);

            if ($result) {
                return Service::redirect()->to('/clients/dashboard');
            } else {
                Service::session()->setFlash('error', 'Неверные учетные данные');
            }
        }

        return $this->render('auth/login');
    }

    public function register()
    {
        if (Service::request()->getMethod() === 'POST') {
            $data = Service::request()->getPost();

            $engines = Service::engines();
            $result = $engines->clients->register($data);

            if ($result) {
                Service::session()->setFlash('success', 'Регистрация успешна');
                return Service::redirect()->to('/auth/login');
            } else {
                Service::session()->setFlash('error', 'Ошибка регистрации');
            }
        }

        return $this->render('auth/register');
    }

    public function logout()
    {
        $engines = Service::engines();
        $engines->clients->logOut();

        return Service::redirect()->to('/');
    }

    public function recoverPassword()
    {
        if (Service::request()->getMethod() === 'POST') {
            $email = Service::request()->getPost('email');

            $engines = Service::engines();
            $result = $engines->clients->recoverPassword($email);

            if ($result) {
                Service::session()->setFlash('success', 'Инструкции отправлены на email');
            } else {
                Service::session()->setFlash('error', 'Email не найден');
            }
        }

        return $this->render('auth/recover-password');
    }
}
```

## Модели данных

### ClientModel

```php
class ClientModel extends BaseModel
{
    protected $table = 'clients';

    protected $fillable = [
        'name',
        'surname',
        'email',
        'phone',
        'password',
        'birth_date',
        'gender',
        'avatar',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['password'];

    public function reservations()
    {
        return $this->hasMany('ReservationModel', 'client_id');
    }

    public function payments()
    {
        return $this->hasMany('PaymentModel', 'client_id');
    }

    public function getFullName()
    {
        return $this->name . ' ' . $this->surname;
    }

    public function getAvatarUrl()
    {
        if ($this->avatar) {
            return UPLOAD_URL . 'avatars/' . $this->avatar;
        }
        return '/assets/images/default-avatar.png';
    }
}
```

## API методы

### Авторизация

```php
POST /api/clients/login
{
    "username": "user@example.com",
    "password": "password123"
}
```

### Регистрация

```php
POST /api/clients/register
{
    "name": "Иван",
    "surname": "Иванов",
    "email": "ivan@example.com",
    "phone": "+79001234567",
    "password": "password123",
    "birth_date": "1990-01-01",
    "gender": "male"
}
```

### Обновление профиля

```php
PUT /api/clients/profile
{
    "name": "Иван",
    "surname": "Иванов",
    "phone": "+79001234567",
    "birth_date": "1990-01-01",
    "gender": "male"
}
```

### История бронирований

```php
GET /api/clients/history?date_from=2024-01-01&date_to=2024-12-31
```

## Интеграция с другими модулями

### Бронирования

```php
// Получение истории бронирований клиента
public function getClientReservations($clientId)
{
    $engines = Service::engines();
    $reservations = [];

    $engines->reservations->getReservationDataByClient($clientId, null, $reservations);

    return $reservations;
}
```

### Платежи

```php
// Получение истории платежей клиента
public function getClientPayments($clientId)
{
    $query = Service::query();

    return $query->table('payments')
                ->where('client_id', $clientId)
                ->orderBy('created_at', 'DESC')
                ->get();
}
```

### Уведомления

```php
// Отправка уведомлений клиенту
public function sendClientNotification($clientId, $type, $data)
{
    $notificationEngine = Service::engines()->notifications;
    $client = $this->getClient($clientId);

    $notificationEngine->sendClientNotification($client, $type, $data);
}
```

## Конфигурация

### Настройки модуля

```php
// config/clients.php
return [
    'min_password_length' => 8,
    'require_email_confirmation' => true,
    'allow_phone_registration' => true,
    'avatar_max_size' => 2048, // KB
    'session_timeout' => 3600, // seconds
    'max_login_attempts' => 5,
    'lockout_duration' => 900, // seconds
];
```

### Права доступа

```php
// Определение прав клиентов
define('CLIENT_RIGHT_BASIC', 1);
define('CLIENT_RIGHT_PREMIUM', 2);
define('CLIENT_RIGHT_VIP', 3);
```

## Шаблоны

### Профиль клиента

```php
<!-- app/tpl/site/views/clients/profile.php -->
<div class="client-profile">
    <div class="profile-header">
        <img src="<?= $client->getAvatarUrl() ?>" class="avatar">
        <h2><?= $client->getFullName() ?></h2>
    </div>

    <form method="POST" action="/clients/update-profile" enctype="multipart/form-data">
        <div class="form-group">
            <label>Имя:</label>
            <input type="text" name="name" value="<?= $client['name'] ?>" required>
        </div>

        <div class="form-group">
            <label>Фамилия:</label>
            <input type="text" name="surname" value="<?= $client['surname'] ?>" required>
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" value="<?= $client['email'] ?>" readonly>
        </div>

        <div class="form-group">
            <label>Телефон:</label>
            <input type="tel" name="phone" value="<?= $client['phone'] ?>">
        </div>

        <div class="form-group">
            <label>Дата рождения:</label>
            <input type="date" name="birth_date" value="<?= $client['birth_date'] ?>">
        </div>

        <div class="form-group">
            <label>Пол:</label>
            <select name="gender">
                <option value="male" <?= $client['gender'] == 'male' ? 'selected' : '' ?>>Мужской</option>
                <option value="female" <?= $client['gender'] == 'female' ? 'selected' : '' ?>>Женский</option>
            </select>
        </div>

        <div class="form-group">
            <label>Аватар:</label>
            <input type="file" name="avatar" accept="image/*">
        </div>

        <button type="submit">Сохранить изменения</button>
    </form>
</div>
```

## События и хуки

### События клиентов

```php
// События, которые можно перехватить
'client.registered'      // Клиент зарегистрирован
'client.logged_in'       // Клиент вошел в систему
'client.logged_out'      // Клиент вышел из системы
'client.profile_updated' // Профиль обновлен
'client.password_changed' // Пароль изменен
```

### Обработчики событий

```php
// Пример обработчика события
class ClientEventHandler
{
    public function onClientRegistered($clientId)
    {
        // Отправка приветственного email
        $this->sendWelcomeEmail($clientId);

        // Создание начального баланса
        $this->createInitialBalance($clientId);
    }
}
```

## Тестирование

### Unit тесты

```php
class ClientTest extends TestCase
{
    public function testClientRegistration()
    {
        $data = [
            'name' => 'Иван',
            'surname' => 'Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+79001234567',
            'password' => 'password123'
        ];

        $engines = Service::engines();
        $result = $engines->clients->register($data);

        $this->assertTrue($result > 0);
    }

    public function testClientLogin()
    {
        $engines = Service::engines();
        $result = $engines->clients->logIn('ivan@example.com', 'password123');

        $this->assertNotFalse($result);
    }
}
```

## Лучшие практики

### 1. Безопасность

- Всегда хешируйте пароли
- Используйте HTTPS для передачи данных
- Валидируйте все входные данные
- Ограничивайте количество попыток входа

### 2. Конфиденциальность

- Не храните пароли в открытом виде
- Шифруйте персональные данные
- Соблюдайте GDPR требования
- Логируйте доступ к персональным данным

### 3. Производительность

- Кэшируйте данные клиентов
- Используйте индексы в базе данных
- Оптимизируйте запросы истории
- Пагинируйте большие списки

### 4. UX/UI

- Предоставляйте понятные сообщения об ошибках
- Используйте прогрессивную регистрацию
- Добавьте автозаполнение форм
- Реализуйте "запомнить меня"

## Заключение

Модуль клиентов обеспечивает полный цикл управления клиентской базой, от регистрации до аналитики. Интеграция с другими модулями позволяет создавать комплексные решения для управления спортивными объектами.

## Система личных счетов (PrivateAccount)

### Обзор

Система PrivateAccount предоставляет функциональность для управления личными счетами клиентов, включая пополнение, списание средств и отслеживание транзакций.

### Основные компоненты

#### PrivateAccountTransactionEngine

Основной движок для управления транзакциями личных счетов:

```php
// Получение движка
$transactionEngine = getEngine('privateAccountTransaction');

// Пополнение счета
$transactionId = $transactionEngine->deposit($transactionDto);

// Списание средств
$transactionId = $transactionEngine->withdraw($transactionDto);

// Получение транзакций клиента
$transactions = $transactionEngine->getTransactionsByClient($clientId, 'in');
```

#### PrivateAccountTransactionDto

DTO объект для передачи данных о транзакциях:

```php
use AC\core\modules\clients\entities\dto\PrivateAccountTransactionDto;

// Создание DTO для новой транзакции
$transactionDto = PrivateAccountTransactionDto::forCreate([
    'client_id' => 123,
    'type_direction' => 'in',
    'type_code' => 'deposit',
    'amount' => 100.00,
    'related_data' => ['source' => 'bank_transfer']
]);

// Создание DTO из массива данных
$transactionDto = PrivateAccountTransactionDto::fromArray($data);
```

### API через modComm

#### PrivateAccountController

Контроллер для API взаимодействия с личными счетами:

```php
// Получение транзакций клиента
$response = $service->sendRequest('clients', 'PrivateAccount/transactions', [
    'clientId' => 123,
    'typeDirection' => 'in'
]);

// Пополнение счета
$response = $service->sendRequest('clients', 'PrivateAccount/deposit', [
    'clientId' => 123,
    'typeCode' => 'deposit',
    'amount' => 100.00
]);

// Списание средств
$response = $service->sendRequest('clients', 'PrivateAccount/withdraw', [
    'clientId' => 123,
    'typeCode' => 'payment',
    'amount' => 50.00
]);
```

#### Доступные методы API

- **transactions** - получение истории транзакций
- **deposit** - пополнение счета
- **withdraw** - списание средств
- **listIn** - получение только пополнений
- **listOut** - получение только списаний
- **changeStatus** - изменение статуса транзакции

### Статусы транзакций

При переходе транзакции в статус `succeeded` движок сохраняет в `related_data`
снимки личного счёта клиента: `balance_before` — баланс до операции и
`balance_after` — баланс после операции. Снимки добавляются как числовые
значения с точностью до двух знаков и не заменяют дополнительные данные,
переданные при создании транзакции. Код использует БД-транзакцию и блокировки,
а таблицы `clients` и `private_account_transactions` используют InnoDB. Поэтому
изменение баланса, снимков и статуса выполняется атомарно с блокировкой строк от
параллельных операций. Для старых сайтов движки фактических таблиц необходимо
проверить отдельно. Полное описание: [private-account.md](private-account.md).
Там же зафиксирован план перехода на отдельные поля `balance_before` и
`balance_after` типа `DECIMAL(12,2)`, переноса исторических JSON-снимков и
отдельной миграции `clients.prepayment_sum` с `DOUBLE` на `DECIMAL(12,2)`.

```php
// Допустимые статусы
$allowedStatuses = [
    'succeeded',  // Успешно выполнено
    'cancelled',  // Отменено
    'pending',    // В ожидании
    'failed',     // Ошибка
    'reversed'    // Отменено/возврат
];
```

### Типы направлений

```php
// Направления транзакций
$directions = [
    'in'   => 'Пополнение счета',
    'out'  => 'Списание средств'
];
```

### Примеры использования

#### Создание транзакции пополнения

```php
// Создание DTO
$transactionDto = PrivateAccountTransactionDto::forCreate([
    'client_id' => $clientId,
    'type_direction' => 'in',
    'type_code' => 'deposit',
    'amount' => $amount,
    'related_id' => $paymentId,
    'related_data' => [
        'payment_method' => 'bank_transfer',
        'reference' => $reference
    ]
]);

// Добавление транзакции
$transactionId = $transactionEngine->addDeposit($transactionDto);

// Завершение транзакции
if ($transactionId) {
    $transactionEngine->succeeded($transactionId);
}
```

#### Получение баланса клиента

```php
// Получение всех транзакций
$allTransactions = $transactionEngine->getTransactionsByClient($clientId);

// Расчет баланса
$balance = 0;
foreach ($allTransactions as $transaction) {
    if ($transaction['status'] === 'succeeded') {
        if ($transaction['type_direction'] === 'in') {
            $balance += $transaction['amount'];
        } else {
            $balance -= $transaction['amount'];
        }
    }
}
```

### Интеграция с платежными системами

```php
// Callback от платежной системы
public function handlePaymentCallback($paymentData)
{
    $transactionEngine = getEngine('privateAccountTransaction');
    
    // Создание транзакции
    $transactionDto = PrivateAccountTransactionDto::forCreate([
        'client_id' => $paymentData['client_id'],
        'type_direction' => 'in',
        'type_code' => 'payment_system',
        'amount' => $paymentData['amount'],
        'external_id' => $paymentData['payment_id'],
        'related_data' => $paymentData
    ]);
    
    // Добавление транзакции
    $transactionId = $transactionEngine->addTransaction($transactionDto);
    
    // Обновление статуса при успешной оплате
    if ($paymentData['status'] === 'success') {
        $transactionEngine->succeeded($transactionId);
    }
}
```

### Логирование и мониторинг

```php
// Логирование операций
Service::logger('private_account')->info('Transaction created', [
    'transaction_id' => $transactionId,
    'client_id' => $clientId,
    'amount' => $amount,
    'type' => $typeDirection
]);

// Мониторинг ошибок
Service::logger('private_account')->error('Transaction failed', [
    'client_id' => $clientId,
    'error' => $errorMessage,
    'data' => $transactionData
]);
```

## API и межмодульное общение (modComm)

### Обзор

Модуль clients предоставляет API для межмодульного общения через систему modComm. Это позволяет другим модулям взаимодействовать с клиентами и их личными счетами через стандартизированный интерфейс.

### PrivateAccountController

Основной контроллер для API взаимодействия с личными счетами клиентов.

#### Структура контроллера

```php
<?php

namespace AC\core\modules\clients\controllers\modComm;

use AC\core\system\modules\modComm\controllers\ModCommController;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;

class PrivateAccountController extends ModCommController
{
    protected function initializeCustomMethods(): void
    {
        // Конфигурация методов
        $this->addMethod('transactions', [
            'description' => 'Получить транзакции лицевого счета',
            'requires_auth' => true
        ]);

        // Правила авторизации
        $this->addAuthorizationRule('transactions', ['admin', 'manager', 'user']);
        $this->addAuthorizationRule('deposit', ['admin', 'manager']);
        $this->addAuthorizationRule('withdraw', ['admin', 'manager']);

        // Правила валидации
        $this->addValidationRule('transactions', [
            'clientId' => ['type' => 'integer', 'min' => 1],
            'typeDirection' => ['type' => 'string', 'in' => ['in', 'out'], 'optional' => true]
        ]);
    }
}
```

#### Доступные методы API

##### transactions - Получение транзакций

```php
// Запрос
$response = $service->sendRequest('clients', 'PrivateAccount/transactions', [
    'clientId' => 123,
    'typeDirection' => 'in'
]);

// Ответ
{
    "success": true,
    "data": {
        "transactions": [...],
        "clientId": 123,
        "typeDirection": "in"
    },
    "message": "Транзакции успешно получены"
}
```

##### deposit - Пополнение счета

```php
// Запрос
$response = $service->sendRequest('clients', 'PrivateAccount/deposit', [
    'clientId' => 123,
    'typeCode' => 'deposit',
    'amount' => 100.00,
    'relatedId' => 456,
    'commentArr' => ['source' => 'bank_transfer']
]);

// Ответ
{
    "success": true,
    "data": {
        "transaction_id": 789,
        "request": {...}
    },
    "message": "Счет успешно пополнен"
}
```

##### withdraw - Списание средств

```php
// Запрос
$response = $service->sendRequest('clients', 'PrivateAccount/withdraw', [
    'clientId' => 123,
    'typeCode' => 'payment',
    'amount' => 50.00,
    'relatedId' => 456
]);

// Ответ
{
    "success": true,
    "data": {
        "transaction_id": 790,
        "request": {...}
    },
    "message": "Средства успешно списаны"
}
```

##### listIn - Только пополнения

```php
// Запрос
$response = $service->sendRequest('clients', 'PrivateAccount/listIn', [
    'clientId' => 123
]);

// Ответ
{
    "success": true,
    "data": {
        "transactions": [...],
        "clientId": 123,
        "type": "in"
    },
    "message": "Пополнения успешно получены"
}
```

##### listOut - Только списания

```php
// Запрос
$response = $service->sendRequest('clients', 'PrivateAccount/listOut', [
    'clientId' => 123
]);

// Ответ
{
    "success": true,
    "data": {
        "transactions": [...],
        "clientId": 123,
        "type": "out"
    },
    "message": "Списания успешно получены"
}
```

##### changeStatus - Изменение статуса

```php
// Запрос
$response = $service->sendRequest('clients', 'PrivateAccount/changeStatus', [
    'transactionId' => 789,
    'status' => 'succeeded',
    'relatedId' => 456,
    'relatedData' => ['note' => 'Payment confirmed']
});

// Ответ
{
    "success": true,
    "data": [],
    "message": "Статус успешно изменен"
}
```

### Авторизация и валидация

#### Правила авторизации

```php
// Публичные методы (требуют только аутентификации)
'ping' => ['public']

// Методы для авторизованных пользователей
'transactions' => ['admin', 'manager', 'user']
'listIn' => ['admin', 'manager', 'user']
'listOut' => ['admin', 'manager', 'user']

// Методы только для администраторов и менеджеров
'deposit' => ['admin', 'manager']
'withdraw' => ['admin', 'manager']
'changeStatus' => ['admin', 'manager']
```

#### Правила валидации

```php
// Валидация для transactions
'transactions' => [
    'clientId' => [
        'type' => 'integer',
        'min' => 1
    ],
    'typeDirection' => [
        'type' => 'string',
        'in' => ['in', 'out'],
        'optional' => true
    ]
]

// Валидация для deposit/withdraw
'deposit' => [
    'clientId' => [
        'type' => 'integer',
        'min' => 1
    ],
    'typeCode' => [
        'type' => 'string',
        'min_length' => 1
    ],
    'amount' => [
        'type' => 'float',
        'min' => 0.01
    ],
    'relatedId' => [
        'type' => 'integer',
        'optional' => true
    ],
    'commentArr' => [
        'type' => 'array',
        'optional' => true
    ]
]
```

### Обработка ошибок

#### Стандартные HTTP статусы

- **200** - Успешное выполнение
- **400** - Ошибка валидации параметров
- **401** - Недостаточно прав доступа
- **404** - Клиент или транзакция не найдены
- **500** - Внутренняя ошибка сервера

#### Примеры ответов об ошибках

```php
// Ошибка валидации
{
    "success": false,
    "status_code": 400,
    "message": "Validation failed: clientId must be integer",
    "data": []
}

// Ошибка авторизации
{
    "success": false,
    "status_code": 401,
    "message": "Insufficient permissions for method 'deposit'",
    "data": []
}

// Клиент не найден
{
    "success": false,
    "status_code": 404,
    "message": "Client not found",
    "data": []
}
```

### Интеграция с другими модулями

#### Модуль бронирований

```php
// Списание средств за бронирование
$response = $service->sendRequest('clients', 'PrivateAccount/withdraw', [
    'clientId' => $clientId,
    'typeCode' => 'reservation_payment',
    'amount' => $reservationPrice,
    'relatedId' => $reservationId,
    'relatedData' => [
        'reservation_date' => $reservationDate,
        'court_name' => $courtName
    ]
]);
```

#### Модуль платежей

```php
// Пополнение счета через платежную систему
$response = $service->sendRequest('clients', 'PrivateAccount/deposit', [
    'clientId' => $clientId,
    'typeCode' => 'payment_system',
    'amount' => $paymentAmount,
    'relatedId' => $paymentId,
    'relatedData' => [
        'payment_method' => 'credit_card',
        'transaction_id' => $externalTransactionId
    ]
]);
```

### Логирование и мониторинг

```php
// Логирование API запросов
Service::logger('modcomm')->info('PrivateAccount API request', [
    'method' => 'transactions',
    'client_id' => $clientId,
    'user_role' => $userRole,
    'request_data' => $requestData
]);

// Мониторинг производительности
Service::logger('modcomm')->info('PrivateAccount API response time', [
    'method' => 'deposit',
    'processing_time' => $processingTime,
    'client_id' => $clientId
]);
```
