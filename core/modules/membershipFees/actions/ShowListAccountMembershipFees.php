<?php

namespace AC\core\modules\membershipFees\actions;

use AC\core\modules\accounts\actions\ShowListAccount;
use AC\core\modules\membershipFees\MembershipFeesModule;

class ShowListAccountMembershipFees extends ShowListAccount
{
  protected string $accountTypeAlias = 'membershipFees';

  protected function getFormAction()
  {
    return MembershipFeesModule::isManagementRequest()
      ? MembershipFeesModule::asManagementHref('membershipFees/accounts/list')
      : 'membershipFees/accounts/list';
  }

}
