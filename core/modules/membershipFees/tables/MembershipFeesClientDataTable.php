<?php

namespace AC\core\modules\membershipFees\tables;

use AC\core\system\model\BaseModel;

class MembershipFeesClientDataTable extends BaseModel
{
  public $membership_fees_group;
  public $gender;
  public $entry_date;
  public $auto_transition_group;

  protected $tableName = "client_data";
}