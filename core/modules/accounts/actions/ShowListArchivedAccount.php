<?php

namespace AC\core\modules\accounts\actions;

class ShowListArchivedAccount extends ShowListAccount
{
  protected function getH1Title(): string
  {
    return lang('accounts_' . $this->accountTypeAlias . ($this->accountTypeAlias ? '_' : '') . 'archives_title', 'structure');
  }

  protected function getPlaceAboveTheTable(): object
  {

    return $this->getNavigationYearsLine(true);
  }

  protected function getFooter()
  {
    return '';
  }

  protected function getDescription()
  {
    return '';
  }

  public function getRenderTableList($data = []): string
  {
    return view()->render('archive', array_merge($this->getMainParams($data), [

    ]));
  }

  protected function getTitlesForTableList()
  {
    $titles = [];
    $titles['number'] = ['width' => '10%', 'title' => lang('Invoice number', 'accounts')];
    $titles['date']   = ['width' => '10%', 'title' => lang('Date')];
    $titles['title']   = ['width' => '30%', 'title' => lang('Name-address', 'accounts')];
    $titles['status'] = ['width' => '5%', 'title' => lang('Status', 'accounts')];
    $titles['amount'] = ['width' => '20%', 'title' => lang('Total amount', 'accounts') . ' (' . CURR_VALUTE . ')'];
    $titles['actions'] = ['width' => false, 'title' => lang('Actions')];

    return $titles;
  }

  protected function getRowListAccountObject($name = 'RowListArchiveAccount'): RowListArchivedAccount
  {
    return parent::getRowListAccountObject($name);
  }
}