# Система устройств (Device Actions)

## Обзор

Система устройств в Active Court обеспечивает поддержку различных типов интерфейсов и устройств через единую архитектуру. Каждый тип устройства имеет свой собственный DeviceAction, который определяет специфику взаимодействия с пользователем.

## Архитектура системы устройств

### DeviceActionInterface

**Расположение**: `core/system/actions/DeviceActionInterface.php`

**Назначение**: Базовый интерфейс для всех действий устройств.

```php
interface DeviceActionInterface
{
    /**
     * Выполнение действия для устройства
     */
    public function execute($module, $action = null, $key = null, $controllerName = null);

    /**
     * Получение типа устройства
     */
    public function getDeviceType(): string;

    /**
     * Проверка поддержки действия
     */
    public function supportsAction($action): bool;

    /**
     * Получение шаблона для устройства
     */
    public function getTemplate($template, $data = []): string;
}
```

## Типы устройств

### WidgetDeviceAction — встраиваемый интерфейс

`app/actions/device/WidgetDeviceAction.php` обслуживает `/widget/` и наследует текущий сайт.
Сессия и ресурсы устройства отделены; контроллеры, шаблоны, структура и переводы поддерживают fallback на `site`.
Фактические точки входа, порядок наследования и сборка описаны в [документации widget](widget.md).

### Страницы HTTP-ошибок

`core/system/view/HttpErrorPage.php` связывает публичные шаблоны `errors/html/error_404.php`
и `errors/html/production.php` с основным шаблоном текущего устройства.
Основной шаблон ищется через `Service::templates()` с тем же наследованием интерфейсов,
что и обычная страница (`widget → site`):

| Устройство | Основной шаблон |
|---|---|
| site | `app/tpl/site/default.php` |
| admin, авторизованный пользователь | `app/tpl/admin/internal.php` |
| admin, до авторизации | `app/tpl/admin/auth.php` |
| touch | `app/tpl/touch/template/{open,close}/default.php`, по типу площадки из сессии |
| Другие устройства | Их `default` при наличии; иначе простой резервный HTML |

Локальные переопределения шаблонов разрешаются обычным автозагрузчиком. Обработчик не запускает
`before()`, контроллер или действие повторно. Навигация использует структуру устройства;
отклонённые параметры запроса не копируются в общую форму входа. Контекстные вкладки админки
на странице HTTP-ошибки не выводятся. Шапка сайта допускает повторный рендеринг после сбоя страницы.
Для сайта левый блок не заполняется пустой строкой: основной шаблон выводит обычный сайдбар.

`App::send404()` отправляет настоящий ответ 404 вместо перенаправления на главную.
При загрузке файловой страницы `App::uploadStaticFilePhp()` проверяет найденный путь
до `require_once`: отсутствующий файл (например, опечатка в URL) вызывает `send404()`.
Для HTML-запроса он использует общий шаблон ошибки; для AJAX и запросов без `Accept: text/html`
возвращает локализованный простой текст. Валидация в `App` сохраняет прежние коды 404 для HTML
и 400 для программных клиентов. Публичный `production.php` сохраняет переданный код 4xx/5xx
в заголовке страницы, а HTTP-статус выставляет обработчик исключения.

В публичном выводе нет текста внутренних исключений. При сбое основного шаблона или его
зависимостей незавершённый HTML отбрасывается и выводится минимальная страница с тем же кодом
и безопасным сообщением. Диагностический шаблон `error_exception.php` режима разработки
сохраняет существующий отдельный вывод; ответы API и CLI не оборачиваются в шаблон устройства.

### SiteDeviceAction - Веб-сайт

**Расположение**: `core/system/actions/SiteDeviceAction.php`

**Назначение**: Основной интерфейс для пользователей через веб-браузер.

#### Особенности

- **Адаптивный дизайн** - Поддержка различных размеров экранов
- **SEO-оптимизация** - Поисковая оптимизация
- **Полная функциональность** - Доступ ко всем возможностям системы
- **Многоязычность** - Поддержка различных языков

#### Основные методы

```php
class SiteDeviceAction implements DeviceActionInterface
{
    public function execute($module, $action = null, $key = null, $controllerName = null)
    {
        // Выполнение действия для веб-сайта
        $result = $this->executeModule($module, $action, $key, $controllerName);

        // Рендеринг представления
        return $this->renderView($result);
    }

    public function getDeviceType(): string
    {
        return 'site';
    }

    protected function renderView($data)
    {
        // Рендеринг HTML представления
        return Service::view()->render('site/layout', $data);
    }
}
```

#### Шаблоны

