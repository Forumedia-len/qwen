<?php

namespace AC\core\system\pattern\event;

/**
 * Event
 *
 * Контекст одного срабатывания события: имя, данные, дополнительный объект
 * и флаг остановки цепочки слушателей. Создается экземпляром Events (см.
 * `AC\core\system\pattern\Events::trigger()`) и передается каждому слушателю.
 */
class Event
{
  /** @var string Имя события */
  public readonly string $name;

  /** @var mixed Данные события; изменения видны следующим слушателям */
  public mixed $data;

  /** @var object|string|null Дополнительный контекст (инициатор события) */
  public mixed $padded;

  /** @var bool Флаг остановки цепочки слушателей */
  protected bool $stopped = false;

  /**
   * @param string             $name   Имя события
   * @param mixed              $data   Передаваемые данные
   * @param object|string|null $padded Дополнительный контекст (например, объект инициатора)
   */
  public function __construct(string $name, mixed $data = null, mixed $padded = null)
  {
    $this->name   = $name;
    $this->data   = $data;
    $this->padded = $padded;
  }

  /** Остановить передачу события остальным слушателям. */
  public function stopPropagation(): void
  {
    $this->stopped = true;
  }

  /** Возобновить передачу события остальным слушателям. */
  public function resumePropagation(): void
  {
    $this->stopped = false;
  }

  /** Остановлена ли цепочка слушателей. */
  public function isStopped(): bool
  {
    return $this->stopped;
  }
}
