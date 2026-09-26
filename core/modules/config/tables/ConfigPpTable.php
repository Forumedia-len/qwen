<?php

namespace AC\core\modules\config\tables;

use AC\core\system\model\BaseModel;

class ConfigPpTable extends BaseModel
{
  public $pp_id;
  public $price_real;
  public $price_account;
  public $sort;

  protected $primary_key = 'pp_id';
}