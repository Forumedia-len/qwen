<?php

namespace AC\core\modules\reservations\entities;

use AC\app\entities\enums\Encash;

final class PaymentMethodsState
{
  /** @param array<string, bool> $visible
   *  @param array<string, bool> $selectable
   */
  public function __construct(
    private array $visible,
    private array $selectable,
    private ?string $selected
  ) {
  }

  public function isVisible(Encash|string $method): bool
  {
    return $this->visible[$this->alias($method)] ?? false;
  }

  public function isSelectable(Encash|string $method): bool
  {
    return $this->selectable[$this->alias($method)] ?? false;
  }

  public function isSelected(Encash|string $method): bool
  {
    return $this->selected === $this->alias($method);
  }

  public function checked(Encash|string $method): string
  {
    return $this->isSelected($method) ? 'checked="checked"' : '';
  }

  private function alias(Encash|string $method): string
  {
    return $method instanceof Encash ? $method->shortAlias() : $method;
  }
}
