<?php

namespace AC\core\system\language\dto;

use AC\app\entities\traits\fields\HasActive;
use AC\app\entities\traits\fields\HasDefault;
use AC\app\entities\traits\fields\HasId;
use AC\app\entities\traits\fields\HasName;
use AC\app\entities\traits\fields\HasTitle;
use AC\core\system\entities\dto\Dto;

class LanguageDto extends Dto
{
  use HasId, HasName, HasTitle, HasActive, HasDefault;
  
  /**
   * Создание DTO из ассоциативного массива
   *
   * @param array $data
   * @return self
   */
  public static function fromArray(array $data): self
  {
    $dto = new self();
    $dto->setId($data['lang_id'] ?? null);
    $dto->setName($data['name'] ?? null);
    $dto->setTitle($data['title'] ?? null);
    $dto->setActive($data['active'] ?? 0);
    $dto->setDefault($data['default'] ?? 0);
    
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
      'lang_id' => $this->getId(),
      'name'    => $this->getName(),
      'title'   => $this->getTitle(),
      'active'  => $this->getActive(),
      'default' => $this->getDefault(),
    ];
  }
  
}