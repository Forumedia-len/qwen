<?php

namespace AC\core\modules\membershipFees\actions;

use AC\core\modules\accounts\actions\MarkExecutionAccount;

class MarkExecutionAccountMembershipFees extends MarkExecutionAccount
{
  protected function download($prefixAccountNumber = MEMBERSHIP_FEES_ACCOUNT_NUMBER)
  {
    parent::download($prefixAccountNumber);
  }
}