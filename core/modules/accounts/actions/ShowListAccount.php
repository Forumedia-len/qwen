<?php

namespace AC\core\modules\accounts\actions;

use AC\core\modules\accounts\models\AccountsModel;
use Service;
use AC\core\system\helpers\ObjectHelper;

class ShowListAccount
{

  protected string        $accountTypeAlias = '';
  protected $areaType;
  protected AccountsModel $accountsModel;


  public function __construct(AccountsModel $accountsModel)
  {
    $this->accountsModel = $accountsModel;
    view()->addPathToView('core\modules\accounts\views\\');
    $this->areaType = Service::request()->_get('type', module('areas')->useModel()->getFirstActiveType());
  }

  /**
   * @param $data
   *
   * @return string
   */
  public function getRenderTableList($data = []): string
  {

    return view()->render('list', array_merge($this->getMainParams($data), [
      'accountType'        => $this->accountsModel->account_type + 1,
      'popUpWindow'        => count($data) ? $this->getPopUpWindow() : '',
      'formAction'         => $this->getFormAction()
    ]));
  }

  protected function getMainParams($data = []): array
  {
    return [
      'areaType'           => $this->areaType,
      'h1'                 => $this->getH1Title(),
      'description'        => $this->getDescription(),
      'placeAboveTheTable' => $this->getPlaceAboveTheTable(),
      'tableList'          => $this->getTableListData($data),
      'footerButtons'      => $this->getFooter(),
    ];
  }

  /**
   * @return string
   */
  protected function getH1Title(): string
  {
    return lang('accounts_' . $this->accountTypeAlias . ($this->accountTypeAlias ? '_' : '') . 'list_title', 'structure');
  }

  /**
   * @return object
   */
  protected function getPlaceAboveTheTable(): object
  {
    if (!config('account')->useSEPA()) {
      return (object)[];
    }
    //+5 рабочих дней (кроме пят, сб, вс)
    $d         = 0;
    $d_end     = 7;
    $next_date = strtotime("+" . $d . " day");
    while ($d < $d_end) {
      $next_date = strtotime("+" . $d . " day");
      $d++;
      if (date('N', $next_date) == 6 || date('N', $next_date) == 7) {
        $d_end++;
      }
    }

    return (object)[
      'text'  => view()->render('sepa_form', [
        'account_type' => $this->accountTypeAlias,
        'areaType'     => $this->areaType,
        'date_start'   => date('d.m.Y'),
        'date_finish'  => date('d.m.Y', $next_date)
      ]),
      'style' => 'text-align:right'
    ];
  }

  protected function getFooter()
  {
    return view()->render('footer_buttons', ['areaType' => $this->areaType]);
  }

  protected function getDescription()
  {
    return lang('text_about_archiving_invoices', 'accounts');
  }

  protected function getNavigationYearsLine($archive = false)
  {
    $out      = ObjectHelper::createObject();

    if (($date = $this->accountsModel->getMinMaxDateAndCurrentYear($archive)) && $date->minDate && $date->maxDate) {
      $menu = [];
      for (
        $y = date('Y', strtotime($date->maxDate));
        $y >= date('Y', strtotime($date->minDate));
        $y--
      ) {
        $menu[] = ObjectHelper::createObject([
          'key'   => $y,
          'title' => $y,
          'href'  => site_url(Service::structure()->getPageHrefForCurrentPageKey() . '/year/' . $y . '/type/' . $this->areaType)
        ], true);
      }
      $out->text = useLayout()->render('menu/link_menu', [
        'menu'        => $menu,
        'currentItem' => $date->currentYear
      ], 'admin');
    }

    return $out;
  }

  protected function getPopUpWindow()
  {
    $out = '';

    if (defined('USE_ACCOUNT_TEXT_FIELDS') && USE_ACCOUNT_TEXT_FIELDS) {
      $out = useLayout()->render('popUpWindow', [
        'place_id'     => 'pupopWindowPlace',
        'place_class'  => 'pupopWindowPlace',
        'window_id'    => 'pupopWindow',
        'window_class' => 'pupopWindow'
      ], 'admin');
    }

    return $out;
  }

  protected function getTableListData($data = [])
  {
    $titles = $this->getTitlesForTableList();
    return ['titles' => $titles, 'rows' => $this->renderDataForTableList($data, array_keys($titles))];
  }


  protected function getTitlesForTableList()
  {
    $titles           = [];
    $field            = ObjectHelper::createObject([
      'name'            => 'all_select',
      'value'           => 0,
      'checked'         => false,
      'id'              => 'checkbox_all_select',
      'otherProperties' => ' onchange="selectAllAccount(this)" ',
      'labelName'       => lang('To mark'),
    ], true);
    $titles['mark']   = [
      'width' => '10%',
      'title' => useLayout()::render('checkbox', ['field' => $field], 'admin')
        . ' ' . useLayout()::render('label', ['field' => $field], 'admin')
    ];
    $titles['number'] = ['width' => '10%', 'title' => lang('Invoice number', 'accounts')];
    $titles['date']   = ['width' => '10%', 'title' => lang('Date')];
    $titles['title']   = ['width' => '30%', 'title' => lang('Name-address', 'accounts')];
    $titles['status'] = ['width' => '7%', 'title' => lang('Status', 'accounts')];
    $titles['amount'] = ['width' => '13%', 'title' => lang('Total amount', 'accounts') . ' (' . CURR_VALUTE . ')'];
    if (config('account')->useSEPA()) {
      $titles['sepa'] = ['width' => '5%', 'title' => lang('SEPA_title')];
    }
    $titles['actions'] = ['width' => false, 'title' => lang('Actions')];

    return $titles;
  }

  protected function renderDataForTableList($data = [], $titleKeys = [])
  {
    $outData = [];
    $itemList = $this->getRowListAccountObject();
    foreach ($data as $datum) {
      $outData[] = $itemList->getRow($datum, $titleKeys);
    }

    return $outData;
  }

  protected function getRowListAccountObject($name = 'RowListAccount'): mixed
  {
    $baseActionName = paths()->actionsDir . $name;
    $className      = $baseActionName;
    if (useClass($baseActionName . ucfirst($this->accountTypeAlias))) {
      $className = $baseActionName . ucfirst($this->accountTypeAlias);
    }

    return useClass($className, true);
  }

  protected function getFormAction()
  {
    return 'accounts.php?action=markExecution&type='. $this->areaType;
  }

}