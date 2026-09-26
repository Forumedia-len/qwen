<?php

namespace AC\app\entities\traits\fields;

trait HasContent
{
  public ?string $content;
  
  public function getContent(): ?string { return $this->content ?? ''; }
  
  public function setContent(?string $content): void { $this->content = $content ?? ''; }
}