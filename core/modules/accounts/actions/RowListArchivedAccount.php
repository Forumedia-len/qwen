<?php

namespace AC\core\modules\accounts\actions;

use Service;
use AC\core\system\helpers\ObjectHelper;

class RowListArchivedAccount extends RowListAccount
{
  protected function getNumber($ItemRow, $accountPrefixNumber = ACCOUNT_NUMBER)
  {
    $ItemRow = parent::getNumber($ItemRow, $accountPrefixNumber);
    $ItemRow->class = $this->data['deleted'] ? 'selectDel' : 'dark';

    return $ItemRow;
  }

  protected function getActions($ItemRow, $separator = '&nbsp;&nbsp;')
  {
    return parent::getActions($ItemRow, $separator);
  }

  protected function getButtonsAction()
  {
    $actions = [];
    $type    = Service::request()->_get('type', module('areas')->useModel()->getFirstActiveType());
    if($this->data['deleted']) {
      $actions['deleted'] = useLayout()->render('link', [
        'field' => ObjectHelper::createObject([
          'href'   => 'accounts_view.php?account_delete=' . $this->data['account_id'] . '&account_type=' . ($this->data['account_type'] + 1) . '&type=' . $type,
          'target' => '_blank',
          'class'  => 'btnConfirmationDelete',
          'value'  => lang('To the credit','accounts'),
        ], true)
      ], 'common');
    }
    $actions['account_view'] = useLayout()->render('link', [
      'field' => ObjectHelper::createObject([
        'href'   => 'accounts_view.php?account=' . $this->data['account_id'] . '&account_type=' . ($this->data['account_type'] + 1) . '&type=' . $type,
        'target' => '_blank',
        'class'  => 'btnView',
        'value'  => lang('View'),
      ], true)
    ], 'common');
    $actions['remove']    = useLayout()->render('link', [
      'field' => ObjectHelper::createObject([
        'href'            => $this->getHrefRemoveAction(),
        'class'           => 'btnRemove',
        'value'           => lang('Cancel', 'accounts'),
        'otherProperties' => 'onclick="return ifConfirm()"'

      ], true)
    ], 'common');

    return $actions;
  }

  protected function getHrefRemoveAction()
  {
    $type    = Service::request()->_get('type', module('areas')->useModel()->getFirstActiveType());
    return 'accounts_view.php?account=' . $this->data['account_id'] . '&account_type=' . ($this->data['account_type'] + 1) . '&type=' . $type;
  }
}