<?php

namespace AC\core\modules\membershipFees\config;

use AC\core\system\config\BaseConfig;

class MembershipFeesConfig extends BaseConfig
{
  public function useMembershipFees()
  {
    return (!defined('USE_MEMBERSHIP_FEES') || USE_MEMBERSHIP_FEES);
  }

  public function verificationPropertiesMembershipFees($tableName = null, $additionalConditions = [])
  {
    $out = array_replace_recursive(
      [
        'clients'     => [
          'club_state' => '> 1'
        ],
        'client_data' => [
          'membership_fees_group' => '!= "0"',
          'entry_date'            => 'is not null',
          'gender'                => '!= "not"',
        ]
      ], $additionalConditions);

    return $tableName && isset($out[$tableName]) ? $out[$tableName] : $out;
  }

  public function maxCountGroups() : int
  {
    return defined('MAX_COUNT_MEMBERSHIP_FEES_GROUPS') ?  MAX_COUNT_MEMBERSHIP_FEES_GROUPS : false;
  }
}