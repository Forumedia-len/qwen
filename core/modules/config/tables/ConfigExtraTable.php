<?php

namespace AC\core\modules\config\tables;

use AC\core\system\model\BaseModel;

class ConfigExtraTable extends BaseModel
{
  public $extra_id;
  public $rate;
  public $club_state;
  public $area_type_id;
  public $area_sport_id;
  public $use_time;

  protected $tableName = 'config_extra';
}