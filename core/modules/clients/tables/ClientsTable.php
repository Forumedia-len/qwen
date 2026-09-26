<?php

namespace AC\core\modules\clients\tables;

use AC\core\system\model\BaseModel;

class ClientsTable extends BaseModel
{
  public $client_id;
  public $mode;
  public $system_mode;
  public $super;
  public $area_type;
  public $club_state;
  public $encash;
  public $active;
  public $nds;
  public $discount;
  public $login;
  public $password_md5;
  public $number;
  public $name;
  public $surname;
  public $birthday;
  public $phone;
  public $phone_mobile;
  public $fax;
  public $city;
  public $post_code;
  public $address;
  public $email;
  public $start_order_period;
  public $reservations_removed;
  public $registered;
  public $account_owner;
  public $account_number;
  public $bank_index;
  public $bank_name;
  public $codecard;
  public $limit_day;
  public $prepayment_sum;
  public $stock_id = (REGISTRATION_STOCK_ID!==false)?('a:1:{i:0;s:1:\"'.REGISTRATION_STOCK_ID.'\";}'):(null);
  public $sprice_id;
  public $sepa_type;
  public $sepa_standart;
  public $bank_iban;
  public $bank_bic;
  public $bank_sepa_mandat;
  public $bank_sepa_referenz;

  public $nds_rate;
  public $discount_retail;
  public $discount_ticket;
  public $extra;

  public $unavailable_sports;
  public $abo_delete;
  public $refund_for_ticket;
  public $refund_for_paypal;
  public $student;
  public $student_number;
  public $firm;

  protected $tableName = 'clients';

}