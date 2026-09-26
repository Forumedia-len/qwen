# Модули системы

## Обзор

Модули системы (`core/modules/`) представляют собой независимые компоненты функциональности, каждый из которых отвечает за определенную область бизнес-логики. Модули следуют принципам MVC и обеспечивают высокую степень переиспользования кода.

## Структура модуля

### Стандартная структура

```
module_name/
├── ModuleNameModule.php    # Главный класс модуля
├── controllers/            # Контроллеры модуля
│   ├── modComm/           # Общие контроллеры
│   └── device/            # Контроллеры для устройств
├── models/                 # Модели данных
├── views/                  # Представления
│   ├── admin/             # Админ-интерфейс
│   ├── site/              # Сайт
│   ├── touch/             # Сенсорные экраны
│   └── display/           # Дисплеи
├── tables/                 # Структуры таблиц БД
├── actions/                # Действия для устройств
├── config/                 # Конфигурация модуля
└── structure/              # Структурные файлы
```

### Базовый класс модуля

```php
abstract class BaseModule
{
    protected $moduleName;
    protected $controller;
    protected $action;
    protected $params;

    public function exec($action = null, $key = null, $controllerName = null)
    {
        // Логика выполнения модуля
    }

    protected function getController($controllerName)
    {
        // Получение контроллера
    }

    protected function getModel($modelName)
    {
        // Получение модели
    }
}
```

## Документация модулей

### Структура документации

Каждый модуль имеет отдельную папку в `.docs/modules/{имя_модуля}/` со следующей структурой:

```
modules/{module_name}/
├── README.md              # Обзор модуля
├── {module_name}.md       # Подробная документация
├── controllers/           # Документация контроллеров
├── models/               # Документация моделей
├── views/                # Документация представлений
└── config/               # Документация конфигурации
```

### Доступные модули

#### mod-comm - Система межмодульного общения

**Расположение**: `core/system/modules/modComm/`

**Документация**: [modules/mod-comm/](mod-comm/)

**Назначение**: Предоставляет систему межмодульного общения через JSON запросы с поддержкой контроллеров для организации бизнес-логики.

**Основные функции**:

- **Межмодульное общение** - система для обмена данными между модулями
- **Контроллеры modComm** - организация бизнес-логики в контроллерах
- **Автоматическая загрузка** - автоматическое обнаружение и загрузка контроллеров
- **Валидация и авторизация** - встроенные системы безопасности
- **Обработка ошибок** - стандартизированные ответы об ошибках
- **Логирование** - детальное логирование всех операций

**Архитектура**:

- **ModCommController** - базовый контроллер с общими методами
- **ModCommModule** - основной модуль, управляющий контроллерами
- **ModCommRequest/Response** - классы для запросов и ответов
- **ModCommService** - сервис для отправки запросов между модулями
- **ModCommHelper** - хелпер для упрощения работы с системой

**Пути загрузки контроллеров**:

- `core/modules/{имя_модуля}/controllers/modComm/` - модульные контроллеры
- `core/system/modules/modComm/controllers/` - системные контроллеры

#### reservations - Управление бронированиями

**Расположение**: `core/modules/reservations/`

**Документация**: [modules/reservations/](reservations/)

**Назначение**: Управление бронированиями спортивных кортов.

**Основные функции**:

- Создание и редактирование бронирований
- Управление расписанием кортов
- Проверка доступности кортов
- Обработка платежей за бронирования
- Уведомления о бронированиях
- Статистика и отчеты

#### clients - Управление клиентами

**Расположение**: `core/modules/clients/`

**Документация**: [modules/clients/](clients/)

**Назначение**: Управление клиентской базой, профилями, аутентификацией и личными счетами клиентов.

**Основные функции**:

- **Управление профилями** - регистрация, редактирование, загрузка аватаров
- **Авторизация и безопасность** - вход/выход, восстановление паролей, двухфакторная аутентификация
- **История и аналитика** - история бронирований, статистика посещений, предпочтения
- **Финансовые операции** - история платежей, баланс, кредиты, скидки
- **API и межмодульное общение** - modComm контроллеры для интеграции

**Архитектура**:

- **ClientsModule** - основной класс модуля
- **ClientsModel** - модель для работы с данными клиентов
- **PrivateAccountTransactionEngine** - движок для управления транзакциями личных счетов
- **PrivateAccountController** - modComm контроллер для API взаимодействия
- **PrivateAccountTransactionDto** - объект передачи данных о транзакциях

**modComm контроллеры**:

- **PrivateAccount** - управление личными счетами через API
- **Методы**: transactions, deposit, withdraw, listIn, listOut, changeStatus
- **Авторизация**: различные уровни доступа для разных ролей
- **Валидация**: строгие правила проверки входных данных

**Интеграция**:

- Модуль accounts (аккаунты и платежи)
- Модуль reservations (бронирования)
- Система аутентификации
- Engines системы
- Система modComm для межмодульного общения