```php
// Структура шаблонов для сайта
site/tpl/site/
├── layout.php          // Основной макет
├── header.php          // Заголовок
├── footer.php          // Подвал
├── navigation.php      // Навигация
├── content/            // Контентные страницы
│   ├── home.php       // Главная страница
│   ├── reservations.php // Бронирования
│   ├── profile.php    // Профиль пользователя
│   └── contact.php    // Контакты
└── components/         // Компоненты
    ├── calendar.php    // Календарь
    ├── form.php        // Формы
    └── modal.php       // Модальные окна
```

### AdminDeviceAction - Админ-панель

**Расположение**: `core/system/actions/AdminDeviceAction.php`

**Назначение**: Интерфейс для администраторов системы.

#### Особенности

- **Расширенная функциональность** - Полный доступ к управлению системой
- **Аналитика и отчеты** - Детальная статистика и аналитика
- **Управление пользователями** - Администрирование клиентов и пользователей
- **Настройки системы** - Конфигурация всех параметров

#### Основные методы

```php
class AdminDeviceAction implements DeviceActionInterface
{
    public function execute($module, $action = null, $key = null, $controllerName = null)
    {
        // Проверка прав доступа
        $this->checkAdminPermissions();

        // Выполнение действия
        $result = $this->executeModule($module, $action, $key, $controllerName);

        // Рендеринг админ-интерфейса
        return $this->renderAdminView($result);
    }

    public function getDeviceType(): string
    {
        return 'admin';
    }

    protected function checkAdminPermissions()
    {
        // Проверка прав администратора
        if (!Service::auth()->isAdmin()) {
            throw new AccessDeniedException('Admin access required');
        }
    }
}
```

#### Шаблоны

```php
// Структура шаблонов для админ-панели
site/tpl/admin/
├── layout.php          // Админ-макет
├── dashboard.php       // Панель управления
├── users/              // Управление пользователями
│   ├── list.php       // Список пользователей
│   ├── edit.php       // Редактирование
│   └── create.php     // Создание
├── reservations/       // Управление бронированиями
│   ├── list.php       // Список бронирований
│   ├── calendar.php   // Календарь
│   └── reports.php    // Отчеты
├── areas/              // Управление кортами
│   ├── list.php       // Список кортов
│   ├── edit.php       // Редактирование
│   └── schedule.php   // Расписание
└── settings/           // Настройки
    ├── general.php    // Общие настройки
    ├── payment.php    // Настройки платежей
    └── system.php     // Системные настройки
```

### TouchDeviceAction - Сенсорные экраны

**Расположение**: `core/system/actions/TouchDeviceAction.php`

**Назначение**: Интерфейс для терминалов самообслуживания.

#### Особенности

- **Упрощенный интерфейс** - Крупные кнопки и элементы
- **Быстрое бронирование** - Минимальное количество шагов
- **Сенсорная оптимизация** - Оптимизация для касаний
- **Ограниченная функциональность** - Только основные операции

#### Основные методы

```php
class TouchDeviceAction implements DeviceActionInterface
{
    public function execute($module, $action = null, $key = null, $controllerName = null)
    {
        // Проверка типа устройства
        $this->validateTouchDevice();

        // Выполнение действия
        $result = $this->executeModule($module, $action, $key, $controllerName);

        // Рендеринг сенсорного интерфейса
        return $this->renderTouchView($result);
    }

    public function getDeviceType(): string
    {
        return 'touch';
    }

    protected function validateTouchDevice()
    {
        // Проверка, что это действительно сенсорное устройство
        if (!$this->isTouchDevice()) {
            throw new DeviceMismatchException('Touch device required');
        }
    }
}
```

#### Шаблоны

```php
// Структура шаблонов для сенсорных экранов
site/tpl/touch/
├── layout.php          // Сенсорный макет
├── welcome.php         // Приветственный экран
├── reservation/        // Бронирование
│   ├── select_court.php // Выбор корта
│   ├── select_time.php  // Выбор времени
│   ├── confirm.php      // Подтверждение
│   └── success.php      // Успешное бронирование
├── info/               // Информация
│   ├── schedule.php    // Расписание
│   ├── prices.php      // Цены
│   └── contact.php     // Контакты
└── components/         // Компоненты
    ├── big_button.php  // Большие кнопки
    ├── calendar.php    // Календарь
    └── keyboard.php    // Виртуальная клавиатура
```

### DisplayDeviceAction - Дисплеи

**Расположение**: `core/system/actions/DisplayDeviceAction.php`

**Назначение**: Информационные экраны для отображения статуса кортов.

