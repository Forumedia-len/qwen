<?php


use AC\core\engines\AccountsEngine;
use AC\core\engines\Engines;

class accounts_ext
{
  public $config;
  public $sepa_file;
  public $sepa_sender;
  /**
   * @var AccountsEngine
   */
  public $a;
  /**
   * @var Engines
   */
  public $r;
  public $dta_file;


  public function __construct()
  {
    $this->r         = Service::engines();
    $this->a         = useClass(paths()->enginesDir . 'AccountsEngine', true);
    $this->dta_file  = getEngine('dta');
    $this->sepa_file = getEngine('sepa');

    $this->sepa_sender = array(
      $this->config['sepa_name'],
      $this->config['sepa_iban'],
      $this->config['sepa_bic'],
      $this->config['sepa_direct_debit'],
      str_replace(' ', '', $this->config['sepa_name'])
    );
  }

  //#################### ФУНЦКЦИИ SEPA #########################
  function downloadAccountsSEPA()
  {
    $this->page_key = 'accounts_generate';

    $account_type                     = Service::request()->_get('account_type', '');
    $this->sepa_sender['date_start']  = Service::request()->_('date_start', date('d.m.Y'));
    $this->sepa_sender['date_finish'] = Service::request()->_('date_finish', date('d.m.Y'));
    $this->sepa_sender['sepa_type']   = $account_type;

    $this->sepa_file->addAccountSender($this->sepa_sender);
    $area_type = Service::request()->_('type', 1);
    switch ($account_type) {
      case 'aboFitness':
        // @todo перенести в отдельный класс
        Service::engines()->areas->setGroupType('fitness');
        $accounts       = $this->a->getAboAccounts();
        $account_number = ABO_ACCOUNT_NUMBER;
        $file_name      = 'lastschrift_aboFitness_rechnung_';
        break;
      case 'abo':
        $accounts       = $this->a->getAboAccounts(false, false, false, $area_type);
        $account_number = ABO_ACCOUNT_NUMBER;
        $file_name      = 'lastschrift_abo_rechnung_';
        break;
      case 'other':
        $accounts       = $this->a->getOtherAccounts();
        $account_number = OTHER_ACCOUNT_NUMBER;
        $file_name      = 'lastschrift_sonderrechnung_';
        break;
      case 'prepayment':
        $accounts       = $this->a->getPrepaymentAccounts();
        $account_number = PREPAYMENT_ACCOUNT_NUMBER;
        $file_name      = 'lastschrift_guthaben_rechnungsjournal_';
        break;
      case 'membershipFees':
        // @todo перенести в отдельный класс
        $accounts       = module('membershipFees')->useModel('MembershipFeesAccountModel')->getEngine()->getAccounts(false, false, $area_type);
        $account_number = MEMBERSHIP_FEES_ACCOUNT_NUMBER;
        $file_name      = 'lastschrift_mitglieder-rechnungsjournal_';
        break;
      default:
        $accounts       = $this->a->getAccounts(false, false, $area_type);
        $account_number = ACCOUNT_NUMBER;
        $file_name      = 'lastschrift_einzelrechnung_';
        break;
    }
    // Add transaction
    if (!empty($accounts)) {
      foreach ($accounts as $a) {
        $sum = in_array($account_type, ['abo', 'aboFitness']) ? 0 : $a['sum'];
        if (in_array($account_type, ['abo', 'aboFitness'])) {
          if (is_array($a['reservations'])) {
            foreach ($a['reservations'] as $p) {
              $sum += $p['price'];
            }
          }
          $sum = ($a['abo_sum'] > 0 ? $a['abo_sum'] : $sum);
        }
        if (strlen($a['name']) > 0 && strlen($a['sepa_iban']) > 0 && strlen($a['sepa_bic']) > 0 && strlen(
            $a['sepa_mndtid']
          ) > 0 && strlen($a['sepa_dtofsgntr']) > 0 && $sum > 0) {
          $this->sepa_file->addExchange(
            array(
              "id"        => $a['a_number'],
              "name"      => $a['name'] . ' ' . $a['surname'],
              "iban"      => $a['sepa_iban'],
              "bic"       => $a['sepa_bic'],
              "mndtid"    => trim($a['sepa_mndtid']),
              "dtofsgntr" => trim($a['sepa_dtofsgntr'])
            ),
            number_format($sum, 2, '.', ''),
            config('accountView')->getNumberAccount($a['a_number'], $account_number)
            . " " . $this->place_name . " von " . $a['name'] . " " . $a['surname']
          );
        }
      }
      // Save file
      header("Content-Disposition: attachment; filename=" . $file_name . date('Y_m_d') . ".xml");
      header("Content-Type: application/x-force-download");
      echo $this->sepa_file->getFileContent();
      die;
    }
  }
}

?>