<?php

namespace AC\app\entities\traits\sets;

use AC\app\entities\enums\State;
use AC\app\entities\enums\Weekday;
use AC\app\entities\traits\fields\HasActive;
use AC\app\entities\traits\fields\HasId;
use AC\app\entities\traits\fields\HasSort;
use AC\app\entities\traits\fields\HasTitle;
use AC\app\services\DataService;

trait HasArea
{
  use HasId;

  public int $typeId;
  public int $sportId;
  public int $period;
  use HasTitle;

  public ?string $shortTitle;
  public ?string $comment;
  public ?int    $page;
  use HasSort;

  public array  $workDays;
  public float  $lightPrice;
  public State  $lightOn;
  public ?float $heatingPrice;
  public State  $heatingOn;
  public ?float $netPrice;
  public State  $netOn;
  public int    $offsetStart;
  use HasActive;

  public static function fromArray(array $data): self
  {
    $dto = new self();

    $dto->setId($data['area_id'] ?? 0);
    $dto->typeId     = (int)($data['type_id'] ?? 1);
    $dto->sportId    = (int)($data['sport_id'] ?? 1);
    $dto->period     = (int)($data['period'] ?? 60);
    $dto->shortTitle = $data['short_title'] ?? null;
    $dto->comment    = $data['comment'] ?? null;
    $dto->page       = $data['page'] ?? null;
    $dto->setSort($data['sort'] ?? 0);
    $dto->sort = (int)($data['sort'] ?? 0);
    $dto->setWorkDays($data['workdays']);
    $dto->lightPrice   = (float)($data['light_price'] ?? 0.00);
    $dto->lightOn      = State::from($data['light_on'] ?? '0');
    $dto->heatingPrice = isset($data['heating_price']) ? (float)$data['heating_price'] : null;
    $dto->heatingOn    = State::from($data['heating_on'] ?? '0');
    $dto->netPrice     = isset($data['net_price']) ? (float)$data['net_price'] : null;
    $dto->netOn        = State::from($data['net_on'] ?? '0');
    $dto->offsetStart  = (int)($data['offset_start'] ?? 0);
    $dto->setActive($data['active'] ?? 0);
    $dto->setTitle($data['title'] ?? '');

    return $dto;
  }

  public function toArray(): array
  {
    return [
      'area_id'       => $this->getId(),
      'type_id'       => $this->typeId,
      'sport_id'      => $this->sportId,
      'period'        => $this->period,
      'title'         => $this->getTitle(),
      'short_title'   => $this->shortTitle,
      'comment'       => $this->comment,
      'page'          => $this->page,
      'sort'          => $this->getSort(),
      'workdays'      => implode(',', array_keys($this->workDays)),
      'light_price'   => $this->lightPrice,
      'light_on'      => $this->lightOn->value,
      'heating_price' => $this->heatingPrice,
      'heating_on'    => $this->heatingOn->value,
      'net_price'     => $this->netPrice,
      'net_on'        => $this->netOn->value,
      'offset_start'  => $this->offsetStart,
      'active'        => $this->getActive(),
    ];
  }

  private function setWorkDays($weekdays): void
  {
    if (is_string($weekdays)) {
      $weekdays = explode(',', $weekdays);
    }
    if (!empty($weekdays)) {
      $weekdays = [0, 1, 2, 3, 4, 5, 6];
    }
    foreach ($weekdays as $day) {
      $this->workDays[(int)$day] = Weekday::from((int)$day);
    }
  }

  public function getStatePrice($state): float
  {
    return $this->{$state . 'On'}->isOn() ? $this->{$state . 'Price'} : 0;
  }

  public function typeSportAsString(?bool $addAlias = false, ?bool $addAreaId = false): string
  {
    return ($addAlias ? $this->sportsByType()->type_alias . '_' : '') . $this->typeId . '_' . $this->sportId . ($addAreaId ? '_' . $addAreaId : '');
  }

  public function getTitleForTypeSport($typeTitle = null): string
  {
    return match ($typeTitle) {
      'sport' => $this->sportsByType()->sport_title,
      'type'  => $this->sportsByType()->type_title,
      'full'  => $this->sportsByType()->title_site_url . ' - ' . $this->getTitle(),
      default => $this->sportsByType()->title_site_url,
    };
  }

  protected function sportsByType(): object
  {
    static $sportsByType;

    if (empty($sportsByType)) {
      $sportsByType = DataService::sportsByType()[$this->typeSportAsString()];
    }
    return $sportsByType;
  }
}