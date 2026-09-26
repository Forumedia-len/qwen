<?php

namespace AC\core\modules\stocks\models;

use AC\core\engines\StocksEngine;
use AC\core\modules\stocks\tables\StocksTable;

class StocksModel extends StocksTable
{
  public $baseGetFunction        = 'getStock';
  public $baseGetFunctionAllData = 'getStocks';
  public $primary_key            = 'stock_id';
  public $baseEngine             = 'StocksEngine';
  
  public function rules()
  {
    return [
      [['only_once', 'for_all'], 'bool'],
      [['code', 'title', 'rate', 'dimension'], 'required'],
      ['rate', 'float'],
      [['code', 'title'], 'string'],
    ];
  }
  
  /**
   * @var StocksEngine
   */
  public $engine;
}