<?php

namespace AC\core\modules\config\entities\dto;

use AC\app\entities\enums\State;
use AC\app\entities\traits;
use AC\core\system\helpers\NumberHelper;

class NdsDto
{
  public ?int   $nds_id;
  public float  $rate;
  public string $comment;
  public State  $default;
  
  /**
   * Создание DTO из ассоциативного массива
   *
   * @param array $data
   * @return self
   */
  public static function fromArray(array $data): self
  {
    $dto          = new self();
    $dto->nds_id  = $data['nds_id'] ?? null;
    $dto->rate    = NumberHelper::float($data['rate'] ?? 0);
    $dto->comment = $data['comment'] ?? '';
    $dto->default = State::from($data['set_default'] ?? 0);
    
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
      'nds_id'      => $this->nds_id,
      'rate'        => $this->rate,
      'comment'     => $this->comment,
      'set_default' => $this->default->value
    ];
  }
  
  public function label(): string
  {
    return $this->rate . ' %';
  }
}