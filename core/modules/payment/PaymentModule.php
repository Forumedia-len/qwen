<?php

namespace AC\core\modules\payment;


use AC\core\system\module\BaseModule;

class PaymentModule extends BaseModule
{

  public function setSubName($subName = null): void
  {
    $subName = $subName ? : ($this->useRouting ? $this->segments[0] : null);
    parent::setSubName($subName);
    if ($this->subName && $this->useRouting && $this->segments[0] == $this->subName) {
      array_shift($this->segments);
    }
  }

}