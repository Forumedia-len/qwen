<?php

namespace AC\core\modules\membershipFees\models;

use AC\core\modules\clients\models\ClientsModel;
use AC\core\modules\membershipFees\engines\MembershipFeesClientEngine;

class MembershipFeesClientModel extends ClientsModel
{
  public $membership_fees_group;
  public $gender;
  public $entry_date;
  public $auto_transition_group;

  /**
   * @var MembershipFeesClientEngine
   */
  protected $baseEngine = 'MembershipFeesClientEngine';

  public function rules()
  {
    return array_merge(parent::rules(), [
      ['auto_transition_group', 'bool'],
      [['membership_fees_group', 'entry_date'], 'integer'],
    ]);
  }

  /**
   * @return MembershipFeesClientEngine
   */
  public function getEngine(): MembershipFeesClientEngine
  {
    return parent::getEngine();
  }
}