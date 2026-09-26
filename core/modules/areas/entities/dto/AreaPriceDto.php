<?php

namespace AC\core\modules\areas\entities\dto;

use AC\app\entities\enums\Weekday;
use AC\core\system\helpers\NumberHelper;
use DateTime;
use DateTimeInterface;
use Exception;

/**
 * DTO для информации о цене зоны.
 *
 * @package AC\core\modules\areas\entities\dto
 */
class AreaPriceDto
{  /**
   * Идентификатор сезона.
   *
   * @var int
   */
  public int               $season;
  /**
   * День недели для этой цены.
   *
   * @var Weekday
   */
  public Weekday           $weekday;
  /**
   * Время начала для этой цены.
   *
   * @var DateTimeInterface
   */
  public DateTimeInterface $start;
  /**
   * Сумма цены.
   *
   * @var float
   */
  public float             $amount;
  
  /**
   * Создает объект из массива данных.
   *
   * @param array $data Массив с данными
   * @return self Созданный объект DTO
   * @throws Exception
   */
  public static function fromArray(array $data): self
  {
    $dto         = new self();
    $dto->season = (int) ($data['season'] ?? 1);
    $dto->weekday = Weekday::from($data['weekday'] ?? 0);
    $dto->start = new DateTime($data['start'] ?? '00:00:00');
    $dto->amount = NumberHelper::float($data['price'] ?? 0);
    
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
      'price' => $this->amount,
      'season' => $this->season,
      'weekday' => $this->weekday->value,
      'start' => $this->start->format('H:i:s'),
    ];
  }
  
  /**
   * Возвращает время начала в виде строки.
   *
   * @param bool $seconds Флаг включения секунд
   * @return string Время в формате H:i или H:i:s
   */
  public function getStart(bool $seconds = false): string
  {
    return $seconds ? $this->start->format('H:i:s') : $this->start->format('H:i');
  }
}