<?php

namespace AC\core\modules\areas\tables;

use AC\core\system\model\BaseModel;

class AreasTable extends BaseModel
{
  public $area_id;
  public $type_id;
  public $sport_id;
  public $period;
  public $title;
  public $short_title;
  public $comment;
  public $workdays;
  public $light_on;
  public $light_price;
  public $heating_on;
  public $heating_price;
  public $net_on;
  public $net_price;
  public $type_title;
  public $sport_title;
  public $archived_at;

  /** Можно бронировать площадку через пк версию
   * @var
   */
  public $online_reservation = 1;
  public $tableName          = 'areas';
}
