<?php

namespace AC\core\modules\discounts\models;

use AC\core\modules\discounts\tables\DiscountsTable;

class DiscountsModel extends DiscountsTable
{
  protected $baseGetFunction = 'getDiscount';
  protected $primary_key = 'discount_id';
  protected $baseEngine = 'DiscountsEngine';
}