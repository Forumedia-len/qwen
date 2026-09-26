<?php

namespace AC\core\modules\mailing\entities\dto;

use AC\core\modules\mailing\entities\traits\set\HasLetterTemplate;

class LetterTemplateViewListHtmlDto
{
  use HasLetterTemplate;
  
  public bool $useRemove = false;
  
  public function isUseRemove(): bool
  {
    return $this->useRemove;
  }
  
  public function setUseRemove(bool $useRemove): void
  {
    $this->useRemove = $useRemove;
  }
}