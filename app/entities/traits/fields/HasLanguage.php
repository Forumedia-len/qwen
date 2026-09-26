<?php

namespace AC\app\entities\traits\fields;

trait HasLanguage
{
  public ?string $language;
  
  public function getLanguage(): ?string { return $this->language ?? null; }
  
  public function setLanguage(?string $language): void { $this->language = $language ?? null; }
}