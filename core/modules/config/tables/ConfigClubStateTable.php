<?php

namespace AC\core\modules\config\tables;

use AC\core\system\model\BaseModel;

class ConfigClubStateTable extends BaseModel
{
  public $id;
  public $title;
  public $comment;
  public $mark;
  public $active;

  public $tableName = 'config_club_state';
}