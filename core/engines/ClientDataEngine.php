<?php

namespace AC\core\engines;

use AC\core\system\db\Query;

use AC\core\system\engine\BaseEngine;

class ClientDataEngine extends BaseEngine
{
  protected string $typeBlock;
  public function setTableName($table_name = null)
  {
    $table_name = $table_name ?? 'client_data';

    parent::setTableName($table_name);
  }

  public function getClientData($client_id = null)
  {
    return $client_id ? $this->all(array_merge(['client_id' => $client_id], (!empty($this->typeBlock) ? ['typeBlock' => $this->typeBlock] : []))) : [];
  }

  public function getClientsData($clients = [])
  {
    if(!empty($clients)) {
      $data = Query::sqlQuery('select * from ' . Query::tableName($this->tableName())
                              . ' where client_id in('. implode(',' , $clients) .')'
                              . (!empty($this->typeBlock) ? ' and typeBlock="' .$this->typeBlock . '"' : ''));
      $clients = array_flip($clients);
      foreach ($clients as $client => $v) {
        $clients[$client] = [];
      }
      foreach ($data as $item) {
         $clients[$item['client_id']][$item['name']] = $item;
      }
    }

    return $clients;
  }

  public function removeClientData($client_id)
  {
    if($client_id) {
      Query::sqlQuery(
        'delete from ' . $this->query->getTableName() . ' where client_id = "' . $client_id . '"',
        array(),
        false
      );
    }
  }
}