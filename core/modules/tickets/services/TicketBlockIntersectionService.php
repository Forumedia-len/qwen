<?php

declare(strict_types=1);

namespace AC\core\modules\tickets\services;

use AC\core\modules\tickets\entities\dto\TicketBlockIntersectionResult;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\DateHelper;
use AC\core\system\helpers\TimeHelper;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;

/** Рассчитывает автоматические исключения добавляемого периода по сетке слотов площадки. */
final class TicketBlockIntersectionService
{
  /**
   * Рассчитывает итоговую структуру добавляемого периода с учётом блокировок.
   *
   * Любое ненулевое пересечение блокировки со слотом исключает слот целиком.
   * Это сохраняет сетку площадки и не создаёт частичные игровые интервалы.
   *
   * @param array<int, int|string>                        $weekdays
   * @param array<int|string, string>                     $firstGameDates
   * @param array<int, array{0: string, 1: string}|array> $blocks
   *
   * @throws Exception
   */
  public function calculate(
    string $periodStart,
    string $periodFinish,
    array $weekdays,
    int $space,
    array $firstGameDates,
    string $timeStart,
    string $timeFinish,
    int $slotMinutes,
    array $blocks,
  ): TicketBlockIntersectionResult {
    $start = DateHelper::parseDate($periodStart);
    $finish = DateHelper::parseDate($periodFinish);
    if ($start > $finish || $slotMinutes <= 0 || $space <= 0) {
      throw new InvalidArgumentException('Incorrect parameters of the subscription period.');
    }

    $gameDates = CalendarHelper::getWorkDaysByPeriod(
      $start->format('Y-m-d'),
      $finish->format('Y-m-d'),
      array_map('intval', $weekdays),
      $firstGameDates,
      $space,
      [],
      false
    );
    ksort($gameDates);
    $slots = $this->buildSlots($timeStart, $timeFinish, $slotMinutes);
    $normalizedBlocks = $this->normalizeBlocks($blocks);
    $fullyBlockedDates = [];
    $partiallyBlockedSlots = [];

    foreach (array_keys($gameDates) as $gameDate) {
      $blockedSlots = [];
      foreach ($slots as $slot) {
        $slotStart = new DateTimeImmutable($gameDate . ' ' . $slot['start']);
        $slotFinish = new DateTimeImmutable($gameDate . ' ' . $slot['finish']);
        if ($slot['finishNextDay']) {
          $slotFinish = $slotFinish->modify('+1 day');
        }
        foreach ($normalizedBlocks as $block) {
          if ($block['start'] < $slotFinish && $block['finish'] > $slotStart) {
            $blockedSlots[] = $slot['start'];
            break;
          }
        }
      }

      if ($blockedSlots !== [] && count($blockedSlots) === count($slots)) {
        $fullyBlockedDates[] = $gameDate;
      } elseif ($blockedSlots !== []) {
        $partiallyBlockedSlots[$gameDate] = $blockedSlots;
      }
    }

    return new TicketBlockIntersectionResult(
      $this->buildRemainingPeriods($start, $finish, $gameDates, $fullyBlockedDates),
      $fullyBlockedDates,
      $partiallyBlockedSlots,
    );
  }

  /**
   * @return array<int, array{start: string, finish: string, finishNextDay: bool}>
   */
  private function buildSlots(string $timeStart, string $timeFinish, int $slotMinutes): array
  {
    $slotStarts = TimeHelper::generateArrayTimeInIncrements($timeStart, $timeFinish, $slotMinutes);
    $slots = [];
    foreach ($slotStarts as $slotStart) {
      $slotFinish = TimeHelper::addMinutes2MySQLTime($slotStart, $slotMinutes, true);
      $finishNextDay = $slotFinish === '24:00:00';
      $slots[] = [
        'start' => $slotStart,
        'finish' => $finishNextDay ? '00:00:00' : $slotFinish,
        'finishNextDay' => $finishNextDay,
      ];
    }

    if ($slots === []) {
      throw new InvalidArgumentException('Subscription is not related to gaming slots.');
    }

    return $slots;
  }

  /**
   * @param array<int, array{0: string, 1: string}|array> $blocks
   *
   * @return array<int, array{start: DateTimeImmutable, finish: DateTimeImmutable}>
   * @throws Exception
   */
  private function normalizeBlocks(array $blocks): array
  {
    $normalized = [];
    foreach ($blocks as $block) {
      if (!isset($block[0], $block[1])) {
        continue;
      }
      $blockStart = new DateTimeImmutable((string)$block[0]);
      $blockFinish = new DateTimeImmutable((string)$block[1]);
      if ($blockFinish <= $blockStart) {
        continue;
      }
      $normalized[] = ['start' => $blockStart, 'finish' => $blockFinish];
    }

    return $normalized;
  }

  /**
   * @param array<string, int> $gameDates
   * @param array<int, string> $fullyBlockedDates
   *
   * @return array<int, array{start: string, finish: string}>
   */
  private function buildRemainingPeriods(
    DateTimeImmutable $start,
    DateTimeImmutable $finish,
    array $gameDates,
    array $fullyBlockedDates,
  ): array {
    if ($gameDates === []) {
      return [];
    }

    if ($fullyBlockedDates === []) {
      return [['start' => $start->format('Y-m-d'), 'finish' => $finish->format('Y-m-d')]];
    }

    $availableGameDates = array_diff(array_keys($gameDates), $fullyBlockedDates);
    $excludedRanges = array_map(
      static fn(string $date): array => ['start' => $date, 'finish' => $date],
      $fullyBlockedDates
    );
    $remainingPeriods = DateHelper::subtractRanges(
      $start->format('Y-m-d'),
      $finish->format('Y-m-d'),
      $excludedRanges
    );
    $periods = [];
    foreach ($remainingPeriods as $period) {
      foreach ($availableGameDates as $gameDate) {
        if ($period['start'] <= $gameDate && $gameDate <= $period['finish']) {
          $periods[] = $period;
          break;
        }
      }
    }

    return $periods;
  }
}
