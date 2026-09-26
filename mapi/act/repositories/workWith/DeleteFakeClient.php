<?php

namespace AC\mapi\act\repositories\workWith;

use AC\core\system\db\DB;
use AC\mapi\act\repositories\BaseRepository;
use Service;

class DeleteFakeClient extends BaseRepository
{
  
  protected function process(): void
  {
    $db = DB::instance('db', true,
      [
        'dbname'      => Service::request()->_get('dbname'),
        'prefixTable' => Service::request()->_get('prefixTable'),
      ]
    )->getDbo();
    foreach ($db->query('select client_id from ' . $db->generateTableName('reservations') . ' group by client_id') as $item) {
      $reservationsClients[] = $item['client_id'];
    }
    foreach ($db->query('select client_id from ' . $db->generateTableName('tickets') . ' group by client_id') as $item) {
      $ticketsClients[] = $item['client_id'];
    }
    foreach ($db->query('select client_id, login,name, surname, city, post_code, registered, account_owner, account_number, bank_index from ' . $db->generateTableName('clients') . ' where prepayment_sum <=0.01 /*and registered>"2024-06-01"*/') as $client) {
      if (!in_array($client['client_id'], $reservationsClients) && !in_array($client['client_id'], $ticketsClients)) {
        if (!empty($client['account_owner']) || !empty($client['account_number']) || !empty($client['bank_index'])) {
          if ((is_numeric($client['account_owner']) && $client['name'] == $client['surname'] && $client['name'] == $client['city']) || is_string($client['account_owner'])) {
            $this->dataAs[] = $client;
            $this->query[]  = 'delete from ' . $db->generateTableName('clients') . ' where client_id=' . $client['client_id'];
          }
        }
      }
    }
  }
}