**Пути загрузки**:

- Основной модуль: `core/modules/clients/`
- modComm контроллеры: `core/modules/clients/controllers/modComm/`
- Движки: `core/modules/clients/engines/`
- Модели: `core/modules/clients/models/`
- Представления: `core/modules/clients/views/`

#### text - Управление статическими текстами

**Расположение**: `core/modules/text/`

**Документация**: [modules/text/](text/)

**Назначение**: редактирование контентных блоков (контакты, прайс, политика конфиденциальности и т.д.), которые переиспользуются на сайте и в письмах.

**Основные функции**:

- Хранение HTML-контента в таблице `config_text`
- Административная форма с CKEditor по адресу `at/text/alias/{alias}`
- Переключение между alias через выпадающий список
- Вкладки по активным языкам, автоматическое сохранение строк с кодом языка и fallback на язык по умолчанию
- Переиспользование контента на сайте, в touch- и display-интерфейсах

**Структура**:

- Модуль: `core/modules/text/TextModule.php`
- Контроллеры: `core/modules/text/controllers/` (включая `admin/TextController.php`)
- Модели: `core/modules/text/models/TextModel.php`
- DTO: `core/modules/text/dto/TextDto.php`
- Представления: `core/modules/text/views/` (`site` и `admin`)

## Основные модули

### accounts - Управление аккаунтами

**Расположение**: `core/modules/accounts/`

**Назначение**: Управление финансовыми операциями, счетами и платежами клиентов.

#### Основные функции

- Создание и управление счетами
- Обработка платежей
- Управление балансом клиентов
- Финансовая отчетность
- Интеграция с платежными системами

#### Контроллеры

```php
// AccountsController.php - Основной контроллер
class AccountsController extends BaseController
{
    public function index()     // Список аккаунтов
    public function show($id)   // Просмотр аккаунта
    public function create()    // Создание аккаунта
    public function update($id) // Обновление аккаунта
    public function delete($id) // Удаление аккаунта
}

// PaymentController.php - Управление платежами
class PaymentController extends BaseController
{
    public function process()   // Обработка платежа
    public function callback()  // Callback от платежной системы
    public function refund()    // Возврат средств
}
```

#### Модели

```php
// AccountModel.php
class AccountModel extends BaseModel
{
    public function getClientAccounts($client_id)
    public function createAccount($data)
    public function updateBalance($account_id, $amount)
    public function getTransactionHistory($account_id)
}
```

### areas - Управление зонами и кортами

**Расположение**: `core/modules/areas/`

**Назначение**: Управление спортивными кортами, зонами и их типами.

#### Основные функции

- Создание и управление кортами
- Настройка типов кортов
- Управление расписанием
- Интеграция с системой бронирований

#### Контроллеры

```php
// AreasController.php
class AreasController extends BaseController
{
    public function index()     // Список кортов
    public function show($id)   // Просмотр корта
    public function create()    // Создание корта
    public function update($id) // Обновление корта
    public function delete($id) // Удаление корта
}

// TypesController.php - Типы кортов
class TypesController extends BaseController
{
    public function index()     // Список типов
    public function create()    // Создание типа
    public function update($id) // Обновление типа
}
```

### blocks - Управление блоками

**Расположение**: `core/modules/blocks/`

**Назначение**: Управление блоками времени и их резервированием.

#### Основные функции

- Создание временных блоков
- Резервирование блоков
- Управление доступностью
- Интеграция с системой бронирований

## Интеграция модулей

### Service Locator

Модули интегрируются через Service Locator:

```php
// Доступ к движкам
$engines = Service::engines();

// Работа с клиентами
$clients = $engines->clients;

// Работа с бронированиями
$reservations = $engines->reservations;

// Работа с аккаунтами
$accounts = $engines->accounts;
```

### Автоматическая загрузка

Модули автоматически загружаются системой:

```php
// Путь к контроллеру модуля
core/modules/{module_name}/controllers/modComm/{ControllerName}.php

// Путь к модели модуля
core/modules/{module_name}/models/{ModelName}.php

// Путь к представлению модуля
core/modules/{module_name}/views/{device_type}/{view_name}.php
```

### Маршрутизация

Модули поддерживают собственную маршрутизацию:

```php
// Маршрут к модулю
/{module_name}/{action}/{key}

// Примеры
/reservations/create
/clients/profile/123
/accounts/payment/456
```

## Разработка новых модулей

### Создание модуля

1. **Создать структуру папок**:

   ```
   core/modules/{module_name}/
   ├── {ModuleName}Module.php
   ├── controllers/
   ├── models/
   ├── views/
   └── config/
   ```

2. **Создать главный класс модуля**:

   ```php
   class {ModuleName}Module extends BaseModule
   {
       public function __construct()
       {
           $this->moduleName = '{module_name}';
       }
   }
   ```

