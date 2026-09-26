<?php

namespace AC\core\modules\text\entities\dto;

use AC\app\entities\traits\fields\HasAlias;
use AC\app\entities\traits\fields\HasContent;
use AC\app\entities\traits\fields\HasLanguage;

/**
 * DTO с данными текстового блока (alias, контент, язык).
 */
class TextDto
{
  use HasAlias;
  use HasContent;
  use HasLanguage;
  
  /**
   * Построить DTO из массива данных.
   * @param array $data
   * @return TextDto
   */
  public static function fromArray(array $data = []): self
  {
    $dto = new self();
    
    $dto->setAlias((string)($data['alias'] ?? ''));
    $dto->setContent($data['content'] ?? null);
    $dto->setLanguage($data['language'] ?? null);
    
    return $dto;
  }
  
  /**
   * Представить DTO в массиве (для форм/шаблонов).
   *
   * @return array
   */
  public function toArray(): array
  {
    return [
      'alias'    => $this->getAlias() ?? '',
      'content'  => $this->getContent(),
      'language' => $this->getLanguage(),
    ];
  }
}


