<?php

namespace AC\core\modules\areas\tables;

use AC\core\system\model\BaseModel;

abstract class AreasTypesTable extends BaseModel
{
  public $primaryKey = 'type_id';

  public $type_id;
  public $title;
  public $color;
  public $sort;
  public $comments;
  public $alias;
}