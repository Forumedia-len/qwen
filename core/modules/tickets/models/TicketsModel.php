<?php

namespace AC\core\modules\tickets\models;

use AC\core\engines\TicketsEngine;
use AC\core\system\model\BaseModel;

class TicketsModel extends BaseModel
{
  protected      $primary_key            = 'ticket_id';
  protected      $baseEngine             = 'TicketsEngine';
  public function getEngine(): TicketsEngine
  {
    return $this->engine;
  }
}