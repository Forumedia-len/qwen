<?php

namespace AC\core\modules\clients\entities\dto;

class PaymentRowDto
{
  public function __construct(
    public string $date,
    public string $label,
    public string $amount,
    public string $icon = '',
    public string $comment = ''
  ) {
  }
  
}