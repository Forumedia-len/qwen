<?php

namespace AC\core\modules\clients\tables;

use AC\core\system\model\BaseModel;

class ClientDataTable extends BaseModel
{
  protected $tableName = "client_data";

  public $id;
  public $client_id;
  public $typeBlock = 'common';
  public $name;
  public $value;

}