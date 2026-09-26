# Модуль бронирований (Reservations)

## Обзор

Модуль бронирований является центральным компонентом системы Active Court, отвечающим за управление резервированием спортивных кортов и площадок. Модуль поддерживает различные типы кортов, временные слоты, систему оплаты и управление статусами бронирований.

## Входные данные и выбранная дата

`ReservationsModel` загружает выбранную площадку до определения типа, спорта и проверки даты.
Если `type_id` или `sport_id` отсутствуют, значения по умолчанию берутся из площадки; спорт площадки используется
только при совпадении её типа с текущим типом. Явно переданные значения сохраняют приоритет.
При выборе контроллера `ReservationsModule` использует тот же источник значений по умолчанию.
Без выбранной площадки сохраняется выбор первого активного типа и минимального спорта типа.

`getDate(bool $new = false)` проверяет запрос через `Request::validated()`:

- принимает `Y-m-d` и `d.m.Y`, проверяет существование даты и нормализует только формат в `Y-m-d`;
- перед проверкой доступности устанавливает предел бронирования для текущих `type_id` и `sport_id`;
- сохраняет дополнительный период отображения `PERIOD_SHOW_CALENDAR` для той же пары типа/спорта;
- отклоняет недоступную дату через `RequestValidationException`, вместо замены её сегодняшним днём;
- использует сегодня при отсутствии параметра, сохраняет исключение для администратора и ранее загруженную дату
  существующей брони при вызове без принудительного обновления.

Границы периодов не изменены: стандартная проверка движка включает последний разрешённый день,
а `ConfigHelper::checkDateInInterval()` использует интервал `[сегодня, сегодня + count)`.
Период отображения календаря не отменяет последующие проверки прав клиента, рабочих часов и правил заказа.
Недоступный день больше не перенаправляет календарь на сегодня: при открытии страницы в браузере сайт
показывает локализованный шаблон 404 с HTTP 404. AJAX и запросы без `Accept: text/html` сохраняют текстовый HTTP 400.

