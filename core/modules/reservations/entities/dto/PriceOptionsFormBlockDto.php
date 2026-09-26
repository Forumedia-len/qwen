<?php

namespace AC\core\modules\reservations\entities\dto;

use AC\app\entities\traits\fields\HasAlias;
use AC\core\system\entities\dto\DtoInterface;

class PriceOptionsFormBlockDto implements DtoInterface
{
  use HasAlias;
  
  public string $id;
  public string $h3;
  public string $description;
  public string $value = '';
  /**
   * @var PriceOptionFormInputDto[]
   */
  public array $priceOptions = [];
  
  public static function fromArray(array $data): self
  {
    $dto = new self();
    
    $dto->id           = $data['id'] ?? '';
    $dto->h3           = $data['h3'] ?? '';
    $dto->description  = $data['description'] ?? '';
    $dto->alias        = $data['alias'] ?? '';
    $dto->value        = $data['value'] ?? '';
    $dto->priceOptions = array_map(static fn(array $priceOption) => PriceOptionFormInputDto::fromArray($priceOption), $data['priceOptions'] ?? []);
    
    return $dto;
  }
  
  public function toArray(): array
  {
    return [
      'id'           => $this->id,
      'h3'           => $this->h3,
      'description'  => $this->description,
      'alias'        => $this->alias,
      'value'        => $this->value,
      'priceOptions' => array_map(static fn(PriceOptionFormInputDto $priceOption) => $priceOption->toArray(), $this->priceOptions),
    ];
  }
}