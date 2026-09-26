<?php

namespace AC\mapi\act\repositories\workWith;

use AC\mapi\act\repositories\BaseRepository;

class FindInConfig extends BaseRepository
{
  protected function process(): void
  {
    parent::process();
    $res  = [];
    $base = $this->baseDB;
    foreach ($base->getDbo()->showDataBase() as $dbName) {
      if (!in_array($dbName, ['db_test', 'db_old']) && str_starts_with($dbName, 'at_')) {
        foreach ($base->getDbo()->getFullTableName('config', $dbName, true) as $tableName) {
          $tableName = '`' . $dbName . '`.`' . $tableName . '`';
          if ($base->getDbo()->query('SHOW COLUMNS FROM ' . $tableName . ' LIKE \'alias\'')
            && $base->getDbo()->query('SHOW COLUMNS FROM ' . $tableName . ' LIKE \'value\'')
          ) {
            $alias = \Service::request()->_get('alias');
            $value = \Service::request()->_get('value');
            $type  = \Service::request()->_get('type');
            if($alias !== null) {
              $params[] = $alias;
              $where[] = 'alias=?';
              if($type !== null) {
                $params[] = $type;
                $where[] = 'type=?';
              }
              if($value !== null) {
                $params[] = $value;
                $where[] = 'value=?';
              }
              if($r = $base->getDbo()->query('select * from ' . $tableName . ' where ' . implode(' and ', $where) . ' limit 1', $params)) {
                Debug($tableName, $r);
              }
            }
          }
        }
      }
    }
  }
}