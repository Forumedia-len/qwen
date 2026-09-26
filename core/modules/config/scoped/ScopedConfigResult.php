<?php

namespace AC\core\modules\config\scoped;

use Service;

/**
 * Результат чтения из источника БД (L2).
 */
final class ScopedConfigResult
{
  public function __construct(
    public readonly mixed $value,
    public readonly bool $found,
    public readonly ?string $resolvedType = null,
    public readonly ?string $resolvedAlias = null,
    public readonly ?string $source = null,
  ) {}

  /** Пустой результат: значение в storage не найдено. */
  public static function notFound(): self
  {
    return new self(null, false);
  }

  /** Успешное чтение с метаданными resolved type/alias и имени источника. */
  public static function fromSource(
    mixed $value,
    string $resolvedType,
    string $resolvedAlias,
    string $source,
  ): self {
    return new self($value, true, $resolvedType, $resolvedAlias, $source);
  }

  /** Приведение value к int для L4/L5 (found=false → default). */
  public function asInt(?int $default = null): ?int
  {
    if (!$this->found || $this->value === null) {
      return $default;
    }

    return (int)$this->value;
  }

  /** Приведение value к bool через cast boolish (start/stop и т.д.). */
  public function asBool(?bool $default = null): ?bool
  {
    if (!$this->found || $this->value === null) {
      return $default;
    }

    return (bool)Service::cast('boolish')->get($this->value, ['strictBool' => false]);
  }
}
