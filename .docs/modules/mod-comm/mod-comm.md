# ModComm - Система модульного общения (v1.3.0)

## Обзор

ModComm - это система для межмодульного общения через JSON запросы с поддержкой контроллеров для организации бизнес-логики. Система интегрирована с `Service::autoloader()` и автоматически ищет контроллеры в множественных путях согласно приоритетам архитектуры проекта.

### Ключевые возможности

- **Автоматическая загрузка контроллеров** - система автоматически сканирует директории и загружает нужные классы
- **Автоматическое формирование карты действий** - карта контроллеров и их методов формируется автоматически при запросе модуля
- **Приоритетный поиск** - `Service::autoloader()->getNamespace()` управляет порядком поиска классов
- **Гибкая архитектура** - поддержка различных типов контроллеров и модулей
- **Безопасность** - встроенная система авторизации и валидации
- **Производительность** - кэширование и оптимизация загрузки
- **Расширяемость** - простое добавление новых контроллеров и методов
- **Детальная информация о действиях** - получение полной информации о доступных действиях и их конфигурации

## Архитектура

### Основные компоненты

1. **ModCommController** - базовый контроллер с общими методами
2. **ModCommModule** - основной модуль, управляющий контроллерами
3. **ModCommRequest** - класс для представления запросов
4. **ModCommResponse** - класс для представления ответов
5. **ModCommService** - сервис для отправки запросов между модулями
6. **ModCommHelper** - хелпер для упрощения работы с системой
7. **Service::autoloader()** - система автозагрузки классов с приоритетами

### Автоматическая загрузка контроллеров

Система автоматически сканирует директории и загружает контроллеры в следующей структуре:

```
core/modules/
├── {имя_модуля}/
│   └── controllers/
│       └── modComm/
│           ├── UserController.php
│           ├── ProductController.php
│           └── OrderController.php
```

#### Система автозагрузки Service::autoloader()

Система использует `Service::autoloader()->getNamespace()` для управления путями автозагрузки:

- **Добавляет пути в порядке приоритета** - от высшего к низшему
- **Основной приоритет** - папка `site/` с пустым namespace
- **Автоматическое определение** - пространств имен на основе структуры директорий
- **Кэширование** - загруженных классов для повышения производительности

**Пример работы:**

```php
// Получение путей автозагрузки в порядке приоритета
$paths = Service::autoloader()->getNamespace();

// Результат включает пути типа:
// 'core\system' => 'C:\path\to\site\core\system\'
// 'app' => 'C:\path\to\site\app\'
// 'AC\core\system' => 'e:\work\web\base-ac\dev\core\system\'
```

#### Поддерживаемые пути для автоматической загрузки modComm контроллеров

Система modComm использует улучшенную логику поиска контроллеров с поддержкой приоритетов через `Service::autoloader()`. Контроллеры ищутся в следующем порядке приоритета:

