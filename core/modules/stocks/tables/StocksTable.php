<?php

namespace AC\core\modules\stocks\tables;

use AC\core\modules\stocks\entities\entity\StocksGroup;
use AC\core\system\model\BaseModel;

class StocksTable extends BaseModel
{
  public $stock_id;
  public $type_sport;
  public $sort;
  public $code;
  public $title;
  public $rate;
  public $for_all;
  public $only_once;
  public $dimension;
  /**
   * @var null|int|StocksGroup
   */
  public $group;
  public $preferences;

}