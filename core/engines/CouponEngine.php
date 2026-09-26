<?php
namespace AC\core\engines;

useClass('engines\coupon');

class CouponEngine extends \coupon
{
  public function __construct()
  {
    $this->initialize();
  }
}