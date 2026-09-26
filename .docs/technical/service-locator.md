# Service Locator - Система сервисов

## Обзор

Service Locator - это центральный компонент системы Active Court, который предоставляет единую точку доступа ко всем сервисам приложения. Этот паттерн упрощает управление зависимостями и обеспечивает централизованный контроль над компонентами системы.

## Основные принципы

### 1. Единая точка доступа

Все сервисы доступны через статический класс `Service`:

```php
// Доступ к основным сервисам
Service::app()           // Главное приложение
Service::engines()       // Движки данных
Service::session()       // Управление сессиями
Service::request()       // Обработка запросов
Service::response()      // Формирование ответов
```

### 2. Shared Instances

Сервисы могут быть shared (общими) или новыми экземплярами:

```php
// Shared instance (по умолчанию)
$app = Service::app();

// Новый экземпляр
$app = Service::app(false);
```

### 3. Автоматическая инициализация

Сервисы инициализируются автоматически при первом обращении.

## Структура классов

### BaseService

Базовый класс для всех сервисов:

```php
class BaseService extends Locator
{
    protected static $instances = [];

    protected static function getSharedInstance($key, ...$params)
    {
        $key = strtolower($key);

        if (!isset(static::$instances[$key])) {
            $params[] = false; // $getShared = false
            static::$instances[$key] = Services::$key(...$params);
        }

        return static::$instances[$key];
    }
}
```

### Services

Основной класс с реализацией всех сервисов:

```php
class Services extends BaseService
{
    public static function app($getShared = true): App
    {
        if ($getShared) {
            return static::getSharedInstance('app');
        }
        return useClass(paths()->systemDir . 'App', true);
    }

    public static function engines($options = [], $getShared = true): Engines
    {
        if ($getShared) {
            return static::getSharedInstance('engines', $options);
        }
        return useClass(paths()->enginesDir . 'Engines', true, $options);
    }

    // ... другие сервисы
}
```

### Service (Локатор)

Фасад для доступа к сервисам:

```php
class Service extends Services
{
    // Наследует все методы от Services
}
```

## Доступные сервисы

### 🚀 Основные сервисы

#### App

Главное приложение системы:

```php
$app = Service::app();
$app->run();
```

#### Engines

Движки для работы с данными:

```php
$engines = Service::engines();
$clients = $engines->clients;
$reservations = $engines->reservations;
```

### 🌐 HTTP сервисы

#### Request

Обработка HTTP запросов:

```php
$request = Service::request();
$method = $request->getMethod();
$url = $request->getUrl();
$data = $request->getPost();
```

#### Response

Формирование HTTP ответов:

```php
$response = Service::response();
$response->setContent($content);
$response->setHeader('Content-Type', 'application/json');
$response->send();
```

#### Format

Выбор formatter-а HTTP-ответа по MIME-типу. Сервис используется внутри `Response::setJSON()`, `Response::setXML()` и API ResponseTrait:

```php
$formatter = Service::format()->getFormatter('application/json');
$json = $formatter->format(['success' => true]);
```

`format()` и собственный `formatData()` решают разные задачи: первый обслуживает HTTP JSON/XML, второй преобразует данные entity-объектов.

#### Negotiator

Согласование формата, языка, кодировки и charset с HTTP-заголовками запроса:

```php
$mime = Service::request()->negotiate(
    'media',
    config('Format')->supportedResponseFormats
);
```

#### Проверенное чтение параметров Request

```php
$date = Service::request()->validated('date', 'get', [
  ['date', ['allowDottedFormat' => true, 'normalize' => true]],
], date('Y-m-d'));
$areaId = Service::request()->validated('area_id', 'post', [
  'required', ['integer', ['min' => 1]],
]);
$memo = Service::request()->validated('memo', 'request', 'string');
```

Сигнатура: `validated(string $alias, string $source, string|array $rules, mixed $default = null): mixed`.
Источники: `get`, `post`, `request` (регистр неважен). `request` сохраняет поведение `_()`:
GET и параметры маршрута имеют приоритет над POST; cookies не читаются. Поддерживаются существующие
camelCase/snake_case алиасы. `_()`, `_get()`, `_post()` и сам `Service::request()` не меняют поведение.

Правила передаются в формате одного поля `Validation::setRule()`. Каждый вызов использует отдельный
`Service::validation(false)`, не меняет исходный запрос и состояние общего сервиса валидации.
Значение по умолчанию применяется только к отсутствующему необязательному параметру и тоже проверяется.
Для обязательного поля используйте `required`. Неверный ввод не заменяется значением по умолчанию.

Новый метод вызывает `Validation::run($data, true)`. В строгом режиме массивы, объекты и ресурсы
не проходят скалярные правила; после ошибки поля дальнейшие правила не преобразуют его значение.
`integer` проверяет переполнение, `float` проверяет число до приведения, `bool` принимает только
`0`, `1`, `"0"`, `"1"`, `false`, `true`. Старые вызовы `Validation::run($data)` и модельных валидаторов
сохраняют прежний режим.

