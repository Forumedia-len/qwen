<?php

namespace AC\core\modules\reservations\entities\dto;

use AC\app\entities\traits\fields\HasAlias;
use AC\app\entities\traits\fields\HasName;
use AC\app\entities\traits\fields\HasTitle;
use AC\core\system\entities\dto\DtoInterface;

class PriceOptionFormInputDto implements DtoInterface
{
  public string $id;
  use HasName, HasTitle, HasAlias;
  
  public string $typeInput   = 'radio';
  public string $classInput  = 'radio';
  public string $podlogInput = 'podlog';
  public bool   $checked     = false;
  public string $value       = '';
  
  
  public static function fromArray(array $data): self
  {
    $dto = new self();
    
    $dto->id          = $data['id'] ?? '';
    $dto->name        = $data['name'] ?? 'option_id';
    $dto->title       = $data['title'] ?? '';
    $dto->alias       = $data['alias'] ?? 'option';
    $dto->typeInput   = $data['typeInput'] ?? $dto->typeInput;
    $dto->classInput  = $data['classInput'] ?? $dto->classInput;
    $dto->podlogInput = $data['podlogInput'] ?? $dto->podlogInput;
    $dto->checked     = $data['checked'] ?? false;
    $dto->value       = $data['value'] ?? '';
    
    return $dto;
  }
  
  public function toArray(): array
  {
    return [
      'id'          => $this->id,
      'name'        => $this->name,
      'title'       => $this->title,
      'alias'       => $this->alias,
      'typeInput'   => $this->typeInput,
      'classInput'  => $this->classInput,
      'podlogInput' => $this->podlogInput,
      'checked'     => $this->checked,
      'value'       => $this->value,
    ];
  }
}