3. **Создать контроллеры**:

   ```php
   class {ModuleName}Controller extends BaseController
   {
       // Методы контроллера
   }
   ```

4. **Создать модели**:
   ```php
   class {ModuleName}Model extends BaseModel
   {
       // Методы модели
   }
   ```

### Документирование модуля

1. **Создать папку документации**:

   ```
   .docs/modules/{module_name}/
   ├── README.md
   ├── {module_name}.md
   └── ...
   ```

2. **Обновить основной README.md**:

   - Добавить ссылку на новый модуль
   - Обновить оглавление

3. **Создать подробную документацию**:
   - Описание функциональности
   - API и методы
   - Примеры использования
   - Конфигурация

## Принципы разработки модулей

### Единственная ответственность

Каждый модуль отвечает за одну область функциональности:

- **reservations** - только бронирования
- **clients** - только клиенты
- **accounts** - только аккаунты

### Слабая связанность

Модули взаимодействуют через интерфейсы:

```php
// Хорошо - через интерфейс
interface ClientInterface
{
    public function getClient($id);
}

// Плохо - прямая зависимость
class ReservationModule
{
    private $clientModule; // Прямая зависимость
}
```

### Высокая когезия

Связанные функции группируются в одном модуле:

```php
// Хорошо - все функции клиента в одном модуле
class ClientsModule
{
    public function register() { }
    public function authenticate() { }
    public function updateProfile() { }
    public function getHistory() { }
}
```

### Переиспользование

Модули должны быть переиспользуемыми:

```php
// Хорошо - модуль можно использовать в разных контекстах
class PaymentModule
{
    public function processPayment($amount, $method) { }
    public function refundPayment($payment_id) { }
}
```

## Тестирование модулей

### Unit тесты

```php
class ReservationModuleTest extends TestCase
{
    public function testCreateReservation()
    {
        // Тест создания бронирования
    }

    public function testCancelReservation()
    {
        // Тест отмены бронирования
    }
}
```

### Integration тесты

```php
class ModuleIntegrationTest extends TestCase
{
    public function testReservationWithClient()
    {
        // Тест интеграции модулей
    }
}
```

## Производительность

### Кэширование

```php
// Кэширование результатов модуля
class CachedModule extends BaseModule
{
    public function getData($key)
    {
        $cache = Service::cache();
        return $cache->remember("module_{$key}", function() {
            return $this->loadData($key);
        });
    }
}
```

### Ленивая загрузка

```php
// Загрузка компонентов по требованию
class LazyModule extends BaseModule
{
    private $components = [];

    public function getComponent($name)
    {
        if (!isset($this->components[$name])) {
            $this->components[$name] = $this->loadComponent($name);
        }
        return $this->components[$name];
    }
}
```

## Безопасность

### Валидация входных данных

```php
class SecureModule extends BaseModule
{
    public function processData($data)
    {
        // Валидация входных данных
        $validator = Service::validator();
        $rules = $this->getValidationRules();

        if (!$validator->validate($data, $rules)) {
            throw new ValidationException($validator->errors());
        }

        return $this->processValidData($data);
    }
}
```

### Авторизация

```php
class AuthorizedModule extends BaseModule
{
    public function executeAction($action, $params)
    {
        // Проверка авторизации
        $auth = Service::auth();

        if (!$auth->can($action, $this->moduleName)) {
            throw new UnauthorizedException();
        }

        return $this->performAction($action, $params);
    }
}
```

## Мониторинг и логирование

### Логирование операций

```php
class LoggedModule extends BaseModule
{
    public function execute($action, $params)
    {
        $logger = Service::logger();

        $logger->info("Module {$this->moduleName} executing action: {$action}", [
            'params' => $params,
            'user_id' => Service::auth()->getUserId()
        ]);

        try {
            $result = $this->performAction($action, $params);

            $logger->info("Module {$this->moduleName} action completed successfully", [
                'action' => $action,
                'result' => $result
            ]);

            return $result;
        } catch (Exception $e) {
            // Логирование ошибок (ОБЯЗАТЕЛЬНО)
            $logger->logException($e, [
                'module' => $this->moduleName,
                'action' => $action
            ]);
            throw $e;
        }
    }
}
```

### Метрики производительности

```php
class MonitoredModule extends BaseModule
{
    public function execute($action, $params)
    {
        $startTime = microtime(true);

        try {
            $result = $this->performAction($action, $params);

            $executionTime = microtime(true) - $startTime;

            // Отправка метрик
            Service::metrics()->record("module_execution_time", [
                'module' => $this->moduleName,
                'action' => $action,
                'time' => $executionTime
            ]);

            return $result;
        } catch (Exception $e) {
            // Запись ошибки
            Service::metrics()->increment("module_errors", [
                'module' => $this->moduleName,
                'action' => $action
            ]);
            throw $e;
        }
    }
}
```
