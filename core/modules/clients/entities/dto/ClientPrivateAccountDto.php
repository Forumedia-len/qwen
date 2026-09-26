<?php

namespace AC\core\modules\clients\entities\dto;

class ClientPrivateAccountDto
{
  public function __construct(public string $title, public array $rows = [])
  {
  }
}

