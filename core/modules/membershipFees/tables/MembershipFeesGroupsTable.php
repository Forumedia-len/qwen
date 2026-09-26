<?php

namespace AC\core\modules\membershipFees\tables;

use AC\core\system\model\BaseModel;

class MembershipFeesGroupsTable extends BaseModel
{
  public $id;
  public $title;
  public $inclusion_age;
  public $rate;
  public $active;

  protected $tableName = "membership_fees_groups";
}