`date` проверяет реальность календарной даты. `allowDottedFormat` дополнительно разрешает `d.m.Y`,
`normalize` возвращает `Y-m-d`; обе опции по умолчанию выключены. Строгий режим требует полного совпадения формата.
`time` принимает `H:i`/`HH:mm` и вариант с нулевыми секундами, возвращает `HH:mm`. Опция `allowArray`
разрешает карту `time[HH:mm]=0|1` с проверкой каждого ключа и флага; `arrayOnly` запрещает скалярное время.
Ключи карты однократно декодируются для совместимости с существующими формами.

При ошибке выбрасывается `RequestValidationException` с кодом 400. Модуль может перехватить его
и показать ошибку в своём интерфейсе: так работает [контроллер бронирований](../modules/reservations/reservations.md).
Для необработанного модулем исключения при обычном открытии страницы
(`Accept: text/html`, без `X-Requested-With: XMLHttpRequest`) `App::run()` возвращает HTTP 404 и штатный
шаблон `app/tpl/errors/html/error_404.php`. Шаблон получает только локализованное сообщение;
исходное значение, URL, трассировка и внутренние ошибки в него не передаются.
Публичные шаблоны `error_404.php` и `production.php` используют `HttpErrorPage`:
ошибка отображается внутри основного шаблона текущего устройства. Порядок выбора и резервный
вывод описаны в [документации устройств](../devices/device-actions.md#страницы-http-ошибок).
AJAX и запросы без `Accept: text/html` сохраняют HTTP 400, `text/plain; charset=UTF-8` и локализованное сообщение.
Код исключения и контракт `validated()` не изменены.
Обработка вынесена в `App::handleRequestValidationException()`. Перед отправкой ответа
`Service::logger('app')` записывает предупреждение `Request validation failed.`: имя параметра,
источник, ошибки валидаторов, HTTP-метод, путь без query string, файл и строку исключения,
стек вызовов без аргументов и объектов. Значения запроса, сессия и заголовки в контекст лога не передаются.
Неизвестный источник/валидатор — ошибка программирования `InvalidArgumentException`.

Метод проверяет ввод, но не делает строку безопасной для любого контекста вывода.
HTML-текст и атрибуты кодируются непосредственно в шаблоне через `htmlspecialchars()` с `ENT_QUOTES | ENT_SUBSTITUTE`;
данные JavaScript передаются через корректное JSON-кодирование или безопасные HTML-атрибуты,
текстовые ответы AJAX вставляются через `textContent`/jQuery `.text()`.

Для имён из старых сессий и записей используется `StringHelper::shield($value, doubleEncode: false)`:
helper поддерживает HTML5-сущности и не добавляет повторное кодирование при выводе.
Настройки старых вызовов `shield()` сохранены; подробности — в
[документации клиентов](../modules/clients/clients.md#html-представление-имён).

#### Validation

Проверка произвольных массивов выполняется теми же валидаторами, которые используются в `BaseModel`:

```php
$validation = Service::validation();

$isValid = $validation
  ->setRules([
    'cell_id' => [
      'label' => 'Cell ID',
      'rules' => ['required', ['integer', ['min' => 1]]],
    ],
  ])
  ->run(Service::request()->getGet());

if (!$isValid) {
  $errors = $validation->getErrors();
}

$data = $validation->getValidated();
```

Также поддерживается формат правил моделей:

```php
$validation->setRules([
  [['cell_id'], 'required'],
  [['cell_id'], 'integer', ['min' => 1]],
]);
```

Адаптер получает конкретные классы (`RequiredValidator`, `IntegerValidator` и другие) через `ValidatorHelper`, поэтому правила моделей и HTTP-входа не дублируются. `getErrors()` не очищает ошибки; для полного сброса shared-сервиса используется `reset()`.

#### URL

Работа с URL:

```php
$url = Service::url();
$segments = $url->getSegments();
$baseUrl = $url->getBaseURL();
```

#### URI

Работа с URI:

```php
$uri = Service::uri();
$path = $uri->getPath();
$query = $uri->getQuery();
```

### 💾 Данные и сессии

#### Session

Управление сессиями:

```php
$session = Service::session();
$session->start('session_name');
$session->set('key', 'value');
$value = $session->get('key');
```

#### Query

Работа с базой данных:

```php
$query = Service::query();
$result = $query->table('users')->where('id', 1)->get();
```

### 🎨 Представления

#### View

Система представлений:

```php
$view = Service::view();
$view->setData('title', 'Заголовок');
$view->render('template');
```

#### TemplateResolver

`Service::templates()` предоставляет общий сервис иерархии интерфейсов и поиска шаблонов;
`Service::templates(false)` создаёт отдельный экземпляр. Сервис не запоминает устройство запроса:
его можно указать явно или получить из текущего `Service::url()`.
Порядок поиска файлов и порядок объединения слоёв разделены.
Контракты методов описаны в [системе автозагрузки](../core/auto-loader.md#templateresolverphp--иерархия-шаблонов).

#### Structure

Структура данных:

```php
$structure = Service::structure();
$pageData = $structure->getPageDataByKey('home');
```

### 🌍 Локализация

#### Lang

Языковая система:

```php
$lang = Service::lang();
$message = $lang->get('welcome_message');
```

### 🔐 Безопасность

#### Auth

Система авторизации:

```php
$auth = Service::auth();
$isLoggedIn = $auth->check();
$user = $auth->user();
```

### 🔄 Маршрутизация

#### Routes

Управление маршрутами:

```php
$routes = Service::routes();
$routes->add('GET', '/users', 'UsersController::index');
```

#### Redirect

Перенаправления:

```php
$redirect = Service::redirect();
$redirect->to('/dashboard')->send();
```

### 📊 Дополнительные сервисы

#### CURL

HTTP клиент:

```php
$curl = Service::curl();
$response = $curl->get('https://api.example.com/data');
```

#### Exceptions

Обработка исключений:

```php
$exceptions = Service::exceptions();
$exceptions->handle($exception);
```

#### Logger

**ВАЖНО: Использовать только встроенную систему логирования через `Service::logger()`**

Логирование:

```php
// Базовое логирование
$logger = Service::logger();
$logger->info('User logged in', ['user_id' => 123]);

// Именованные логгеры для категоризации
$dbLogger = Service::logger('database');
$authLogger = Service::logger('auth');
$reservationLogger = Service::logger('reservations');

// Логирование ошибок (ОБЯЗАТЕЛЬНО)
$dbLogger->logError('Database connection failed', [
    'error' => $e->getMessage(),
    'connection' => 'main_db'
]);

// Логирование исключений (ПРЕДПОЧТИТЕЛЬНО)
$authLogger->logException($exception, [
    'user_id' => $userId,
    'action' => 'login_attempt'
]);
```

## Использование в модулях

### В контроллерах

```php
class UsersController extends BaseController
{
    public function index()
    {
        $engines = Service::engines();
        $users = $engines->users->getAllUsers();

        $view = Service::view();
        $view->setData('users', $users);
        return $view->render('users/index');
    }
}
```

### В моделях

```php
class UserModel extends BaseModel
{
    public function save()
    {
        $query = Service::query();
        return $query->table('users')->insert($this->data);
    }
}
```

### В движках

```php
class UsersEngine
{
    public function getCurrentUser()
    {
        $session = Service::session();
        $userId = $session->get('user_id');

        if ($userId) {
            $query = Service::query();
            return $query->table('users')->where('id', $userId)->first();
        }

        return null;
    }
}
```

## Создание собственных сервисов

### 1. Создание сервиса

```php
class CustomService
{
    public function doSomething()
    {
        return 'Custom service action';
    }
}
```

### 2. Добавление в Services

```php
class Services extends BaseService
{
    public static function custom($getShared = true): CustomService
    {
        if ($getShared) {
            return static::getSharedInstance('custom');
        }
        return useClass('CustomService', true);
    }
}
```

### 3. Использование

```php
$custom = Service::custom();
$result = $custom->doSomething();
```

## Преимущества Service Locator

### 1. Централизация

- Единая точка доступа ко всем сервисам
- Упрощение управления зависимостями
- Консистентный API

### 2. Гибкость

- Возможность замены реализаций
- Легкое тестирование с mock-объектами
- Конфигурируемые сервисы

### 3. Производительность

- Shared instances для экономии памяти
- Ленивая загрузка сервисов
- Кэширование экземпляров

### 4. Поддерживаемость

- Четкая структура зависимостей
- Легкое добавление новых сервисов
- Документированные интерфейсы

## Лучшие практики

### 1. Использование shared instances

```php
// ✅ Правильно - используйте shared instances для основных сервисов
$app = Service::app();
$engines = Service::engines();

// ❌ Избегайте создания новых экземпляров без необходимости
$app = Service::app(false);
```

### 2. Проверка существования сервиса

```php
if (Service::exists('custom')) {
    $custom = Service::custom();
}
```

### 3. Сброс сервисов при необходимости

```php
// Сброс всех сервисов
Service::reset();

// Сброс конкретного сервиса
Service::resetSingle('app');
```

### 4. Использование в тестах

```php
class UserTest extends TestCase
{
    public function testUserCreation()
    {
        // Mock сервиса
        $mockQuery = $this->createMock(Query::class);
        Service::resetSingle('query');

        // Тестирование
        $user = new User();
        $result = $user->save();

        $this->assertTrue($result);
    }
}
```

## Заключение

Service Locator в Active Court обеспечивает мощную и гибкую систему управления зависимостями. Он упрощает разработку, тестирование и поддержку кода, предоставляя единообразный способ доступа ко всем компонентам системы.
