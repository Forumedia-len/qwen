<?php

namespace AC\mapi\act\repositories\workWith;

use AC\core\system\db\Query;
use AC\core\system\helpers\DataHelper;
use AC\mapi\act\repositories\BaseRepository;

class PFPPay extends BaseRepository
{
  protected function process(): void
  {
    parent::process();
    if(Query::sqlQuery('select count(*) as cnt from ' . Query::tableName('config') .
        ' where alias="type_pricing_system" and value="PFP"', [], true, ['onlyOne' => true])['cnt'] > 0) {
      $prices  = getEngine('areas', false)->getPricesForPlayers();
      $clients = DataHelper::getDataAs(Query::sqlQuery('select client_id, club_state from ' . Query::tableName('clients')), ['key' => 'client_id']);
      $q       = 'select r.reservation_id, r.client_id, r.price, r.type_reservation, r.street_friends, r.ordered, at.* from ' . Query::tableName('reservations') . ' r'
        . ' inner join ' . Query::tableName('areas') . ' a on r.area_id = a.area_id'
        . ' inner join ' . Query::tableName('areas_types') . ' at on a.type_id = at.type_id and at.alias="open"'
        . ' where r.`ordered` >= "2026-06-01 00:00:00" and r.main_reservation_id is null';
      foreach (Query::sqlQuery($q) as $row) {
        $countClub             = [];
        $row['street_friends'] = \Service::cast('array')->get($row['street_friends']);
        if (!empty($row['street_friends'])) {
          foreach ($row['street_friends'] as $friend) {
            $club = $friend['club_state'] ?: 1;
            $countClub[$club] ??= 0;
            $countClub[$club]++;
          }
        }
        $price = 0;
        foreach ($countClub as $club => $count) {
          $price += (float)$prices[$row['type_reservation']][$clients[$row['client_id']]['club_state'] ?? 1][$club][$count]['price'];
        }
        if($row['price'] != $price) {
          if(\Service::request()->checkGet('view')) {
            Debug($row, $price);
          }
          $this->query[] = 'Update ' . Query::tableName('reservations') . ' set price = "' . $price . '" where reservation_id = ' . $row['reservation_id'];
        }
      }
    }
  }
}