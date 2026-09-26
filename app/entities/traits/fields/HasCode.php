<?php

namespace AC\app\entities\traits\fields;

trait HasCode
{
  public string $code = '';
  
  public function getCode(): string
  {
    return $this->code;
  }
  
  public function setCode(string $code): void
  {
    $this->code = $code;
  }
}