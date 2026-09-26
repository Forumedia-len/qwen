<?php

namespace AC\core\modules\accounts\models;

use AC\core\modules\accounts\engines\AccountsClientEngine;
use AC\core\modules\clients\models\ClientsModel;
use AC\core\system\model\BaseModel;

class AccountsClientModel extends BaseModel
{
  public $baseEngine = 'AccountsClientEngine';

  /**
   * @var AccountsClientEngine
   */
  protected $engine;
  public    $client_id;
  public    $sys_client_id;
  public    $name;
  public    $surname;
  public    $address;
  public    $post_code;
  public    $city;
  public    $email;
  public    $number;
  public    $account_owner;
  public    $account_number;
  public    $bank_index;
  public    $bank_name;
  public    $outdated = "0";

  public $sepa_iban;
  public $sepa_bic;
  public $sepa_mndtid;
  public $sepa_dtofsgntr;

  public function insert(&$error_code = false)
  {
    if(parent::insert()) {
      if ($client_id = $this->insertAccountClient(
        $this->sys_client_id,
        $this->name,
        $this->surname,
        $this->address,
        $this->post_code,
        $this->city,
        $this->email,
        $this->number,
        $this->getSepaData(),
        $this->getBankData()
      )) {
        return $client_id;
      }
    }

    return false;
  }

  public function setClientData(ClientsModel $client)
  {
    if($this->setData($client)) {
      $this->sys_client_id = $client->client_id;
      $this->client_id     = null;
      $this->setSepaAndBankData($client);
      return true;
    }

    return false;
  }

  public function setSepaAndBankData(ClientsModel $client)
  {
    $this->sepa_bic  = $client->bank_bic;
    $this->sepa_iban = $client->bank_iban;
    $this->sepa_mndtid = $client->bank_sepa_referenz;
    $this->sepa_dtofsgntr = $client->bank_sepa_mandat;
  }

  public function getSepaData()
  {
    return [
      $this->sepa_iban,
      $this->sepa_bic,
      $this->sepa_mndtid,
      $this->sepa_dtofsgntr
    ];
  }

  public function getBankData()
  {
    return [
      $this->account_owner,
      $this->account_number,
      $this->bank_index,
      $this->bank_name
    ];
  }

}