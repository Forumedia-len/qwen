# Система событий (Events)

## Обзор

Система событий реализована по паттерну **Observer / Event Dispatcher** и позволяет
рассоединить места возникновения событий (триггеры, разбросаны по всему коду) и
места их обработки (слушатели, объявлены в одном отдельном файле).

| Компонент | Расположение | Назначение |
|---|---|---|
| `Events` | `core/system/pattern/Events.php` | Статическая шина событий: регистрация слушателей, триггеры, приоритеты, wildcard. |
| `Event` | `core/system/pattern/event/Event.php` | Объект события: имя, данные, контекст, остановка цепочки. |
| Файл слушателей | `app/uses/events.php` | Единое место навешивания обработчиков на события. |
| Локальные переопределения | `app/uses/events.loc.php` | Необязательный локальный файл (подключается раньше основного). |
| Пример | `examples/events_trigger.php` | Рабочий пример регистрации и триггеров. |

Подключение файла слушателей выполняется лениво: при первом вызове
`Events::listen()` / `Events::trigger()` / `Events::hasListeners()` автоматически
вызывается `Events::initialize()`, которое подключает `events.loc.php` (если есть)
и `events.php`. Явного вызова в `bootstrap.php` для регистрации не требуется.

## Объект `Event`

```php
$event->name    // string, readonly — имя события (например 'reservation.created')
$event->data    // mixed — данные триггера; слушатели могут изменять их «по месту»
$event->padded  // mixed — дополнительный контекст (например, объект инициатора)

$event->stopPropagation();     // остановить передачу события следующим слушателям
$event->resumePropagation();   // возобновить (редко требуется)
$event->isStopped();           // проверен флаг остановки
```

Изменения, внесённые слушателем в `$event->data`, видят все последующие
слушатели этого же события — это позволяет строить конвейеры обработки
(обогащение данных, валидация, модификация).

## Регистрация слушателей (навешивание функций)

Все обработчики регистрируются **в одном файле** `app/uses/events.php`:

```php
use AC\core\system\pattern\Events;

// 1) Замыкание — самый частый вариант
Events::listen('user.registered', function (\AC\core\system\pattern\event\Event $e) {
  $mail = $e->data['email'] ?? null;
  // ... отправить приветственное письмо
});

// 2) Имя глобальной функции
Events::listen('user.registered', 'on_user_registered');

// 3) Статический метод класса строкой 'Класс::метод'
Events::listen('user.registered', \AC\app\services\SomeService::class . '::handle');

// 4) Метод объекта [объект, 'метод']
Events::listen('user.registered', [SomeService::some(), 'handle']);
```

Сигнатура:

```php
Events::listen(string $event, callable|string|array $callback, int $priority = 0, bool $once = false): int
Events::once(string $event, $callback, int $priority = 0): int   // одноразовый слушатель
Events::remove(string $event, ?int $id = null): void             // снять одного/всех
```

- `$priority` — больше значение, раньше исполняется (по умолчанию `0`);
  сортировка стабильна: при равном приоритете сохраняется порядок регистрации.
- `$once` — слушатель автоматически снимается после первого срабатывания.
- Возвращаемое значение — идентификатор слушателя для `Events::remove()`.

### Wildcard-события

Имена событий поддерживают точки и маски `*`:

- `reservation.*` реагирует на `reservation.created`, `reservation.canceled` и т.д.;
- `*` реагирует на любое событие, содержащее точку в имени.

## Триггеры (вызов событий из кода)

Триггеры рассредоточены по всему коду и вызываются статически, import класса
не обязателен благодаря полному имени:

```php
use AC\core\system\pattern\Events;

// Простой вызов
Events::trigger('system.booted');

// С данными (массив/объект/скаляр)
Events::trigger('reservation.created', ['id' => $reservationId, 'user' => $userId]);

// С дополнительным контекстом-инициатором
Events::trigger('payment.succeeded', $payload, $orderObject);

// Результат — объект Event: итоговые данные и флаг остановки
$result = Events::trigger('cart.validate', $cart);
if ($result->isStopped()) {
  // цепочка прервана (например, валидация не пройдена)
}
```

Рекомендуемые имена событий: `домен.действие` в прошедшем времени
(`user.registered`, `reservation.canceled`, `payment.succeeded`) — тогда wildcard
`домен.*` собирает все события домена.

### Отложенные события

Если `Events::trigger()` вызван **до** подключения файла слушателей (шина ещё не
инициализирована), событие попадает в очередь и будет исполнено автоматически
при первой инициализации. Принудительный прогон очереди — `Events::init()`.

## Полный API `Events`

| Метод | Описание |
|---|---|
| `listen($event, $callback, $priority = 0, $once = false)` | Зарегистрировать слушателя, вернуть его id. |
| `once($event, $callback, $priority = 0)` | Одноразовый слушатель. |
| `remove($event, $id = null)` | Снять слушателя по id или всех слушателей события. |
| `trigger($event, $data = null, $padded = null)` | Вызвать событие, вернуть `Event`. |
| `hasListeners($event)` | Есть ли слушатели (включая wildcard). |
| `initialize()` | Идемпотентно подключить файл слушателей. |
| `init()` | Исполнить отложенную очередь событий. |
| `reset()` | Полностью очистить состояние шины (для тестов). |

## Как добавить новое событие в систему

1. **Придумать имя** в формате `домен.действие` (например `stock.depleted`).
2. **Поставить триггер** в нужном месте бизнес-кода:
   ```php
   \AC\core\system\pattern\Events::trigger('stock.depleted', ['productId' => $id]);
   ```
3. **Навесить обработчик** в `app/uses/events.php`:
   ```php
   Events::listen('stock.depleted', fn($e) => StockService::notify($e->data['productId']));
   ```
4. При необходимости — приоритеты (`priority`), одноразовость (`once`) и
   остановка цепочки (`$e->stopPropagation()`).

Локальные переопределения обработчиков размещаются в `app/uses/events.loc.php`
(подключается перед основным файлом, в git не попадает).

## Тестирование

Перед тестами сбрасывайте состояние шины:

```php
AC\core\system\pattern\Events::reset();
```

Рабочая демонстрация всех возможностей — `examples/events_trigger.php`.
