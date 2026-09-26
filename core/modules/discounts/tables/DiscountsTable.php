<?php

namespace AC\core\modules\discounts\tables;

use AC\core\system\model\BaseModel;

class DiscountsTable extends BaseModel
{
  public $discount_id;
  public $type;
  public $dimension;
  public $title;
  public $retail;
  public $ticket;
  public $comment;

}