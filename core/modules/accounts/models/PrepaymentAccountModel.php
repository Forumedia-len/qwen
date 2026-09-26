<?php

namespace AC\core\modules\accounts\models;

class PrepaymentAccountModel extends AccountsModel
{
  public function insertAccount(&$error_code)
  {
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
}