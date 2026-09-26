<?php

declare(strict_types=1);

namespace AC\core\modules\tickets\entities\dto;

/** Результат расчёта пересечений добавляемого периода абонемента с блокировками. */
final readonly class TicketBlockIntersectionResult
{
  /**
   * @param array<int, array{start: string, finish: string}> $periods
   * @param array<int, string>                               $fullyBlockedDates
   * @param array<string, array<int, string>>                $partiallyBlockedSlots
   */
  public function __construct(
    private array $periods,
    private array $fullyBlockedDates,
    private array $partiallyBlockedSlots,
  ) {
  }

  /**
   * @return array<int, array{start: string, finish: string}>
   */
  public function getPeriods(): array
  {
    return $this->periods;
  }

  /**
   * @return array<int, string>
   */
  public function getFullyBlockedDates(): array
  {
    return $this->fullyBlockedDates;
  }

  /**
   * @return array<string, array<int, string>>
   */
  public function getPartiallyBlockedSlots(): array
  {
    return $this->partiallyBlockedSlots;
  }
}
