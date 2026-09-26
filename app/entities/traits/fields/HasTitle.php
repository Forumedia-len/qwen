<?php

namespace AC\app\entities\traits\fields;

trait HasTitle
{
  public string $title = '';
  
  public function getTitle(): string
  {
    return $this->title ?? '';
  }
  
  public function setTitle(?string $title): void
  {
    $this->title = $title ?? '';
  }
}