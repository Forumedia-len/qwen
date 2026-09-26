<?php

namespace AC\core\modules\config\tables;

use AC\core\system\model\BaseModel;

class ConfigNdsTable extends BaseModel
{
  public $nds_id;
  public $rate;
  public $comment;
  public $set_default;

  protected $tableName = 'config_nds';

}