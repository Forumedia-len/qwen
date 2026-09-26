<?php

namespace AC\core\modules\accounts\actions;

use AC\core\modules\accounts\models\AccountsModel;
use Service;

class MarkExecutionAccount
{
  protected AccountsModel $accountsModel;

  protected $accountIds = [];
  protected $accountType;
  protected $areaType;

  public function __construct(AccountsModel $accountsModel)
  {
    $this->accountsModel = $accountsModel;
    view()->addPathToView('core\modules\accounts\views\\');
    $this->accountIds  = Service::request()->_post('account', (array)Service::request()->_get('account', []));
    $this->accountType = Service::request()->_post('account_type', Service::request()->_get('account_type', 0));
    $this->areaType    = Service::request()->_get('type', module('areas')->useModel()->getFirstActiveType());
  }

  public function markExecution($action = null)
  {
    if (method_exists($this, $action) && (in_array($action, $this->getActionsWhereDontCheckAccountIds()) || !empty($this->accountIds))) {
      return $this->$action();
    }

    return '';
  }

  protected function print()
  {
    $this->accountsModel->getEngine()->markExecution($this->accountIds);

    return $this->accountPrint();
  }

  protected function sendAllEmails()
  {
    return $this->accountPrint('sendAll', true);
  }

  protected function accountPrint($action = 'print', $send = false)
  {
    return view()->render('accountPrint', [
      'accountIds'     => $this->accountIds,
      'accountType'    => $this->accountType,
      'action'         => $action,
      'jsonAccountIds' => json_encode($this->accountIds),
      'send'           => $send,
      'formAction'     => 'accounts_view.php',
    ]);
  }

  protected function archives()
  {
    $this->accountsModel->getEngine()->sendInArchives($this->accountIds);

    return '';
  }


  protected function download($prefixAccountNumber = ACCOUNT_NUMBER)
  {
    $fields = [
      'Invoice number'      => lang('Invoice number', 'accounts'),
      'Name'                => lang('Name'),
      'Surname'             => lang('First name'),
      'Street'              => lang('Street/No'),
      'Zip'                 => lang('Zip'),
      'City'                => lang('City'),
      'Bank account holder' => lang('Bank account holder'),
      'Bank name'           => lang('Bank name'),
      'IBAN'                => lang('IBAN'),
      'BIC'                 => lang('BIC'),
      'Mandate reference'   => lang('Mandate reference'),
      'Mandate date'        => lang('Mandate date'),
      'Amount'              => lang('Amount'),
      'Net amount'          => lang('Net amount'),
      'VAT Amount'          => lang('VAT Amount'),
      'VAT Set'             => lang('VAT Set'),
      'E-mail'              => lang('E-mail')
    ];
    $out    = implode(';', $fields) . "\n";
    if ($accounts = $this->accountsModel->getEngine()->getAccounts(false, false, $this->areaType)) {
      foreach ($accounts as $a) {
        $nds_sum = 0;
        $nds_sum = $a['sum'] - ($a['sum'] / (1 + $a['nds'] / 100));

        $out .= join(
            ';',
            array(
              config('accountView')->getNumberAccount($a['a_number'], $prefixAccountNumber),
              $a['name'],
              $a['surname'],
              $a['address'],
              $a['post_code'],
              $a['city'],
              $a['account_owner'],
              $a['bank_name'],
              $a['sepa_iban'],
              $a['sepa_bic'],
              trim($a['sepa_mndtid']),
              trim($a['sepa_dtofsgntr']),
              number_format($a['sum'], 2, ',', ''),
              number_format(($a['sum'] - $nds_sum), '2', ',', ''),
              number_format($nds_sum, '2', ',', ''),
              $a['nds'] . ' %',
              $a['email']
            )

          ) . "\n";
      }
      header("Content-Disposition: attachment; filename=accounts_export_" . date('Y_m_d') . ".csv");
      header(
        "Content-Type: application/x-force-download; name=\"accounts_export_" . date('Y_m_d') . ".csv\"; charset=utf-8"
      );
      echo "\xEF\xBB\xBF" . $out;
      die;
    }
  }

  protected function delete()
  {
    foreach ($this->accountIds as $accountId) {
      $this->accountsModel->getEngine()->deleteAccount($accountId);

    }
  }

  protected function getActionsWhereDontCheckAccountIds(): array
  {
    return ['download'];
  }
}