<?php

namespace AC\app\services;

use AC\core\system\pattern\event\Event;

/**
 * EventDemoListener
 *
 * Пример обработчика событий в виде класса. Слушатели можно регистрировать
 * не только замыканиями, но и ссылками на статические методы вида
 * 'Класс::метод' — как это сделано в app/uses/events.php.
 */
class EventDemoListener
{
  /**
   * Статический обработчик — вызывается через 'EventDemoListener::handle'.
   * Пишет информацию о событии в error_log (в веб-версии — в лог сервера).
   */
  public static function handle(Event $event): void
  {
    $who = is_object($event->padded) ? get_class($event->padded) : (string) $event->padded;

    error_log(sprintf(
      '[events] %s сработало (data: %s, padded: %s)',
      $event->name,
      json_encode($event->data, JSON_UNESCAPED_UNICODE),
      $who !== '' ? $who : '—'
    ));
  }
}
