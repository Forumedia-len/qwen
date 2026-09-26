<?php

namespace AC\core\modules\discounts\entities\dto;

use AC\app\entities\traits\fields\HasTitle;
use AC\core\modules\discounts\entities\enums\TypeDimension;
use AC\core\modules\discounts\entities\enums\TypeDiscount;
use AC\core\system\helpers\NumberHelper;

class DiscountDto
{
  public ?int          $discount_id;
  public TypeDiscount  $type;
  public TypeDimension $dimension;
  use HasTitle;
  
  public float  $retail;
  public float  $ticket;
  public string $comment;
  
  /**
   * Создание DTO из ассоциативного массива
   *
   * @param array $data
   * @return self
   */
  public static function fromArray(array $data): self
  {
    $dto              = new self();
    $dto->discount_id = $data['discount_id'] ?? null;
    $dto->type        = TypeDiscount::from($data['type'] ?? 0);
    $dto->dimension   = TypeDimension::from($data['dimension'] ?? 2);
    $dto->title       = $data['title'] ?? '';
    $dto->retail      = NumberHelper::float($data['retail'] ?? 0);
    $dto->ticket      = NumberHelper::float($data['ticket'] ?? 0);
    $dto->comment     = $data['comment'] ?? '';
    
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
      'discount_id' => $this->discount_id,
      'type'        => $this->type->value,
      'dimension'   => $this->dimension->value,
      'title'       => $this->title,
      'retail'      => $this->retail,
      'ticket'      => $this->ticket,
      'comment'     => $this->comment,
    ];
  }
  
  public function changePrice(float $price, TypeDiscount $typeDiscount): float
  {
    return $price - $this->getDiscountPrice($price, $typeDiscount);
  }
  
  public function getDiscountPrice(float $price, TypeDiscount $typeDiscount): float
  {
    $discount = match ($typeDiscount) {
      TypeDiscount::Client => $this->retail,
      TypeDiscount::Abo    => $this->ticket,
    };
    
    return match ($this->dimension) {
      TypeDimension::FIXED   => $discount,
      TypeDimension::PERCENT => $price * $discount / 100,
    };
  }
  
  public function label(TypeDiscount $typeDiscount): string
  {
    $discount = match ($typeDiscount) {
      TypeDiscount::Client => $this->retail,
      TypeDiscount::Abo    => $this->ticket,
    };
    
    return match ($this->dimension) {
      TypeDimension::FIXED   => NumberHelper::valute($discount),
      TypeDimension::PERCENT => $discount . ' %',
    };
  }
}