Фактический поток данных, настройки и текущие ограничения табло описаны в
[документации информационного дисплея](display.md).

#### Особенности

- **Только для чтения** - Отображение информации без взаимодействия
- **Автообновление** - Периодическое обновление данных
- **Статус кортов** - Отображение доступности кортов
- **Минимальный интерфейс** - Простое отображение информации

#### Основные методы

```php
class DisplayDeviceAction implements DeviceActionInterface
{
    public function execute($module, $action = null, $key = null, $controllerName = null)
    {
        // Получение данных для отображения
        $data = $this->getDisplayData();

        // Рендеринг информационного экрана
        return $this->renderDisplayView($data);
    }

    public function getDeviceType(): string
    {
        return 'display';
    }

    protected function getDisplayData()
    {
        // Получение актуальных данных о кортах
        $engines = Service::engines();
        return [
            'areas' => $engines->areas->getActiveAreas(),
            'schedule' => $engines->reservations->getCurrentSchedule(),
            'time' => date('H:i'),
            'date' => date('Y-m-d')
        ];
    }
}
```

#### Шаблоны

```php
// Структура шаблонов для дисплеев
site/tpl/display/
├── layout.php          // Макет дисплея
├── status.php          // Статус кортов
├── schedule.php        // Расписание
├── info.php           // Информация
└── components/        // Компоненты
    ├── court_status.php // Статус корта
    ├── time_display.php // Отображение времени
    └── weather.php     // Погода
```

### MapiDeviceAction - API

**Расположение**: `core/system/actions/MapiDeviceAction.php`

**Назначение**: Программный интерфейс для интеграции с внешними системами.

#### Особенности

- **RESTful API** - Стандартный REST интерфейс
- **JSON ответы** - Структурированные данные
- **Аутентификация** - API ключи и токены
- **Документация** - Swagger/OpenAPI спецификация

#### Основные методы

```php
class MapiDeviceAction implements DeviceActionInterface
{
    public function execute($module, $action = null, $key = null, $controllerName = null)
    {
        // Проверка API ключа
        $this->validateApiKey();

        // Выполнение API запроса
        $result = $this->executeApiRequest($module, $action, $key, $controllerName);

        // Формирование JSON ответа
        return $this->renderJsonResponse($result);
    }

    public function getDeviceType(): string
    {
        return 'mapi';
    }

    protected function validateApiKey()
    {
        $apiKey = Service::request()->getHeader('X-API-Key');
        if (!$this->isValidApiKey($apiKey)) {
            throw new UnauthorizedException('Invalid API key');
        }
    }

    protected function renderJsonResponse($data)
    {
        return Service::response()->json($data);
    }
}
```

#### API Endpoints

```php
// Примеры API endpoints
GET    /api/v1/areas              // Список кортов
GET    /api/v1/areas/{id}         // Информация о корте
GET    /api/v1/reservations       // Список бронирований
POST   /api/v1/reservations       // Создание бронирования
PUT    /api/v1/reservations/{id}  // Обновление бронирования
DELETE /api/v1/reservations/{id}  // Отмена бронирования
GET    /api/v1/clients/{id}       // Информация о клиенте
POST   /api/v1/auth/login         // Авторизация
```

## Определение типа устройства

### Автоматическое определение

```php
// В App.php
protected function initializeDeviceAction(): void
{
    $request = Service::request();
    $userAgent = $request->getUserAgent();
    $path = $request->getPath();

    // Определение типа устройства
    if (strpos($path, '/admin') === 0) {
        $this->deviceAction = new AdminDeviceAction();
    } elseif (strpos($path, '/touch') === 0) {
        $this->deviceAction = new TouchDeviceAction();
    } elseif (strpos($path, '/display') === 0) {
        $this->deviceAction = new DisplayDeviceAction();
    } elseif (strpos($path, '/api') === 0) {
        $this->deviceAction = new MapiDeviceAction();
    } else {
        $this->deviceAction = new SiteDeviceAction();
    }
}
```

### Ручное определение

```php
// Установка типа устройства вручную
App::setAppParam('device_type', 'touch');
App::setAppParam('device_action', new TouchDeviceAction());
```

## Адаптивные шаблоны

### Система тем

```php
// Структура тем для разных устройств
site/tpl/
├── site/               // Веб-сайт
│   ├── default/       // Тема по умолчанию
│   ├── modern/        // Современная тема
│   └── classic/       // Классическая тема
├── admin/             // Админ-панель
│   ├── default/       // Стандартная админка
│   └── modern/        // Современная админка
├── touch/             // Сенсорные экраны
│   ├── default/       // Стандартный интерфейс
│   └── kiosk/         // Киоск-режим
└── display/           // Дисплеи
    ├── default/       // Стандартный дисплей
    └── large/         // Большой дисплей
```

