<?php

namespace AC\core\modules\clients\models;

use AC\core\modules\clients\engines\PrivateAccountTransactionEngine;
use AC\core\modules\clients\tables\PrivateAccountTransactionsTable;

class PrivateAccountTransactionModel extends PrivateAccountTransactionsTable
{
  protected $baseEngine = 'PrivateAccountTransactionEngine';
  
  /**
   * @var PrivateAccountTransactionEngine|mixed
   */
  protected $engine;
  
  public function rules()
  {
    return [
      [['client_id', 'type_direction', 'type_code', 'amount'], 'required'],
      [['client_id', 'related_id'], 'integer'],
      ['amount', 'float'],
      [['type_code', 'type_direction', 'created_user'], 'string', ['useShielding' => true]],
      [['related_data', 'status', 'external_id'], 'string'],
    ];
  }
  
  public function optionalAttributesInsert(): array
  {
    return ['related_id', 'related_data', 'status', 'external_id'];
  }
} 