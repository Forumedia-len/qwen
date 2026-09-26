<?php
namespace AC\core\modules\accounts\tables;

use AC\core\engines\AccountsEngine;
use AC\core\system\model\BaseModel;

abstract class AccountsTable extends BaseModel
{
  /**
   * @var AccountsEngine
   */
  protected $engine;

  public $account_id;
  public $client_id;
  public $number;
  public $account_type;
  public $date_start;
  public $date_finish;
  public $execution;
  public $send;
  public $archives;
  public $deleted;
  public $closed;
  public $nds;
  public $weekdays;
  public $ticket_id;
  public $sepa_type;
  public $sepa_standart;
  public $abo_sum;
  public $text_config;
  public $info;
  public $count_game;
  public $price_info;

  /** Создать новый счет
   *
   * @param bool $error_code
   *
   *
   * @return bool
   */
  public function insert(&$error_code = false)
  {
    if (parent::insert($error_code)) {
      switch ($this->account_type) {
        case 3:
          return $this->insertPrepaymentAccount($error_code);
          break;
        default:
          return true;
      }
    } else {
      return false;
    }
  }

  /** Закрыть счет по его id
   *
   * @param $account_id
   *
   * @return bool
   */
  public function closed($account_id)
  {
    return $this->engine->closedPrepaymentAccount($account_id);
  }

  abstract public function insertPrepaymentAccount(&$error_code);
  abstract public function insertAccount(&$error_code);

  public function getEngine(): AccountsEngine
  {
    return parent::getEngine();
  }
}