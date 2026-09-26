<?php

namespace AC\core\modules\membershipFees\actions;

use AC\core\modules\accounts\actions\RowListArchivedAccount;
use AC\core\modules\membershipFees\MembershipFeesModule;

class RowListArchiveAccountMembershipFees extends RowListArchivedAccount
{
  protected function getNumber($ItemRow, $accountPrefixNumber = MEMBERSHIP_FEES_ACCOUNT_NUMBER)
  {
    return parent::getNumber($ItemRow, $accountPrefixNumber);
  }

  protected function getTitle($ItemRow)
  {
    $ItemRow = parent::getTitle($ItemRow);
    $ItemRow->value .= '<br/>' . $this->data['membership_fees_group_title'];

    return $ItemRow;
  }

  protected function getDate($ItemRow)
  {
    $ItemRow = parent::getDate($ItemRow);
    $ItemRow->value = date('Y', strtotime($this->data['date_start']));

    return $ItemRow;
  }

  protected function getHrefRemoveAction()
  {
    if (!MembershipFeesModule::isManagementRequest()) {
      return \Service::structure()->getPageHrefForCurrentPageKey() . '/action/delete/account/'
        . $this->data['account_id'] . '/account_type/' . ($this->data['account_type'] + 1);
    }

    $href = 'membershipFees/accounts/archives/action/delete/account/' . $this->data['account_id']
      . '/account_type/' . ($this->data['account_type'] + 1);

    return MembershipFeesModule::asManagementHref($href);
  }
}
