<?php

namespace AC\core\modules\membershipFees\actions;

use AC\core\modules\accounts\actions\RowListAccount;

class RowListAccountMembershipFees extends RowListAccount
{
  protected function getNumber($ItemRow, $accountPrefixNumber = MEMBERSHIP_FEES_ACCOUNT_NUMBER)
  {
    return parent::getNumber($ItemRow, $accountPrefixNumber);
  }

  protected function getDate($ItemRow)
  {
    $ItemRow = parent::getDate($ItemRow);
    $ItemRow->value = date('Y', strtotime($this->data['date_start']));

    return $ItemRow;
  }

  protected function getTitle($ItemRow)
  {
    $ItemRow = parent::getTitle($ItemRow);
    $ItemRow->value .= '<br/>' . $this->data['membership_fees_group_title'];

    return $ItemRow;
  }
}