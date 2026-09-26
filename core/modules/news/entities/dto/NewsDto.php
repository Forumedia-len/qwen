<?php

namespace AC\core\modules\news\entities\dto;

use AC\app\entities\traits\fields\HasContent;
use AC\app\entities\traits\fields\HasDate;
use AC\app\entities\traits\fields\HasId;
use AC\app\entities\traits\fields\HasLanguage;
use AC\app\entities\traits\fields\HasPublished;
use AC\app\entities\traits\fields\HasTitle;
use AC\core\system\entities\dto\Dto;

class NewsDto extends Dto
{
  use HasId, HasDate, HasTitle, HasContent, HasPublished, HasLanguage;
  
  public ?int $main_news_id = null;
  
  /**
   * @inheritDoc
   */
  public static function fromArray(array $data): self
  {
    $dto = new self();
    $dto->setId($data['news_id'] ?: null);
    // Всегда устанавливаем дату, даже если она null (setDate обработает это)
    $dto->setDateTime($data['date'] ?? date('Y-m-d H:i:s'));
    $dto->setTitle($data['title'] ?? '');
    $dto->setContent($data['content'] ?? '');
    $dto->setPublished($data['publish'] ?? 0);
    $dto->setLanguage($data['language'] ?? config('lang')->getDefault());
    $dto->setMainNewsId($data['main_news_id'] ?? null);
    
    return $dto;
  }
  
  /**
   * @inheritDoc
   */
  public function toArray(): array
  {
    return [
      'news_id'      => $this->getId(),
      'date'         => $this->getDateTime(),
      'title'        => $this->getTitle(),
      'content'      => $this->getContent(),
      'publish'      => $this->getPublished(),
      'language'     => $this->getLanguage(),
      'main_news_id' => $this->getMainNewsId(),
    ];
  }
  
  public function getMainNewsId(): ?int
  {
    return $this->main_news_id ?? null;
  }
  
  public function setMainNewsId(?int $main_news_id): void
  {
    $this->main_news_id = $main_news_id ?: null;
  }
}