### Переключение тем

```php
// Переключение темы
public function setTheme($theme)
{
    Service::session()->set('theme', $theme);
}

public function getTheme()
{
    return Service::session()->get('theme', 'default');
}
```

## Интеграция с модулями

### Адаптивные контроллеры

```php
// Контроллер с поддержкой разных устройств
class ReservationsController extends BaseController
{
    public function index()
    {
        $deviceType = Service::app()->getDeviceActionInstance()->getDeviceType();

        switch ($deviceType) {
            case 'site':
                return $this->renderSiteView();
            case 'admin':
                return $this->renderAdminView();
            case 'touch':
                return $this->renderTouchView();
            case 'display':
                return $this->renderDisplayView();
            case 'mapi':
                return $this->renderApiResponse();
        }
    }

    protected function renderSiteView()
    {
        // Полная версия для веб-сайта
        return Service::view()->render('site/reservations/index', $this->getData());
    }

    protected function renderTouchView()
    {
        // Упрощенная версия для сенсорных экранов
        return Service::view()->render('touch/reservations/index', $this->getTouchData());
    }
}
```

### Адаптивные модели

```php
// Модель с адаптивными методами
class ReservationModel extends BaseModel
{
    public function getReservationsForDevice($deviceType, $filters = [])
    {
        switch ($deviceType) {
            case 'site':
                return $this->getFullReservations($filters);
            case 'touch':
                return $this->getSimpleReservations($filters);
            case 'display':
                return $this->getCurrentReservations();
            case 'mapi':
                return $this->getApiReservations($filters);
        }
    }

    protected function getSimpleReservations($filters)
    {
        // Упрощенные данные для сенсорных экранов
        return $this->query()
            ->select(['id', 'area_id', 'date', 'time', 'status'])
            ->where($filters)
            ->limit(10)
            ->get();
    }
}
```

## Производительность

### Кэширование по устройствам

```php
// Кэширование для разных устройств
public function getCachedData($key, $deviceType)
{
    $cacheKey = "{$key}_{$deviceType}";
    return Service::cache()->get($cacheKey);
}

public function setCachedData($key, $data, $deviceType, $ttl = 3600)
{
    $cacheKey = "{$key}_{$deviceType}";
    Service::cache()->set($cacheKey, $data, $ttl);
}
```

### Оптимизация для устройств

```php
// Оптимизация данных для разных устройств
public function optimizeForDevice($data, $deviceType)
{
    switch ($deviceType) {
        case 'touch':
            return $this->optimizeForTouch($data);
        case 'display':
            return $this->optimizeForDisplay($data);
        case 'mapi':
            return $this->optimizeForApi($data);
        default:
            return $data;
    }
}
```

## Безопасность

### Проверка прав доступа

```php
// Проверка прав для разных устройств
public function checkDevicePermissions($deviceType, $action)
{
    switch ($deviceType) {
        case 'admin':
            return Service::auth()->isAdmin();
        case 'mapi':
            return $this->validateApiPermissions($action);
        case 'touch':
            return $this->validateTouchPermissions($action);
        default:
            return true;
    }
}
```

### Валидация устройств

```php
// Валидация типа устройства
public function validateDevice($deviceType)
{
    $allowedDevices = ['site', 'admin', 'touch', 'display', 'mapi'];

    if (!in_array($deviceType, $allowedDevices)) {
        throw new InvalidDeviceException("Invalid device type: {$deviceType}");
    }
}
```

## Расширение системы устройств

### Создание нового типа устройства

```php
// Новый тип устройства
class MobileDeviceAction implements DeviceActionInterface
{
    public function execute($module, $action = null, $key = null, $controllerName = null)
    {
        // Логика для мобильного устройства
        $result = $this->executeModule($module, $action, $key, $controllerName);
        return $this->renderMobileView($result);
    }

    public function getDeviceType(): string
    {
        return 'mobile';
    }

    protected function renderMobileView($data)
    {
        return Service::view()->render('mobile/layout', $data);
    }
}
```

### Регистрация нового устройства

```php
// Регистрация в системе
protected function initializeDeviceAction(): void
{
    // ... существующий код ...

    if ($this->isMobileDevice()) {
        $this->deviceAction = new MobileDeviceAction();
    }
}

protected function isMobileDevice(): bool
{
    $userAgent = Service::request()->getUserAgent();
    return preg_match('/Mobile|Android|iPhone|iPad/', $userAgent);
}
```
