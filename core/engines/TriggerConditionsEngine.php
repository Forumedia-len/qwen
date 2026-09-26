<?php

namespace AC\core\engines;

use AC\core\system\db\Query;
use AC\core\system\engine\BaseEngine;
use AC\core\system\helpers\ObjectHelper;
use AC\core\system\object\entity\triggerConditions\Condition;

class TriggerConditionsEngine extends BaseEngine
{
  
  protected string $typeBlock = 'trigger';
  
  public function tableName()
  {
    return 'trigger_conditions';
  }
  
  public function getAvailableCondition(): array
  {
    return [];
  }
  
  /**
   * @param array $data
   * @return Condition[]
   */
  public function getConditionsEntities(array $data = []): array
  {
    $entities = [];
    foreach (array_merge($this->getAvailableCondition(), $data) as $alias => $condition) {
      $entities[] = ObjectHelper::createEntity('triggerConditions\Condition', null, $alias, $condition);
    }
    
    return $entities;
  }
  
  public function getConditionsByEntryId($entryId): array
  {
    $data = Query::sqlQuery('select * from ' . Query::tableName($this->tableName()) . ' where entry_id = ? and type_block = ?',
      [$entryId, $this->typeBlock]);
    return $this->renderDataConditions($data)[$entryId] ?? [];
  }
  
  public function getConditions(): array
  {
    $data = Query::sqlQuery('select * from ' . Query::tableName($this->tableName()) . ' where type_block = ?', [$this->typeBlock]);
    
    return $this->renderDataConditions($data) ?? [];
  }
  
  protected function renderDataConditions(?array $data): array
  {
    $availableCondition = $this->getAvailableCondition();
    
    return is_array($data)
      ? array_reduce($data, static function ($carry, $item) use ($availableCondition) {
        if (!isset($carry[$item['entry_id']])) {
          $carry[$item['entry_id']] = $availableCondition;
        }
        // возвращаем только определенные условия из getAvailableCondition()
        if (isset($carry[$item['entry_id']][$item['name']])) {
          $carry[$item['entry_id']][$item['name']]['value'] = $item['value'];
          $carry[$item['entry_id']][$item['name']]['id']    = $item['id'];
        }
        
        return $carry;
      }, [])
      : [];
  }
  
  public function saveCondition($entryId, Condition $condition): bool
  {
    return $condition->id
      ? $this->updateCondition($entryId, $condition)
      : $this->insertCondition($entryId, $condition);
  }
  
  public function removeConditionsByEntryId($entryId): bool
  {
    return Query::sqlQuery('delete from ' . Query::tableName($this->tableName()) . ' where type_block = ? and entry_id = ?',
      [$this->typeBlock, $entryId], false);
  }
  
  public function updateCondition($entryId, Condition $condition): bool
  {
    return Query::sqlQuery('update ' . Query::tableName($this->tableName()) . ' set value = ? where id = ?', [$condition->value, $condition->id],
      false);
  }
  
  public function insertCondition($entryId, Condition $condition): bool
  {
    return Query::sqlQuery('insert into ' . Query::tableName($this->tableName()) . ' (entry_id, name, value, type_block) values (?, ?, ?, ?)',
      [$entryId, $condition->getName(), $condition->value, $this->typeBlock], false);
  }
  
  public function getEntryIdsByConditionName($name): array
  {
    return array_column(Query::sqlQuery('select entry_id from ' . Query::tableName($this->tableName()) . ' where name = ? and type_block = ? and value = 1',
      [$name, $this->typeBlock]) ?? [], 'entry_id');
    
  }
}