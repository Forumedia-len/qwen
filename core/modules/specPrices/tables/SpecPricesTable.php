<?php

namespace AC\core\modules\specPrices\tables;

use AC\core\system\model\BaseModel;

class SpecPricesTable extends BaseModel
{
  public $sprice_id;
  public $sort;
  public $code;
  public $title;
  public $rate;
  public $for_all;
  public $type_sport;
  public $duration;
  public $duration_start;
  public $duration_finish;
}