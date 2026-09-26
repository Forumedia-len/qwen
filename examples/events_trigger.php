<?php

/*
 | -------------------------------------------------------------------------
 | ПРИМЕР: как вставлять триггеры событий в код
 | -------------------------------------------------------------------------
 |
 | Триггер — это одна строка в том месте кода, где «произошло» событие
 | (сохранили бронь, зарегистрировали пользователя, оплатили счёт и т.п.).
 | Слушатели при этом ничего не знают о коде-инициаторе: он просто сообщает
 | шине «что случилось», а app/uses/events.php решает, кто отреагирует.
 |
 | Синтаксис:
 |   Events::trigger(string $event, mixed $data = null, object|string|null $padded = null): Event
 |
 |   $event  — имя события, точкой разделяются группы: 'reservation.created';
 |   $data   — любые данные для слушателей (массив, объект, скаляр);
 |             слушатели могут менять $e->data — изменения пойдут дальше по цепочке;
 |   $padded — дополнительный контекст, например объект-инициатор ($this).
 |
 | Возвращается объект Event, поэтому можно проверить итог:
 |   $res = Events::trigger('reservation.created', $data, $this);
 |   if ($res->isStopped()) { ... цепочку остановил какой-то слушатель ... }
 |   $finalData = $res->data; // возможно, изменённый слушателями массив
 */

use AC\core\system\pattern\Events;

/**
 * Демонстрационная функция: «сохраняет бронь» и триггерит событие.
 * В реальном коде вместо этой функции вызов Events::trigger() размещается
 * прямо в методе сервиса/контроллера сразу после нужного действия, например:
 *
 *   class ReservationService
 *   {
 *     public function save(array $data): void
 *     {
 *       // ... INSERT в БД ...
 *       \AC\core\system\pattern\Events::trigger('reservation.created', $data, $this);
 *     }
 *   }
 */
function demo_create_reservation(array $reservation): array
{
  // ... здесь реальное сохранение брони в БД ...

  // ТРИГГЕР: сообщаем системе, что бронь создана.
  $event = Events::trigger('reservation.created', [
    'id'     => 123,
    'client' => 'Ivanov',
    'date'   => '2026-09-26',
  ], __FUNCTION__);

  // Слушатели могли изменить/дополнить данные — забираем итоговый вариант.
  return $event->data;
}

// Прогон демонстрации: php examples/events_trigger.php
require_once __DIR__ . '/../core/system/pattern/Locator.php';
require_once __DIR__ . '/../core/system/pattern/event/Event.php';
require_once __DIR__ . '/../core/system/pattern/Events.php';
// В реальном проекте классы берёт автозагрузчик; в демо подключаем вручную.
require_once __DIR__ . '/../app/services/EventDemoListener.php';

// Заглушки констант/функций, которые normally предоставляет bootstrap.
if (!defined('SHARED_PATH')) {
  define('SHARED_PATH', dirname(__DIR__));
}
if (!function_exists('pathAs')) {
  function pathAs(string $path): string
  {
    return $path;
  }
}
if (!function_exists('paths')) {
  function paths(): object
  {
    return new class {
      public string $constantDir = '/app/uses/';
    };
  }
}

// При первом trigger() шина сама подключит app/uses/events.php со слушателями.
$result = demo_create_reservation(['id' => 123]);

echo "Итоговые данные после работы слушателей:\n";
print_r($result);
