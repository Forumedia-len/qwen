<?php

/**
 * DTO для рабочих часов зоны.
 *
 * @package AC\core\modules\areas\entities\dto
 */

namespace AC\core\modules\areas\entities\dto;

use AC\app\entities\enums\Weekday;
use DateTime;
use DateTimeInterface;
use Exception;

/**
 * Класс DTO для представления рабочих часов зоны по дням недели.
 */
class AreaWorkingHourDto
{
  /**
   * День недели.
   *
   * @var Weekday
   */
  public Weekday $weekday;
  /**
   * Время начала рабочего периода.
   *
   * @var DateTimeInterface
   */
  public DateTimeInterface $start;
  /**
   * Время окончания рабочего периода.
   *
   * @var DateTimeInterface
   */
  public DateTimeInterface $finish;
  
  public array $workTime = [];
  
  /**
   * Создает объект из массива данных.
   *
   * @param array $data Массив с данными
   * @return self Созданный объект DTO
   * @throws Exception При ошибках создания объекта DateTime
   */
  public static function fromArray(array $data): self
  {
    $dto          = new self();
    $dto->weekday = Weekday::from($data['weekday'] ?? 0);
    $dto->start   = new DateTime($data['start'] ?? '00:00:00');
    $dto->finish  = new DateTime($data['finish'] ?? '00:00:00');
    
    return $dto;
  }
  
  /**
   * Преобразует объект в массив.
   *
   * @return array Массив с данными объекта
   */
  public function toArray(): array
  {
    return [
      'weekday' => $this->weekday->value,
      'start'   => $this->start->format('H:i:s'),
      'finish'  => $this->finish->format('H:i:s'),
    ];
  }
  
  /**
   * Возвращает время начала в виде строки.
   *
   * @param bool $seconds Флаг включения секунд
   * @return string Время в формате H:i или H:i:s
   */
  public function getStart($seconds = false): string
  {
    return $seconds ? $this->start->format('H:i:s') : $this->start->format('H:i');
  }
  
  /**
   * Возвращает время окончания в виде строки.
   *
   * @param bool $seconds Флаг включения секунд
   * @return string Время в формате H:i или H:i:s
   */
  public function getFinish($seconds = false): string
  {
    return $seconds ? $this->finish->format('H:i:s') : $this->finish->format('H:i');
  }
  
  public function renderWorkTime(int $period): self
  {
    $current = clone $this->start;
    while ($current < $this->finish) {
      $this->workTime[$current->format('H:i')] = $current->modify("+$period minutes")->format('H:i');
    }
    
    return $this;
  }
  
  public function workTime(): array
  {
    return $this->workTime;
  }
}