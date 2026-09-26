<?php

namespace AC\core\system\pattern;

use AC\core\system\pattern\event\Event;
use InvalidArgumentException;

/**
 * Events
 *
 * Паттерн "событийная шина" (Observer / Event Dispatcher) для системы.
 *
 * Слушатели регистрируются в отдельном файле `app/uses/events.php`
 * (подключается через `uses('events')` в bootstrap), триггеры рассредоточены
 * по коду и вызываются статическим методом Events::trigger().
 *
 * Возможности:
 * - слушатель — замыкание, [класс, метод], 'Класс::метод' или имя глобальной функции;
 * - приоритет исполнения (больше — раньше), по умолчанию 0;
 * - одноразовые слушатели (`once`) и снятие слушателя по идентификатору;
 * - остановка цепочки (`stopPropagation()`) и передача изменённых данных дальше
 *   через `Event::$data`;
 * - отложенные события до регистрации слушателей (`Events::init()`);
 * - wildcard-события: `reservation.*` реагирует на `reservation.created`.
 */
class Events extends Locator
{
  /** Имя файла подключения слушателей относительно папки констант (`app/uses/`). */
  public const LISTENERS_FILE = 'events';

  /** @var array<string, array<int, array{id: int, callback: callable, priority: int, once: bool}>> */
  protected static array $listeners = [];

  /** @var array<int, array{name: string, event: Event}> Отложенные события, триггернутые до инициализации. */
  protected static array $queued = [];

  /** @var int Счётчик идентификаторов слушателей. */
  protected static int $sequence = 0;

  /** @var bool Был ли подключён файл слушателей. */
  protected static bool $initialized = false;

  /** @var bool Идёт ли сейчас подключение файла слушателей (защита от рекурсии). */
  protected static bool $loading = false;

  /**
   * Зарегистрировать слушателя события.
   *
   * @param string              $event    Имя события, поддерживает wildcard (`reservation.*`)
   * @param callable|string|array $callback Слушатель: замыкание, 'Класс::метод', [объект|класс, метод] или функция
   * @param int                 $priority Больше — раньше исполняется, по умолчанию 0
   * @param bool                $once     Снять слушателя после первого срабатывания
   *
   * @return int Идентификатор слушателя для последующего снятия
   *
   * @throws InvalidArgumentException Если передан не вызываемый слушатель
   */
  public static function listen(string $event, $callback, int $priority = 0, bool $once = false): int
  {
    if (!is_string($callback) && !is_callable($callback)) {
      throw new InvalidArgumentException("Listener for event '{$event}' is not callable.");
    }

    // ленивое разрешение строковых слушателей ('Класс::метод' или имя функции)
    if (is_string($callback) && str_contains($callback, '::')) {
      $callback = explode('::', $callback);
    }

    // при регистрации из файла слушателей повторное подключение не требуется
    if (!static::$loading) {
      static::initialize();
    }

    $id = ++static::$sequence;
    static::$listeners[$event][$id] = [
      'id'       => $id,
      'callback' => $callback,
      'priority' => $priority,
      'once'     => $once,
    ];

    return $id;
  }

  /**
   * Зарегистрировать одноразового слушателя события.
   *
   * @param string              $event    Имя события
   * @param callable|string|array $callback Слушатель
   * @param int                 $priority Приоритет исполнения
   *
   * @return int Идентификатор слушателя
   */
  public static function once(string $event, $callback, int $priority = 0): int
  {
    return static::listen($event, $callback, $priority, true);
  }

  /**
   * Снять слушателя по идентификатору либо всех слушателей события.
   *
   * @param string   $event Имя события
   * @param int|null $id    Идентификатор слушателя; null — снять всех слушателей события
   */
  public static function remove(string $event, ?int $id = null): void
  {
    if ($id === null) {
      unset(static::$listeners[$event]);

      return;
    }

    unset(static::$listeners[$event][$id]);
    if (empty(static::$listeners[$event])) {
      unset(static::$listeners[$event]);
    }
  }

  /**
   * Есть ли слушатели у события (включая wildcard).
   *
   * @param string $event Имя события
   */
  public static function hasListeners(string $event): bool
  {
    static::initialize();

    return count(static::listenersFor($event)) > 0;
  }