**Приоритет 1: Модульные контроллеры (ядро системы)**
- **core/modules/{имя_модуля}/controllers/modComm/** - основные модульные контроллеры в ядре системы
- Namespace: `AC\core\modules\{имя_модуля}\controllers\modComm\`

**Приоритет 2: Системные контроллеры**
- **core/system/module/modComm/controllers/** - системные контроллеры modComm
- Namespace: `AC\core\system\module\modComm\controllers\`

**Общие пути автозагрузки в системе:**
- **Движки**: `{project_root}/core/engines/{EngineName}.php` 
- **Контроллеры приложения**: `{project_root}/app/controllers/{ControllerName}.php` 
- **Модели**: `{project_root}/app/models/{ModelName}.php` 
- **Классы**: `{project_root}/classes/{ClassName}.php` 
- **Сторонние библиотеки**: `{project_root}/core/system/thirdParty/{VendorName}/{ClassName}.php`

**Примеры путей из site/:**

- `site/core/system/` → `core\system`
- `site/app/` → `app`
- `site/core/` → `core`

#### Пространства имен

Система автоматически определяет пространства имен на основе структуры директорий:

**Основной приоритет (пустой namespace):**

- **site/** → пустой namespace (основной приоритет)

**Разработка (namespace AC\):**

- **Основное пространство**: `AC\` → `{project_root}/`
- **Приложение**: `AC\app\` → `{project_root}/app/`
- **Ядро системы**: `AC\core\` → `{project_root}/core/`
- **Модули**: `AC\core\modules\{имя_модуля}\` → `{project_root}/core/modules/{имя_модуля}/`
- **Системные компоненты**: `AC\core\system\` → `{project_root}/core/system/`
- **Модульные контроллеры**: `AC\core\modules\{имя_модуля}\controllers\modComm\{ControllerName}`
- **Системные контроллеры**: `AC\core\system\modules\modComm\controllers\{ControllerName}`
- **Движки**: `AC\core\engines\{EngineName}`
- **Контроллеры приложения**: `AC\app\controllers\{ControllerName}`
- **Модели**: `AC\app\models\{ModelName}`
- **Сторонние библиотеки**: `PHPMailer\`, `Psr\` и другие

#### Порядок поиска классов

1. **site/** (пустой namespace) - основной приоритет, публичная часть
2. **{project_root}/** (namespace AC\) - разработка
3. **Сторонние библиотеки** (PHPMailer, PSR и др.)

### Структура контроллеров

```
ModCommController (базовый)
├── getInfo() - информация о контроллере
├── getAvailableMethodsList() - список доступных методов
├── ping() - проверка доступности
└── [пользовательские методы в потомках]
```

## Использование

### 1. Создание контроллера-потомка

Создайте файл в папке `core/modules/{имя_модуля}/controllers/modComm/`:

```php
<?php

namespace AC\core\modules\{имя_модуля}\controllers\modComm;

use AC\core\system\modules\modComm\controllers\ModCommController;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;

class UserController extends ModCommController
{
    protected function initializeCustomMethods(): void
    {
        // Добавляем новые методы
        $this->addMethod('getUsers', [
            'description' => 'Получить список пользователей',
            'requires_auth' => true,
            'required_params' => [],
            'optional_params' => ['limit', 'offset']
        ]);

        // Добавляем правила авторизации
        $this->addAuthorizationRule('getUsers', ['admin', 'manager']);

        // Добавляем правила валидации
        $this->addValidationRule('getUsers', [
            'limit' => ['type' => 'integer', 'optional' => true],
            'offset' => ['type' => 'integer', 'optional' => true]
        ]);
    }

    public function getUsers(ModCommRequest $request): ModCommResponse
    {
        $limit = $request->getDataValue('limit', 10);
        $offset = $request->getDataValue('offset', 0);

        try {
            // Ваша бизнес-логика здесь
            $users = []; // Получение пользователей
            $total = 0;  // Общее количество

            return ModCommHelper::success([
                'users' => $users,
                'total' => $total
            ], 'Users retrieved successfully');
        } catch (Exception $e) {
            return ModCommHelper::error('Error retrieving users: ' . $e->getMessage(), 500);
        }
    }
}
```

### 2. Инициализация модуля

При создании экземпляра модуля необходимо передать имя модуля:

```php
// Создание модуля с именем 'clients'
$module = new ModCommModule('clients');

// Система автоматически:
// 1. Найдет контроллеры в папках:
//    - core/modules/clients/controllers/modComm/
//    - core/system/module/modComm/controllers/ (системные)
// 2. Загрузит все найденные контроллеры
// 3. Сформирует карту действий автоматически
// 4. Создаст маппинг "action" => "controller/method"
```

### 3. Автоматическое формирование карты действий

Система автоматически формирует карту действий при инициализации модуля:

```php
// Создание модуля автоматически формирует карту действий
$clientsModule = new ModCommModule('clients');

// Получение карты действий
$actionMap = $clientsModule->getActionControllerMap();

// Результат будет содержать маппинг типа:
// [
//   'ClientsPrivateAccount/transactions' => [
//     'controller' => 'ClientsPrivateAccount',
//     'controller_instance' => object,
//     'config' => [...],
//     'full_action' => 'ClientsPrivateAccount/transactions'
//   ],
//   'ClientsPrivateAccount/deposit' => [...],
//   'ping' => [...],
//   'getInfo' => [...]
// ]
```

#### Получение детальной информации о действиях

```php
// Получение полной информации о всех доступных действиях
$actionsInfo = $clientsModule->getAvailableActionsInfo();

// Получение информации о действиях конкретного контроллера
$controllerInfo = $clientsModule->getControllerActionsInfo('ClientsPrivateAccount');

// Проверка поддержки конкретного действия
$supported = $clientsModule->supportsAction('ClientsPrivateAccount/transactions');

// Получение правил для действия
$rules = $clientsModule->getSupportActionRule('ClientsPrivateAccount/transactions');
```

### 4. Отправка запросов

#### Прямой вызов метода контроллера

```php
use AC\core\system\modules\modComm\services\ModCommService;

$service = ModCommService::getInstance();

// Запрос к контроллеру в формате "controller/method"
$response = $service->sendRequest('ModCommModule', 'User/getUsers', [
    'limit' => 20,
    'offset' => 0
], [
    'user_role' => 'admin'
]);
```

#### Вызов базового метода

```php
// Получение информации о контроллере
$response = $service->sendRequest('ModCommModule', 'getInfo');

// Проверка доступности
$response = $service->sendRequest('ModCommModule', 'ping', ['message' => 'Hello']);

// Получение списка методов
$response = $service->sendRequest('ModCommModule', 'getAvailableMethodsList');
```

## Правила именования

### Названия контроллеров

- Контроллеры должны наследоваться от `ModCommController`
- Имя файла должно соответствовать имени класса
- Пример: `UserController.php` → класс `UserController`

### Названия методов

- Методы должны быть в camelCase
- Первая буква метода должна быть строчной
- Пример: `getUsers`, `createUser`, `updateUser`

### Формат действий

- Прямой вызов: `getUsers` (ищет в базовом контроллере)
- Вызов через контроллер: `User/getUsers` (ищет в UserController)
- Вызов через короткое имя: `personal_account/transactions` (ищет в PrivateAccountController)
- Вызов через полное имя: `ClientsPrivateAccount/transactions` (ищет в ClientsPrivateAccountController)

## Доступные методы базового контроллера

### getInfo()

Получает информацию о контроллере.

**Параметры:** нет

**Ответ:**

```json
{
  "success": true,
  "data": {
    "controller_name": "UserController",
    "controller_class": "AC\\core\\system\\module\\modComm\\controllers\\UserController",
    "available_methods": ["getInfo", "getAvailableMethodsList", "ping", "getUsers"],
    "methods_count": 4,
    "inheritance_chain": ["ModCommController", "UserController"],
    "created_at": "2025-07-23 13:25:00"
  },
  "message": "Controller information retrieved successfully"
}
```

### getAvailableMethodsList()

Получает список доступных методов с их конфигурацией.

**Параметры:** нет

**Ответ:**

```json
{
  "success": true,
  "data": {
    "getUsers": {
      "description": "Получить список пользователей",
      "requires_auth": true,
      "required_params": [],
      "optional_params": ["limit", "offset"]
    }
  },
  "message": "Available methods retrieved successfully"
}
```

### ping()

Проверяет доступность контроллера.

**Параметры:**

- `message` (опционально) - сообщение для ответа

**Ответ:**

```json
{
  "success": true,
  "data": {
    "message": "pong",
    "timestamp": 1648123456.789,
    "controller": "AC\\core\\system\\module\\modComm\\controllers\\UserController",
    "status": "available"
  },
  "message": "Controller is available"
}
```

## Правила валидации

### Поддерживаемые типы

- `string` - строка
- `integer` - целое число
- `float` - число с плавающей точкой
- `boolean` - логическое значение
- `array` - массив
- `object` - объект

### Примеры правил

```php
$this->addValidationRule('createUser', [
    'name' => [
        'type' => 'string',
        'min_length' => 2,
        'max_length' => 50
    ],
    'email' => [
        'type' => 'string',
        'max_length' => 255
    ],
    'age' => [
        'type' => 'integer',
        'optional' => true
    ]
]);
```

## Правила авторизации

```php
$this->addAuthorizationRule('deleteUser', ['admin']);
$this->addAuthorizationRule('updateUser', ['admin', 'manager']);
$this->addAuthorizationRule('getUsers', ['public']);
```

## Алгоритм поиска методов

1. **Парсинг действия**: Система разбирает действие по символу `/`
2. **Поиск контроллера**: Ищет контроллер в зарегистрированных или загружает из файловой системы
3. **Поиск метода**: Ищет метод в найденном контроллере
4. **Fallback**: Если контроллер не найден, ищет метод в базовом контроллере
5. **Ошибка**: Если метод не найден нигде, возвращает ошибку 400

### Примеры поиска

```php
// 1. User/getUsers -> ищет UserController::getUsers()
$response = $service->sendRequest('ModCommModule', 'User/getUsers');

// 2. getUsers -> ищет ModCommController::getUsers()
$response = $service->sendRequest('ModCommModule', 'getUsers');

// 3. User/nonExistent -> ошибка 400
$response = $service->sendRequest('ModCommModule', 'User/nonExistent');
```

## Обработка ошибок

Система автоматически обрабатывает следующие типы ошибок:

- **404** - метод не найден
- **400** - ошибка валидации параметров
- **401** - недостаточно прав доступа
- **500** - внутренняя ошибка сервера

## Примеры использования

### Получение транзакций через контроллер

```php
// Использование короткого имени контроллера (существующий формат)
$response = $service->sendRequest('clients', 'private_account/transactions', [
    'clientId' => 123,
    'typeDirection' => 'in'
], [
    'user_role' => 'admin'
]);
```

### Создание пользователя

```php
$response = $service->sendRequest('ModCommModule', 'User/createUser', [
    'username' => 'newuser',
    'email' => 'newuser@example.com',
    'password' => 'password123',
    'first_name' => 'John',
    'last_name' => 'Doe'
], [
    'user_role' => 'admin'
]);
```

### Получение данных с фильтрацией (legacy)

```php
$response = $service->sendRequest('ModCommModule', 'getData', [
    'filter' => 'test',
    'limit' => 10,
    'offset' => 0
]);
```

### Создание новых данных (legacy)

```php
$response = $service->sendRequest('ModCommModule', 'createData', [
    'name' => 'New Item',
    'value' => 'Item Value',
    'description' => 'Item description'
], [
    'user_role' => 'admin'
]);
```

### Получение статистики (legacy)

```php
$response = $service->sendRequest('ModCommModule', 'getStatistics', [
    'period' => 'month',
    'group_by' => 'category'
]);
```

## Расширение функциональности

### Добавление новых контроллеров

1. Создайте файл в папке `core/modules/{имя_модуля}/controllers/modComm/`
2. Наследуйтесь от `ModCommController`
3. Реализуйте метод `initializeCustomMethods()`
4. Добавьте ваши методы с правилами валидации и авторизации

### Кастомная авторизация

Переопределите метод `isUserAuthorized()` в вашем контроллере:

```php
protected function isUserAuthorized(ModCommRequest $request): bool
{
    $token = $request->getOptionValue('auth_token');
    $userRole = $request->getOptionValue('user_role');

    // Ваша логика проверки авторизации
    return $this->validateToken($token) && $this->hasRole($userRole);
}
```

## Лучшие практики

1. **Именование методов** - используйте camelCase для названий методов
2. **Валидация** - всегда определяйте правила валидации для параметров
3. **Авторизация** - явно указывайте правила доступа для каждого метода
4. **Документация** - добавляйте описания для всех методов
5. **Обработка ошибок** - используйте `ModCommHelper` для создания стандартных ответов
6. **Тестирование** - создавайте тесты для всех методов контроллеров
7. **Структура файлов** - следуйте соглашениям по именованию файлов и папок

## Отладка

### Получение информации о модуле

```php
$response = $service->sendRequest('ModCommModule', 'getModuleInfo');
$info = $response->getData();
echo "Текущий модуль: " . $info['current_module'] . "\n";
echo "Путь к контроллерам: " . $info['controllers_path'] . "\n";
echo "Загруженные контроллеры: " . implode(', ', array_keys($info['controllers'])) . "\n";
```

### Получение карты действий

```php
$module = new ModCommModule('myModule');
$actionMap = $module->getActionControllerMap();
foreach ($actionMap as $action => $info) {
    echo "Действие '{$action}' -> Контроллер '{$info['controller']}'\n";
}
```

## Реализованные возможности

### ✅ Улучшенная автоматическая загрузка контроллеров

Система автоматически сканирует множественные пути поиска контроллеров с поддержкой приоритетов:

- **Множественные пути поиска** - поддержка core/modules и системных контроллеров
- **Приоритетная загрузка** - интеграция с `Service::autoloader()` для соблюдения приоритетов
- **Предотвращение дублирования** - загружается только первый найденный контроллер по приоритету
- **Автоматическое определение namespace** - правильное построение namespace для каждого пути
- **Валидация наследования** - проверка, что контроллеры наследуются от `ModCommController`
- **Детальное логирование** - логирование ошибок загрузки с указанием пути

#### Производительность и кэширование

- **Приоритетная загрузка** - контроллеры загружаются согласно приоритетам автозагрузчика
- **Предотвращение дублирования** - система предотвращает загрузку одного контроллера из разных путей
- **Интеграция с Service::autoloader()** - использует существующую систему автозагрузки для оптимальной производительности
- **Ленивая загрузка** - контроллеры загружаются только при необходимости
- **Оптимизированное сканирование** - система сканирует только существующие директории
- **Метрики производительности** - отслеживание времени загрузки и выполнения запросов
- **Логирование ошибок** - детальное логирование проблем загрузки контроллеров

### ✅ Поддержка формата действий "controller/method"

- `User/getUsers` - вызов метода `getUsers` в контроллере `UserController`
- `ProductController/getProducts` - вызов метода `getProducts` в контроллере `ProductController`
- `ping` - прямой вызов базового метода (fallback)

### ✅ Fallback к базовому контроллеру

Если действие не содержит `/`, система ищет метод в базовом контроллере:

- `ping` → `ModCommController::ping()`
- `getInfo` → `ModCommController::getInfo()`
- `getAvailableMethodsList` → `ModCommController::getAvailableMethodsList()`

### ✅ Улучшенная обработка ошибок

- **404** - метод не найден в контроллере
- **400** - ошибка валидации параметров
- **401** - недостаточно прав доступа
- **500** - внутренняя ошибка сервера

### ✅ Метаданные в ответах

Система автоматически добавляет метаданные к ответам:

- Время выполнения запроса
- Информация о контроллере
- Статус выполнения

## Тестирование

Для тестирования функциональности используйте существующие методы API:

```php
// Создание модуля и проверка функциональности
$module = new ModCommModule('clients');

// Получение информации о модуле
$moduleInfo = $module->getModuleInfo();

// Получение карты действий
$actionMap = $module->getActionControllerMap();

// Проверка поддержки действий
$supported = $module->supportsAction('ClientsPrivateAccount/transactions');
```

> **Примечание**: Согласно правилам проекта, тестовые файлы не создаются. Функциональность тестируется через API методы модуля.

## Структура проекта

```
{project_root}/
├── core/system/modules/modComm/
│   ├── ModCommModule.php (обновлен с поддержкой автозагрузки)
│   ├── ModCommController.php (базовый контроллер)
│   ├── ModCommService.php (сервис для запросов)
│   ├── ModCommHelper.php (хелпер для упрощения работы)
│   ├── ModCommModuleInterface.php (интерфейс модуля)
│   ├── http/
│   │   ├── ModCommRequest.php (класс запроса)
│   │   └── ModCommResponse.php (класс ответа)
│   ├── exceptions/
│   │   └── ModCommException.php (исключения)
│   └── lang/ (переводы)
├── core/modules/{имя_модуля}/controllers/modComm/
│   ├── ProductController.php (пример контроллера)
│   └── OrderController.php (пример контроллера)
├── classes/ (сторонние классы)
├── core/system/thirdParty/ (сторонние библиотеки)
└── .docs/modules/mod-comm.md (эта документация)
```

### Доступные модули в системе

Система поддерживает следующие модули (на основе реальной структуры автозагрузки):

- **accounts** - управление аккаунтами
- **areas** - управление зонами
- **blocks** - блоки системы
- **clients** - управление клиентами
- **config** - конфигурация
- **coupons** - купоны и скидки
- **discounts** - система скидок
- **fitness** - фитнес-функции
- **mailing** - рассылки
- **membershipFees** - членские взносы
- **news** - новости
- **payment** - платежи
- **reports** - отчеты
- **reservations** - бронирования
- **specPrices** - специальные цены
- **stocks** - складские остатки
- **text** - текстовый контент
- **tickets** - тикеты
- **users** - пользователи
- **webIo** - веб-ввод/вывод

## Улучшения в текущей версии (v1.3.0)

### ✅ Автоматическое формирование карты действий
- **Автоматическое обнаружение контроллеров** - система автоматически находит и загружает контроллеры из модулей
- **Автоматическое формирование карты** - карта действий создается автоматически при инициализации модуля
- **Детальная информация о действиях** - получение полной информации о доступных действиях и их конфигурации
- **Логирование процесса** - детальное логирование процесса формирования карты действий

### ✅ Новые методы для работы с действиями
- **getAvailableActionsInfo()** - получение детальной информации о всех доступных действиях
- **getControllerActionsInfo()** - получение информации о действиях конкретного контроллера
- **getAuthorizationRules()** - получение правил авторизации для методов
- **getValidationRules()** - получение правил валидации для методов

### ✅ Улучшенная интеграция с контроллерами модулей
- **Поддержка контроллеров из core/modules/** - автоматическое обнаружение контроллеров в новой структуре
- **Правильная обработка namespace** - корректное построение namespace для контроллеров модулей
- **Валидация наследования** - проверка, что контроллеры наследуются от ModCommController

### ✅ Обратная совместимость
- **Legacy методы помечены как deprecated** - старые методы сохранены для обратной совместимости
- **Поддержка старой структуры** - система продолжает поддерживать modules/ путь для legacy проектов
- **Плавный переход** - возможность постепенного перехода на новую архитектуру

## Улучшения в предыдущих версиях

### v1.2.0 - Интеграция с системой автозагрузки
- **Service::autoloader() интеграция** - полная интеграция с системой приоритетной автозагрузки
- **Множественные пути поиска** - поддержка core/modules/ и системных контроллеров
- **Приоритетная загрузка** - соблюдение порядка приоритетов согласно архитектуре системы
- **Удаление зависимостей от примеров** - убраны ссылки на несуществующие примеры контроллеров
- **Реальная реализация методов** - базовые методы получили полноценную реализацию
- **Улучшенная архитектура** - правильное построение namespace и предотвращение дублирования

### v1.1.0 - Базовая функциональность
- **Базовый контроллер** - создание ModCommController с основными методами
- **Система валидации** - правила валидации параметров
- **Система авторизации** - правила доступа к методам
- **Обработка ошибок** - стандартизированные ответы об ошибках
- **Логирование** - детальное логирование операций
