<?php

namespace AC\core\modules\config\scoped;

/**
 * Запрос на чтение scoped-значения из таблицы config (L1).
 *
 * Поле alias — имя параметра в config (не alias типа корта).
 * Поле default — подсказка для L4; gateway не подставляет.
 */
final class ScopedConfigQuery
{
  /**
   * @param array<string, scalar|null|bool> $dimensions clubMark, courtTypeAlias, fallbackTypeAlias, …
   */
  public function __construct(
    public readonly string $prefix,
    public readonly string $alias,
    public readonly ?int $typeId = null,
    public readonly ?int $sportId = null,
    public readonly ?int $areaId = null,
    public readonly ScopedConfigPolicy $policy = ScopedConfigPolicy::DEFAULT,
    public readonly array $dimensions = [],
    public readonly mixed $default = null,
    public readonly bool $nullIfMissing = true,
  ) {}

  /** Значение dimension по ключу (clubMark, courtTypeAlias, …). */
  public function dimension(string $key, mixed $fallback = null): mixed
  {
    return $this->dimensions[$key] ?? $fallback;
  }
}
