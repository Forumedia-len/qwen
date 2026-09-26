<?php

namespace AC\core\modules\areas\tables;

use AC\core\system\model\BaseModel;

abstract class AreasSportsTable extends BaseModel
{
  public $primaryKey = 'sport_id';

  public $sport_id;
  public $title;
  public $color;
  public $sort;
  public $comments;

  public $tableName = 'areas_sports';

}