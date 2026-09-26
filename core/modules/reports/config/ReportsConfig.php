<?php

namespace AC\core\modules\reports\config;

class ReportsConfig
{
  public function addWebIoTypesPrice(): bool
  {
    return false;
  }
  
  public function takeIntoAccountFailedPayment(): bool
  {
    return true;
  }
}