  /**
   * Триггернуть событие: передать данные всем слушателям по убыванию приоритета.
   *
   * Данные доступны в `$event->data` и сохраняют изменения слушателей при
   * передаче следующему. Цепочку можно остановить через `$event->stopPropagation()`.
   *
   * До подключения файла слушателей события накапливаются в очереди
   * и исполняются автоматически при первом подключении (см. init()).
   *
   * @param string              $event Имя события
   * @param mixed               $data  Передаваемые слушателям данные
   * @param object|string|null  $padded Дополнительный контекст (например, объект инициатора)
   *
   * @return Event Экземпляр события с итоговыми данными и флагом остановки
   */
  public static function trigger(string $event, $data = null, $padded = null): Event
  {
    $instance = new Event($event, $data, $padded);

    if (!static::$initialized) {
      static::initialize();

      // Событие триггернуто до подключения слушателей — оставляем его в очереди
      if (!static::$initialized) {
        static::$queued[] = ['name' => $event, 'event' => $instance];

        return $instance;
      }
    }

    foreach (static::listenersFor($event) as $listener) {
      if ($instance->isStopped()) {
        break;
      }

      call_user_func($listener['callback'], $instance);

      if ($listener['once']) {
        static::remove($event, $listener['id']);
      }
    }

    return $instance;
  }

  /**
   * Подключить файл слушателей `app/uses/events.php` (или `.loc.php`).
   * Метод идемпотентен; безопасен для повторных вызовов.
   */
  public static function initialize(): void
  {
    if (static::$initialized) {
      return;
    }

    static::$initialized = true;
    static::$loading     = true;

    try {
      // подключаем файл слушателей напрямую по путям: локальная версия имеет приоритет
      foreach (['.loc', ''] as $filePrefix) {
        $file = pathAs(SHARED_PATH . paths()->constantDir . static::LISTENERS_FILE . $filePrefix . '.php');
        if (is_file($file)) {
          require_once $file;
        }
      }
    } finally {
      static::$loading = false;
    }

    static::init();
  }

  /**
   * Принудительно исполнить отложенные события (слушатели уже зарегистрированы).
   * Вызывается автоматически из initialize().
   */
  public static function init(): void
  {
    if (empty(static::$queued)) {
      return;
    }

    $queued = static::$queued;
    static::$queued = [];

    foreach ($queued as $item) {
      foreach (static::listenersFor($item['name']) as $listener) {
        if ($item['event']->isStopped()) {
          break;
        }

        call_user_func($listener['callback'], $item['event']);

        if ($listener['once']) {
          static::remove($item['name'], $listener['id']);
        }
      }
    }
  }

  /**
   * Полностью очистить состояние шины (слушатели, очередь, счётчики).
   * Используется тестами и при сбросе окружения.
   */
  public static function reset(): void
  {
    static::$listeners   = [];
    static::$removed     = [];
    static::$queued      = [];
    static::$sequence    = 0;
    static::$initialized = false;
  }

  /**
   * Собрать подходящие слушатели события: точное имя плюс все wildcard-маски.
   *
   * @return array<int, array{id: int, callback: callable, priority: int, once: bool}>
   */
  protected static function listenersFor(string $event): array
  {
    $matched = static::$listeners[$event] ?? [];

    foreach (static::wildcardEvents($event) as $mask) {
      foreach (static::$listeners[$mask] ?? [] as $id => $listener) {
        $matched[$id] = $listener;
      }
    }

    // сортировка стабильна: при равном приоритете сохраняется порядок регистрации
    uasort($matched, static fn(array $a, array $b): int => $b['priority'] <=> $a['priority']);

    return $matched;
  }

  /**
   * Wildcard-маски события: `reservation.created` -> [`reservation.*`, `*`].
   *
   * @return string[]
   */
  protected static function wildcardEvents(string $event): array
  {
    if (!str_contains($event, '.')) {
      return [];
    }

    $parts = explode('.', $event);
    $masks = [];
    while (count($parts) > 1) {
      array_pop($parts);
      $masks[] = implode('.', $parts) . '.*';
    }
    $masks[] = '*';

    return $masks;
  }
}
