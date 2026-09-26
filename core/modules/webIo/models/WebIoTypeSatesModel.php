<?php

namespace AC\core\modules\webIo\models;

use AC\core\system\db\Query;


use PDO;

class WebIoTypeSatesModel
{
  public $id;
  public $alias;
  public $title;
  public $active;
  public $use_reservation;
  
  protected static $table = 'webio_type_states';
  
  private static $type_states;
  
  private static function setTypeStates($new = false)
  {
    if (empty(self::$type_states) || $new) {
      self::$type_states = [];
      self::$type_states = Query::sqlQuery(
        'select * from ' . Query::tableName(self::$table),
        [],
        true,
        ['style' => PDO::FETCH_CLASS, 'argument' => module('webIo')->getFullModelName('WebIoTypeSatesModel')]
      );
    }
  }
  
  public static function reset()
  {
    self::setTypeStates(true);
  }
  
  public static function getTypeSates($asKey = null, $onlyActive = true, $all = true, $onlyUseForReservation = false): array
  {
    self::setTypeStates();
    $result = [];
    $states = $all ? array_merge([self::getOverall()], self::$type_states) : self::$type_states;
    foreach ($states as $state) {
      $state->title = lang('title_type_webIo_' . $state->alias, 'webIo', [], $state->title);
      if ((!$onlyActive || $state->active) && (!$onlyUseForReservation || $state->use_reservation)) {
        if ($asKey && isset($state->{$asKey})) {
          $result[$state->{$asKey}] = $state;
        } else {
          $result[] = $state;
        }
      }
    }
    
    return $result;
  }
  
  public static function getOverall()
  {
    $all         = new WebIoTypeSatesModel();
    $all->id     = 0;
    $all->alias  = 'all';
    $all->title  = lang('title_type_webIo_all', 'webIo');
    $all->active = 1;
    
    return $all;
  }
  
  public static function setActiveByAlias($active, $alias, $reset = true)
  {
    if (Query::sqlQuery(
      'update ' . Query::tableName(self::$table) . ' set active= :active where alias = :alias',
      ['active' => (int)$active, 'alias' => $alias],
      false
    )) {
      if ($reset) {
        self::reset();
      }
      
      return true;
    }
    
    return false;
  }
  
  public static function setActiveById($active, $id, $reset = true)
  {
    if (Query::sqlQuery(
      'update ' . Query::tableName(self::$table) . ' set active= :active where id = :id',
      ['active' => (int)$active, 'id' => $id],
      false
    )) {
      if ($reset) {
        self::reset();
      }
      
      return true;
    }
    
    return false;
  }
  
  public static function getTitleWebIoTypeSateById($id)
  {
    return self::getTypeSates('id', false, false)[$id]->title;
  }
  
  public static function setActive($alias, $typeStates)
  {
    $name = 'setActiveBy' . ucfirst($alias);
    foreach ($typeStates as $key => $state) {
      self::{$name}($state, $key, false);
    }
    self::reset();
  }
  
  
}