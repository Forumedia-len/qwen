<?php

namespace AC\core\modules\membershipFees\models;

use AC\core\modules\accounts\models\ItemAccountModel;

class MembershipFeesItemAccountModel extends ItemAccountModel
{
  protected $tableName = 'accounts_membership_fees';
  protected $primary_key = 'id';

  protected $baseEngine = 'MembershipFeesItemAccountEngine';


  public $id;
  public $membership_fees_group_title;
  public $membership_fees_group;
}