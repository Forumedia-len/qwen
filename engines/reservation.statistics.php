<?php

use AC\core\system\db\Query;

class reservation_statistics
{

  function initialize()
  {
  }

  function finalize()
  {
  }

  //общее количество заказов пользователя
  function getReservationsCountByClientId($client_id)
  {
    $result = array();
    $q      = 'select at.title as title, count(r.reservation_id) as cnt 
			from ' . Query::tableName('reservations') . ' r,
				' . Query::tableName('areas') . ' a,
				' . Query::tableName('areas_types') . ' at
			where
				r.area_id = a.area_id and
				a.type_id = at.type_id and
				client_id = ' . $client_id . '
			group by at.type_id';
    $temp   = Query::sqlQuery($q);
    foreach ($temp as $row) {
      $result[] = [$row['title'], $row['cnt']];
    }

    return $result;
  }

  //общее количество удаленных заказов пользователя
  function getRemovedReservationsCountByClientId($client_id)
  {
    $q    = 'select reservations_removed
			from ' . Query::tableName('clients') . '
			where client_id = "' . $client_id . '"';
    $temp = Query::sqlQuery($q);

    return (int)$temp[0]['reservations_removed'];
  }

  //кол-во заказов, сделанных в этом месяце
  function getCurrentMonthReservationsCount()
  {
    $q    = 'select count(*) as cnt 
			from ' . Query::tableName('reservations') . '
			where ordered between "' . date('Y-m') . '-01" and "' . date('Y-m') . '-31"';
    $temp = Query::sqlQuery($q);

    return (int)$temp[0]['cnt'];
  }

}

?>