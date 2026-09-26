<?php

namespace AC\core\modules\accounts\models;

use AC\core\modules\accounts\engines\ItemAccountEngine;
use AC\core\system\model\BaseModel;

abstract class ItemAccountModel extends BaseModel
{
  protected $primary_key = 'reservation_id';

  public $account_id;
  public $price;

  /**
   * @var ItemAccountEngine
   */
  protected $engine;

  public function insert(&$error_code = false): bool
  {
    if (parent::insert($error_code)) {
      return $this->engine->insert($this);
    }

    return false;
  }

}