<?php

namespace AC\core\modules\mailing\entities\traits\set;

use AC\app\entities\traits\fields\HasAlias;
use AC\app\entities\traits\fields\HasContent;
use AC\app\entities\traits\fields\HasLanguage;
use AC\app\entities\traits\fields\HasTitle;
use AC\core\modules\mailing\entities\traits\fields\HasModeTemplate;
use AC\core\modules\mailing\entities\traits\fields\HasSubject;

trait HasLetterTemplate
{
  use HasModeTemplate;
  use HasAlias;
  use HasTitle;
  use HasSubject;
  use HasContent;
  use HasLanguage;
  
  /**
   * Построить DTO из массива данных.
   * @param array $data
   * @return self
   */
  public static function fromArray(array $data = []): self
  {
    $dto = new self();
    
    $dto->setMode($data['mode']);
    $dto->setAlias((string)($data['alias'] ?? ''));
    $dto->setTitle($data['title'] ?? null);
    $dto->setSubject($data['subject'] ?? null);
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
      'mode'     => $this->getMode(),
      'alias'    => $this->getAlias() ?? '',
      'title'    => $this->getTitle(),
      'subject'  => $this->getSubject(),
      'content'  => $this->getContent(),
      'language' => $this->getLanguage(),
    ];
  }
}