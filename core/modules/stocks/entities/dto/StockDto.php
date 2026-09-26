<?php

namespace AC\core\modules\stocks\entities\dto;

use AC\core\system\entities\dto\Dto;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\StringHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;

class StockDto extends Dto
{
  public ?int    $stockId        = null;
  public int     $sort           = 0;
  public string  $code           = '';
  public string  $title          = '';
  public float   $rate           = 0.0;
  public ?string $typeSport      = null;
  public bool    $forAll         = false;
  public bool    $duration       = false;
  public ?string $durationStart  = null; // формат: 'Y-m-d'
  public ?string $durationFinish = null; // формат: 'Y-m-d'
  public bool    $onlyOnce       = false;
  public string  $dimension      = '1'; // set: '1', '2'
  public ?int    $group          = null;
  public ?string $preferences    = null;
  
  public array   $durations      = [];
  public array   $conditions     = [];
  
  /**
   * Создание DTO из ассоциативного массива
   *
   * @param array $data
   * @return self
   */
  public static function fromArray(array $data): self
  {
    $dto = new self();
    
    $dto->stockId        = $data['stock_id'] ?? $dto->stockId;
    $dto->sort           = (int)($data['sort'] ?? $dto->sort);
    $dto->code           = StringHelper::shield((string)($data['code'] ?? $dto->code));
    $dto->title          = StringHelper::shield((string)($data['title'] ?? $dto->title));
    $dto->rate           = (float)($data['rate'] ?? $dto->rate);
    $dto->typeSport      = $data['type_sport'] ?? $dto->typeSport;
    $dto->forAll         = (bool)($data['for_all'] ?? $dto->forAll);
    $dto->duration       = (bool)($data['duration'] ?? $dto->duration);
    $dto->durationStart  = $data['duration_start'] ?? $dto->durationStart;
    $dto->durationFinish = $data['duration_finish'] ?? $dto->durationFinish;
    $dto->onlyOnce       = (bool)($data['only_once'] ?? $dto->onlyOnce);
    $dto->dimension      = (string)($data['dimension'] ?? $dto->dimension);
    $dto->group          = isset($data['group']) ? (int)$data['group'] : $dto->group;
    $dto->preferences    = $data['preferences'] ?? $dto->preferences;
    $dto->durations      = $data['durations'] ?? $dto->durations;
    
    self::setConditions($dto);
    
    return $dto;
  }
  
  /**
   * Преобразование DTO в ассоциативный массив
   *
   * @return array
   */
  public function toArray(): array
  {
    return [
      'stock_id'         => $this->stockId,
      'sort'             => $this->sort,
      'code'             => $this->code,
      'title'            => $this->title,
      'rate'             => $this->rate,
      'type_sport'       => $this->typeSport,
      'for_all'          => $this->forAll ? '1' : '0', // enum('0','1') — сохраняем как строку
      'duration'         => $this->duration ? '1' : '0',
      'duration_start'   => $this->durationStart,
      'duration_finish'  => $this->durationFinish,
      'only_once'        => (int)$this->onlyOnce, // tinyint(1)
      'dimension'        => $this->dimension,
      'group'            => $this->group,
      'preferences'      => $this->preferences,
      'durations'        => $this->durations,
      'conditions'       => $this->getConditionToArray(),
    ];
  }
  
  public function amount(): string
  {
    return NumberHelper::format($this->rate, 2, ',', ' ') . ' ' .
      match ($this->dimension) {
        '2'     => '%',
        default => CURR_VALUTE,
      };
  }
  
  public function titleSportByType(): string
  {
    static $sports;
    if (empty($sports)) {
      $sports = ModCommHelper::get('areas', 'areas/relevantSportsByType', [], 'sportsByType', []);
    }
    return StringHelper::shield($sports[$this->typeSport]->title ?? lang('all_choice', 'config_stock'));
  }
  
  private static function setConditions(StockDto $dto): void
  {
    static $groups;
    !empty($groups) || getEngine('stocksGroups')?->getData($groups);
    if (isset($groups[$dto->group]) && $groups[$dto->group]->active) {
      $dto->conditions[$dto->group] = $groups[$dto->group];
    }
  }
  
  private function getConditionToArray(): array
  {
    $result = [];
    foreach ($this->conditions as $condition) {
      $result[] = $condition->toArray();
    }
    
    return $result;
  }
}
