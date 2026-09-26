<?php

use AC\core\system\pattern\Events;

/*
 | -------------------------------------------------------------------------
 | Регистрация слушателей системных событий
 | -------------------------------------------------------------------------
 |
 | Здесь навешиваются обработчики на события системы. Триггеры рассредоточены
 | по коду и вызываются через Events::trigger('имя.события', $data, $padded).
 |
 | Поддерживаемые форматы слушателя:
 |   - замыкание:        fn(Events\Event $e) => ...
 |   - 'Класс::метод':   My\Service::class . '::handle'
 |   - [объект, метод]:  [Service::some(), 'method']
 |   - имя функции:      'my_function'
 |
 | Аргументы Events::listen($event, $callback, $priority = 0, $once = false):
 |   $priority — больше значение, раньше исполняется (по умолчанию 0);
 |   $once     — снять слушателя после первого срабатывания.
 |
 | Wildcard-события: 'reservation.*' реагирует на 'reservation.created',
 | 'reservation.canceled' и т.д.; '*' — на любое событие с точкой в имени.
 |
 | Локальные переопределения размещаются в events.loc.php рядом с этим файлом.
 */

/*
 | -------------------------------------------------------------------------
 | ПРИМЕРЫ: как навешивать функции-обработчики
 | -------------------------------------------------------------------------
 |
 | 1) Замыкание (самый частый вариант):
 |    Events::listen('user.registered', function (Events\Event $e) {
 |      $mail = $e->data['email'] ?? null;        // данные триггера
 |      // ... отправить приветственное письмо
 |    });
 |
 | 2) Имя глобальной функции из этого же файла (или любой загруженной):
 |    Events::listen('user.registered', 'on_user_registered');
 |
 | 3) Статический метод класса строкой 'Класс::метод':
 |    Events::listen('user.registered', \AC\app\services\EventDemoListener::class . '::handle');
 |
 | 4) Метод объекта [объект, 'метод']:
 |    Events::listen('user.registered', [new SomeService(), 'handle']);
 |
 | Приоритет: больше — раньше. Одноразовый слушатель: Events::once(...).
 */

// Пример 1: замыкание — пишет в лог при «бронировании» (триггер см. ниже в коде).
Events::listen('reservation.created', function (\AC\core\system\pattern\event\Event $event): void {
  error_log('[events] создана бронь: ' . json_encode($event->data, JSON_UNESCAPED_UNICODE));
}, priority: 10);

// Пример 2: обычная функция-слушатель.
function on_reservation_created(\AC\core\system\pattern\event\Event $event): void
{
  // $event->data можно менять — изменения увидят следующие слушатели.
  if (is_array($event->data)) {
    $event->data['handled_by'] = 'on_reservation_created';
  }
}

Events::listen('reservation.created', 'on_reservation_created');

// Пример 3: статический метод класса.
Events::listen('reservation.created', \AC\app\services\EventDemoListener::class . '::handle');

// Пример 4: wildcard — сработает на reservation.created, reservation.canceled и т.д.
Events::listen('reservation.*', function (\AC\core\system\pattern\event\Event $event): void {
  error_log('[events] любое событие группы reservation: ' . $event->name);
});

// Пример 5: одноразовый слушатель стартовой загрузки системы.
Events::once('system.booted', fn(\AC\core\system\pattern\event\Event $e) =>
  error_log('[events] система стартовала'));
