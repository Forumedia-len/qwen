<?php

namespace AC\core\modules\accounts\models;

use AC\core\modules\accounts\tables\AccountsTable;
use AC\core\modules\clients\models\ClientsModel;
use AC\core\system\helpers\ObjectHelper;


class AccountsModel extends AccountsTable
{
  public $baseGetFunction = 'getAccountsById';
  public $primary_key     = 'account_id';
  public $baseEngine      = 'AccountsEngine';

  public $name;
  public $surname;
  public $address;
  public $post_code;
  public $city;
  public $email;
  public $nds_rate;
  public $account_owner;
  public $account_number;
  public $bank_index;
  public $bank_name;
  public $price;

  public $bank_iban;
  public $bank_bic;
  public $bank_sepa_referenz;
  public $bank_sepa_mandat;

  /**
   * @var AccountsClientModel
   */
  protected $clientAccount;

  protected $itemsAccount = [];

  public function __construct($id = null)
  {
    parent::__construct($id);
    $this->instanceClientAccount();
  }

  protected function instanceClientAccount()
  {
    $this->clientAccount = module('accounts')->useModel('AccountsClientModel');
  }

  /** Заполнить данные клиента счета
   *
   * @param ClientsModel $client
   *
   * @return bool
   */
  public function setClientAccount(ClientsModel $client): bool
  {
    if ($this->clientAccount->setClientData($client)) {
      $this->sepa_type     = $client->sepa_type;
      $this->sepa_standart = $client->sepa_standart;
      $this->nds           = $client->nds_rate;

      return true;
    }

    return false;
  }

  /**
   * @param $data
   *
   * @return bool
   */
  public function setDataLoad($data)
  {
    if ($this->setData($data)) {
      if (isset($data->current_client)) {
        $this->setData($data->current_client);

        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }


  public function insertPrepaymentAccount(&$error_code)
  {
    // todo перенести в PrepaymentAccountModel.php
    if ($this->account_id = $this->engine->insertPrepaymentAccount(
      $this->client_id,
      $this->name,
      $this->surname,
      $this->address,
      $this->post_code,
      $this->city,
      $this->email,
      $this->number,
      date('Y-m-d H:i:s'),
      0,
      (int)$this->nds_rate,
      array(
        $this->bank_iban,
        $this->bank_bic,
        $this->bank_sepa_referenz,
        $this->bank_sepa_mandat,
        $this->sepa_type,
        $this->sepa_standart
      ),
      array(
        $this->account_owner,
        $this->account_number,
        $this->bank_index,
        $this->bank_name
      ),
      $this->price,
      false,
      $this->ticket_id
    )) {
      return $this->account_id;
    } else {
      return false;
    }
  }

  public function insertAccount(&$error_code)
  {
    if ($this->clientAccount->client_id = $this->clientAccount->insert($error_code)) {
      $this->client_id = $this->clientAccount->client_id;
      $this->number    = $this->engine->generateAccountNumber($this->account_type);
      if ($this->insert($error_code)) {
        if ($this->insertItemsAccount()) {
          return true;
        } else {
          $this->engine->fullRemoveAccount($this->account_id);
        }
      }
    }

    return false;
  }

  public function insertItemsAccount()
  {
    if (!empty($this->getItemsAccount())) {
      foreach ($this->getItemsAccount() as $itemAccount) {
        /** @var ItemAccountModel $itemAccount */
        $itemAccount->account_id = $this->account_id;
        if (!$itemAccount->insert()) {
          return false;
        }
      }
      $this->resetItemsAccount();
    }

    return true;
  }

  public function getClientAccount(): AccountsClientModel
  {
    return $this->clientAccount;
  }

  public function addItemAccount(ItemAccountModel $itemAccount)
  {
    $this->itemsAccount[] = $itemAccount;
  }

  public function resetItemsAccount()
  {
    $this->itemsAccount = [];
  }

  /**
   * @return array
   */
  public function getItemsAccount(): array
  {
    return $this->itemsAccount;
  }

  public function getMinMaxDateAndCurrentYear($archive = false)
  {
    static $outDate;
    if (empty($outDate)) {
      $outDate              = ObjectHelper::createObject();
      $outDate->currentYear = \Service::request()->_get('year', date('Y'));
      if (($year_periods = $this->engine->getMaxMinDate($this->account_type, $archive)) && !empty($year_periods['max_date']) && !empty($year_periods['min_date'])) {
        if ($outDate->currentYear > date('Y', strtotime($year_periods['max_date'])) || $outDate->currentYear < date(
            'Y',
            strtotime($year_periods['min_date'])
          )) {
          $outDate->currentYear = date('Y', strtotime($year_periods['max_date']));
        }

        $outDate->minDate = $year_periods['min_date'];
        $outDate->maxDate = $year_periods['max_date'];
      }
    }

    return $outDate;
  }

  public function getCurrentYear($archive = false)
  {
    return $this->getMinMaxDateAndCurrentYear($archive)->currentYear;
  }
}