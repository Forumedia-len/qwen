<?php

namespace AC\core\modules\specPrices\models;

use AC\core\modules\specPrices\engines\SpecPricesEngine;
use AC\core\modules\specPrices\tables\SpecPricesTable;

class SpecPricesModel extends SpecPricesTable
{
  public $baseGetFunction        = 'getSprice';
  public $baseGetFunctionAllData = 'getSprices';
  public $primary_key            = 'sprice_id';
  public $baseEngine             = 'SpecPricesEngine';
  /**
   * @var SpecPricesEngine
   */
  public $engine;
  
  public function rules()
  {
    return [
      ['for_all', 'bool'],
      [['code', 'title', 'rate'], 'required'],
      ['rate', 'float'],
      [['code', 'title'], 'string'],
    ];
  }
  
  public function save()
  {
    if (parent::save()) {
      $entryId = $this->{$this->getPrimaryKey()};
      $TCEngine   = $this->getEngine()->getTriggerConditionsEngine();
      $conditions = $TCEngine->getConditionsEntities($TCEngine->getConditionsByEntryId($entryId));
      foreach ($conditions as $condition) {
        $condition->loadValue($this->getData()->conditions[$condition->getName()] ?? null);
        $TCEngine->saveCondition($entryId, $condition);
      }
      
      return true;
    }
    
    return false;
  }
  
  public function remove()
  {
    if(parent::remove()) {
      return $this->getEngine()->getTriggerConditionsEngine()->removeConditionsByEntryId($this->{$this->getPrimaryKey()});
    }
    return false;
  }
  
  public function getEngine(): SpecPricesEngine
  {
    return parent::getEngine();
  }
}