Имена основного и дополнительных игроков выводятся через `StringHelper::shield(..., doubleEncode: false)`:
поддерживается смесь исходных и однократно экранированных значений, включая сущности HTML5.
Подробности: [представление имён](../clients/clients.md#html-представление-имён).

Регрессионные тесты в соседнем репозитории: `Helpers/HtmlShieldingTest.php` и
`Reservations/ReservationDateContextTest.php`, группа `booking-input`.
В worktree включены `helpers.html-shielding`, `reservations.date-context` и `core.request-validation`.
Результаты и границы проверки: [отчёт](../../archive/reports/booking-names-date-fix-report.md).

## Основные возможности

### 🏟️ Типы кортов

- **Открытые корты** - корты на открытом воздухе
- **Закрытые корты** - крытые спортивные площадки
- **MC Arena** - специальные арены для определенных видов спорта

### ⏰ Временное управление

- Бронирование по часам
- Гибкие временные слоты
- Календарное планирование
- Повторяющиеся бронирования

### 📅 Отображение расписания (день/неделя) и ограничения по датам

- **Дневной и недельный режим** используют одинаковое ограничение доступности дат: если дата недоступна по правилам календаря (например, превышает `maxReservationUnixTime`), слоты должны отображаться как недоступные (серые) и быть **некликабельными**.
- **Ключевая проверка**: `Engines::checkDateAvaliableByUnixtime()` применяется для каждой конкретной даты слота/дня.
- В дневном расписании сайта (`close`, `open`, `mc_arena`) клик по доступному времени (`available` и `link_reserv`)
  открывает существующую форму `AuthWindow(true)` для посетителя без `client_id` и `client_bar`, если включена
  `USE_AUTHORIZATION`, как в недельном виде. Ссылка не выполняет переход и не отправляет форму бронирования.
  При выключенной авторизации такой посетитель видит время без ссылки. Для вошедших клиентов и режима `client_bar`
  сохраняются прежние ссылки бронирования, включая проверку `show_order_action` на открытых кортах.
- При `USE_PAGE_BREAK_FROM_BASE_AREA` табы расписания сайта и админки используют значения `areas.page` активных площадок. Если
  запрошенная страница отсутствует, выбирается первый активный таб для текущей пары `type_id` и `sport_id`; последовательная нумерация страниц
  не требуется. Получение страниц и их разрешение централизованы в `AreasEngine::resolveAreasPage()` и `AreasPageResolver`.

### Групповые тарифы площадок и праздники

- Административная таблица цен объединяет площадки при одинаковой длительности бронирования и совместимой недельной временной сетке: границы рабочих часов могут отличаться, если их смещение кратно периоду бронирования, а различия в наборе рабочих дней не создают отдельные группы.
- Изменение тарифа записывается только в площадки выбранной группы.
- Воскресные тарифы сохраняются и без обычного воскресного расписания: они используются праздниками с флагом `sunday_prices`.
- Выбор воскресного расписания централизован в `HolidayHelper::resolveWeekday()`: режим `Prices` учитывает настройку воскресных цен и флаг `sunday_prices`, режим `Times` — настройку воскресных часов и флаг `sunday_times`.
- `HolidayHelper::usesSundayScheduleForDate()` кеширует решение в рамках запроса по ключу `Y-m-d` + режим (`prices`/`times`): слоты одного дня не повторяют SQL к праздникам; режимы цен и рабочих часов независимы.
- Для воскресного тарифа меняется только день недели. Фактическая дата сохраняется, поэтому ценовой сезон определяется по дате праздника.
- `PricingReservationEngine` кеширует lookup тарифа слота в рамках запроса (`findPeriodPrice`): `hasPeriodPrice()` и `getPeriodPrice()` с одинаковыми `area` / клиент / timestamp не повторяют holiday-resolve и SQL. Гость (`null` / `0` / `'guest'`) нормализуется в один ключ кеша.
- Если продолжительность рабочего интервала не делится без остатка на период бронирования, группа не редактируется и администратор получает локализованную ошибку.

### 💳 Система оплаты

- Предоплата
- Оплата на месте
- Различные способы оплаты
- Система скидок и купонов

### 📊 Статусы бронирований

- Забронировано
- Подтверждено
- Отменено
- Завершено
- В ожидании оплаты

## Открытые корты

Тип площадки с alias `open` — отдельный контур бронирования: свой контроллер, модель, цены и правила второго игрока.

**Подробная документация:** [open-courts.md](open-courts.md) — маршрутизация, системы цен, проверки, join, гость, константы.

**Двойная игра** (выбор друзей, single/double): [double-game.md](double-game.md).

## Двойная игра на открытых кортах

Расширенный режим бронирования с выбором друзей, типа игры (single/double) и нескольких временных периодов.

Кратко: при `config('DoubleGame')->doubleFriendsEnabled()` модуль использует `DoubleOpenControllerReservation` вместо `OpenControllerReservation`. Детали — в [double-game.md](double-game.md).

## Структура модуля

```
core/modules/reservations/
├── ReservationsModule.php      # Основной класс модуля
├── controllers/                # Контроллеры
│   ├── CloseControllerReservation.php
│   ├── OpenControllerReservation.php
│   ├── DoubleOpenControllerReservation.php
│   └── McArenaControllerReservation.php
├── models/                     # Модели данных
│   ├── ReservationModel.php
│   ├── CourtModel.php
│   └── TimeSlotModel.php
└── views/                      # Представления
    ├── booking-form.php
    ├── calendar.php
    └── confirmation.php
```

## Основной класс модуля

### ReservationsModule

```php
class ReservationsModule extends BaseModule
{
    public function exec($action = null, $key = null, $controllerName = null)
    {
        return parent::exec($action, $key, $this->getReservationControllerClassName());
    }

    protected function getReservationControllerClassName(): string
    {
        $openModelClassName = 'OpenControllerReservation';
        $typeId = (int)Service::request()->_('type_id', module('areas')->useModel()?->getFirstActiveType());
        $alias  = module('areas')->useModel()?->getEngine()?->getAliasType($typeId);

        if (config('DoubleGame')->doubleFriendsEnabled($typeId, (int)Service::request()->_('sport_id', 0), (int)Service::request()->_('area_id', 0))) {
            $openModelClassName = 'Double' . $openModelClassName;
        }

        return match ($alias) {
            'open'  => $openModelClassName,
            default => ucfirst(StringHelper::underscoreToCamelCase($alias)) . 'ControllerReservation',
        };
    }
}
```

## Контроллеры

### CloseControllerReservation

Контроллер для закрытых кортов:

```php
class CloseControllerReservation extends BaseController
{
    public function index()
    {
        // Получение доступных кортов
        $engines = Service::engines();
        $courts = $engines->areas->getAvailableCourts('closed');

        // Отображение формы бронирования
        $view = Service::view();
        $view->setData('courts', $courts);
        return $view->render('reservations/booking-form');
    }

    public function book()
    {
        // Обработка бронирования
        $data = Service::request()->getPost();

        $engines = Service::engines();
        $result = $engines->reservations->createReservation($data);

        if ($result) {
            return Service::redirect()->to('/reservations/confirmation');
        }

        return $this->index();
    }
}
```

### OpenControllerReservation

Контроллер для открытых кортов:

```php
class OpenControllerReservation extends BaseController
{
    public function index()
    {
        // Получение погодных данных
        $weather = $this->getWeatherData();

        // Получение доступных открытых кортов
        $engines = Service::engines();
        $courts = $engines->areas->getAvailableCourts('open');

        $view = Service::view();
        $view->setData([
            'courts' => $courts,
            'weather' => $weather
        ]);
        return $view->render('reservations/open-courts');
    }
}
```

### McArenaControllerReservation

Контроллер для MC Arena:

```php
class McArenaControllerReservation extends BaseController
{
    public function index()
    {
        // Специальная логика для MC Arena
        $engines = Service::engines();
        $arenaData = $engines->areas->getArenaData();

        $view = Service::view();
        $view->setData('arena', $arenaData);
        return $view->render('reservations/arena');
    }
}
```

## Движок бронирований

### ReservationsEngine

```php
class ReservationsEngine
{
    public function createReservation($data)
    {
        // Валидация данных
        if (!$this->validateReservationData($data)) {
            return false;
        }

        // Проверка доступности
        if (!$this->checkAvailability($data)) {
            return false;
        }

        // Создание бронирования
        $reservationId = $this->insertReservation($data);

        // Отправка уведомлений
        $this->sendNotifications($reservationId);

        return $reservationId;
    }

    public function getReservationDataByClient($clientId, $filters = null, &$reservationData)
    {
        $query = Service::query();

        $query->table('reservations')
              ->where('client_id', $clientId)
              ->orderBy('start', 'DESC');

        if ($filters) {
            $this->applyFilters($query, $filters);
        }

        $reservationData = $query->get();
        return !empty($reservationData);
    }

    public function cancelReservation($reservationId, $reason = '')
    {
        $query = Service::query();

        $result = $query->table('reservations')
                       ->where('id', $reservationId)
                       ->update([
                           'status' => 'cancelled',
                           'cancel_reason' => $reason,
                           'cancelled_at' => date('Y-m-d H:i:s')
                       ]);

        if ($result) {
            $this->sendCancellationNotification($reservationId);
        }

        return $result;
    }
}
```

## Модели данных

### ReservationModel

```php
class ReservationModel extends BaseModel
{
    protected $table = 'reservations';

    protected $fillable = [
        'client_id',
        'court_id',
        'start_time',
        'end_time',
        'status',
        'price',
        'payment_method',
        'notes'
    ];

    public function client()
    {
        return $this->belongsTo('ClientModel', 'client_id');
    }

    public function court()
    {
        return $this->belongsTo('CourtModel', 'court_id');
    }

    public function calculatePrice()
    {
        $basePrice = $this->court->hourly_rate;
        $duration = $this->getDuration();

        // Применение скидок
        $discount = $this->calculateDiscount();

        return ($basePrice * $duration) - $discount;
    }
}
```

## API методы

### Создание бронирования

```php
POST /api/reservations
{
    "court_id": 1,
    "start_time": "2024-01-15 14:00:00",
    "end_time": "2024-01-15 16:00:00",
    "client_id": 123,
    "payment_method": "card"
}
```

### Получение доступных слотов

```php
GET /api/reservations/available-slots?court_id=1&date=2024-01-15
```

### Отмена бронирования

```php
POST /api/reservations/{id}/cancel
{
    "reason": "Изменение планов"
}
```

## Интеграция с другими модулями

### Платежи

```php
// В контроллере бронирования
public function processPayment()
{
    $reservationId = Service::request()->getPost('reservation_id');
    $paymentData = Service::request()->getPost('payment');

    $paymentEngine = Service::engines()->payment;
    $result = $paymentEngine->processPayment($reservationId, $paymentData);

    if ($result) {
        // Обновление статуса бронирования
        $this->updateReservationStatus($reservationId, 'confirmed');
    }
}
```

### Уведомления

```php
// Отправка уведомлений о бронировании
private function sendReservationNotification($reservationId)
{
    $notificationEngine = Service::engines()->notifications;
    $reservation = $this->getReservation($reservationId);

    $notificationEngine->sendReservationConfirmation($reservation);
}
```

### Клиенты

```php
// Получение истории бронирований клиента
public function getClientHistory($clientId)
{
    $engines = Service::engines();
    $reservations = [];

    $engines->reservations->getReservationDataByClient($clientId, null, $reservations);

    return $reservations;
}
```

## Архив удалённых бронирований

Удалённые бронирования сохраняются в `reservations_deleted`. Поле
`reservation_data` содержит JSON-снимок брони, а `street_friends` в новых
снимках предварительно преобразуется из PHP-serialized строки в массив.

При чтении старых записей `ArchivedReservationDataDecoder`:

- декодирует корректный JSON и нормализует `street_friends` в массив;
- восстанавливает старый JSON с неэкранированной serialized-строкой, определяя
  её конец по фактической валидности serialized-значения, а не по фиксированному
  количеству закрывающих скобок;
- если `street_friends` восстановить нельзя, отбрасывает это поле и весь
  следующий за ним хвост, затем возвращает из корректного префикса только
  необходимые для истории поля `start`, `finish`, `encash`, `price`, а
  `street_friends` нормализует в пустой массив;
- не разрешает создание объектов при `unserialize`;
- не передаёт внутренние диагностические сообщения в интерфейс.

Если в обрезанном префиксе нет всех обязательных полей или отдельную запись
восстановить невозможно по другой причине, она пропускается, а причина и
идентификаторы брони и клиента записываются в журнал `reservation_archive`.
Содержимое `reservation_data` в журнал не добавляется, поскольку может включать
персональные данные. Остальные строки архива продолжают отображаться.

## Конфигурация

### Настройки модуля

```php
// config/reservations.php
return [
    'max_advance_booking_days' => 30,
    'min_advance_booking_hours' => 2,
    'cancellation_deadline_hours' => 24,
    'auto_cancel_unpaid_minutes' => 15,
    'max_concurrent_bookings' => 3,
    'enable_recurring_bookings' => true,
    'weather_integration' => true,
];
```

### Типы кортов

```php
// Определение типов кортов
define('COURT_TYPE_CLOSED', 1);
define('COURT_TYPE_OPEN', 2);
define('COURT_TYPE_ARENA', 3);
```

## Шаблоны

### Форма бронирования

```php
<!-- app/tpl/site/views/reservations/booking-form.php -->
<div class="booking-form">
    <form method="POST" action="/reservations/book">
        <div class="form-group">
            <label>Выберите корт:</label>
            <select name="court_id" required>
                <?php foreach ($courts as $court): ?>
                    <option value="<?= $court['id'] ?>">
                        <?= $court['name'] ?> - <?= $court['hourly_rate'] ?> €/час
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Дата и время:</label>
            <input type="datetime-local" name="start_time" required>
        </div>

        <div class="form-group">
            <label>Продолжительность:</label>
            <select name="duration">
                <option value="1">1 час</option>
                <option value="2">2 часа</option>
                <option value="3">3 часа</option>
            </select>
        </div>

        <button type="submit">Забронировать</button>
    </form>
</div>
```

## События и хуки

### События бронирования

```php
// События, которые можно перехватить
'reservation.created'     // Бронирование создано
'reservation.confirmed'   // Бронирование подтверждено
'reservation.cancelled'   // Бронирование отменено
'reservation.completed'   // Бронирование завершено
```

### Обработчики событий

```php
// Пример обработчика события
class ReservationEventHandler
{
    public function onReservationCreated($reservationId)
    {
        // Отправка email подтверждения
        $this->sendConfirmationEmail($reservationId);

        // Обновление календаря
        $this->updateCalendar($reservationId);
    }
}
```

## Тестирование

### Unit тесты

```php
class ReservationTest extends TestCase
{
    public function testCreateReservation()
    {
        $data = [
            'court_id' => 1,
            'start_time' => '2024-01-15 14:00:00',
            'end_time' => '2024-01-15 16:00:00',
            'client_id' => 123
        ];

        $engines = Service::engines();
        $result = $engines->reservations->createReservation($data);

        $this->assertTrue($result > 0);
    }

    public function testCheckAvailability()
    {
        $engines = Service::engines();
        $available = $engines->reservations->checkAvailability([
            'court_id' => 1,
            'start_time' => '2024-01-15 14:00:00',
            'end_time' => '2024-01-15 16:00:00'
        ]);

        $this->assertTrue($available);
    }
}
```

## Лучшие практики

### 1. Валидация данных

- Всегда проверяйте доступность корта
- Валидируйте временные слоты
- Проверяйте права доступа клиента

### 2. Обработка ошибок

- Используйте транзакции для критических операций
- Логируйте все действия с бронированиями
- Предоставляйте понятные сообщения об ошибках

### 3. Производительность

- Кэшируйте данные о доступности кортов
- Используйте индексы в базе данных
- Оптимизируйте запросы для больших объемов данных

### 4. Безопасность

- Проверяйте права доступа
- Валидируйте все входные данные
- Защищайте от SQL-инъекций

## Инициализация модели и контекст устройства

`ReservationsModel` реализует `InitializableModelInterface`. Конструктор подготавливает
базовый движок и сохраняет параметры `$id`, `$tmp`, `$second`, но не загружает бронирование
и не проверяет параметры HTTP-запроса.

`BaseController` после создания модели передаёт ей `admin` и `touch`, затем вызывает
`initialize()`. Прежние отдельные вызовы `getPage()`, `getDate(true)` и `getTimes()` из
общего контроллера удалены: они выполняются в составе инициализации бронирования.
Модели без интерфейса дополнительную инициализацию не проходят.

`initialize()` выполняет `initializeData()` один раз после успешной загрузки. Наследники
переопределяют `initializeData()`, вызывая родительский метод перед загрузкой своих
полей. Повторный вызов `initialize()` сохраняет уже изменённые данные модели.

Внутри контроллеров дополнительные модели света, абонементов и основного игрока
подготавливаются через `initializeModel($model)`. При прямом `new ReservationsModel(...)`
вне контроллера вызывающий код должен задать контекст и вызвать `initialize()` до работы
с данными. `module('reservations')->useModel(...)` сохраняет контракт готовой модели:
сам устанавливает контекст текущего устройства и выполняет инициализацию. Это также
сохраняет загрузку обычных и временных бронирований в обработчиках онлайн-платежей.

Проверка даты выполняется уже с установленным административным контекстом. Админка
может обрабатывать прошлые бронирования; на сайте и touchscreen сохраняются ограничения
календаря. Синтаксис входных параметров проверяется для всех устройств.

Если корректная дата выходит за доступный период, `getDate()` выбрасывает
`ReservationDateUnavailableException`. `ReservationsController::runController()`
перехватывает её при подготовке модели или выполнении действия и показывает
локализованное сообщение через `setErrorMessage()` и `getInfoBlockContent()`.
Прерванное действие не продолжается, дата не подменяется сегодняшней. Ссылка возврата
открывает календарь текущего типа площадки и спорта на сегодня.
Календарь навигации сохраняется и при ошибке. Если дата модели ещё не установлена,
для календаря используется сегодня; это не меняет дату заказа и не возобновляет действие.
Если дата модели уже была установлена, календарь сохраняет её.
`ReservationsController` так же перехватывает остальные `RequestValidationException`
при инициализации и выполнении действия. Ошибки формата даты, времени, выбора площадки,
спорта, абонемента, оплаты и состояний услуг показываются обычным блоком ошибки
с локализованной подсказкой. Внутренние сообщения валидаторов и исходные значения
в этот блок не передаются. Ошибки выбора контроллера по `area_id`, `type_id`, `sport_id`
модуль передаёт в `ReservationsController::showValidationError()` без выполнения действия.
Если тип площадки или спорт ещё не загружены, календарь использует первый активный
тип и доступный спорт; параметры заказа и запроса при этом не меняются.
Эти ошибки валидации не передаются общему HTTP-обработчику и не создают ответ 404.
На маршрутах `reservations.php` и `reservations` предварительная подготовка сайта
передаёт проверку `date` и `type_id` модулю бронирований. Общая форма входа на странице
ошибки не проверяет эти параметры повторно и не переносит их в скрытые поля.
Исключения БД и программные ошибки не перехватываются как ошибки ввода.
При загрузке через `useModel()` вне контроллера исключение по-прежнему обрабатывает вызывающий код.

## Проверка ввода и защита от XSS

Параметры `type_id`, `sport_id`, `area_id`, `page`, `week`, `date` и `time` проверяются через
`Service::request()->validated()` до использования моделью бронирования. Тип площадки проверяется также
до выбора контроллера. `date` принимает `Y-m-d` из календаря и `d.m.Y` из формы подтверждения и приводится
к `Y-m-d`. Проверка доступности даты по бизнес-правилам сохраняется; ошибки формата
показываются внутри контроллера стандартным блоком ошибки бронирования.
Для массива `time` проверяются все ключи и значения, включая не первый выбранный период.
В моделях света и абонементов `ticket_id` проверяется как положительное целое число;
отсутствующий параметр остаётся `null`, чтобы сохранялся сценарий света без абонемента.
Для активных состояний WebIo проверяется карта времени; неиспользуемые поля не отражаются в ответе.
Проверенные флаги сохраняются как `0` или `1`: присутствие периода в карте само по себе не включает услугу.

Форма `lightOrder` на сайте и touchscreen передаёт состояния в том же формате карты времени,
что и обычная форма бронирования: например, `light_state[22:30]=1`. Формат одинаков для checkbox
и скрытых полей света, отопления и сетки. Общая проверка применяется без исключений по действию;
`LightModelOrder` проверяет всю карту и берёт флаг текущего периода, по умолчанию `0`.
Состояния других периодов не включают свет для выбранного времени.

`memo`, `prepayment`, `option_id` и поля формы входа проверяются как строки. Текстовые данные не проходят
глобальную очистку от тегов и не кодируются заранее. Кодирование выполняется в месте вывода:
скрытые поля, данные гостевой сессии, имя в шапке, список игроков и имя второго игрока в расписании.
Имена и комментарий основного бронирования уже кодируются в `ReservationsVisualizationCommon`;
готовая разметка этого компонента повторно целиком не кодируется.

Общая форма входа `_auth.php` больше не подставляет GET `date`/`type_id` непосредственно в атрибуты
и не вставляет `tab` в JavaScript. Вкладка передаётся как проверенное число `0|1` через `data-auth-tab`,
инициализируется в CDN `common.js` после загрузки DOM. Параметры общей формы проверяются в
`SiteDeviceAction::before()` до вывода шаблона. Redirect после входа собирается через `http_build_query()`.
CDN `all_device.js` выводит цену и время AJAX-ответа как текст.

Контракт метода описан в [Service Locator](../../technical/service-locator.md).
Результаты проверки и границы покрытия: [отчёт об исправлении XSS](../../archive/reports/reservations-xss-fix-report.md).

## Заключение

Модуль бронирований является ключевым компонентом системы Active Court, обеспечивающим полный цикл управления резервированием спортивных кортов. Модульная архитектура позволяет легко расширять функциональность и адаптировать систему под различные требования.
