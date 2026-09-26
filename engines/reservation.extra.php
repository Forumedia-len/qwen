<?php

use AC\core\system\db\Query;


//проводимые акции
class reservation_extra
{
  //изменить
  function changeExtra($no_club_rate, $club_rate, $club_rate2)
  {
    $q = 'update ' . Query::tableName(
        'config_extra'
      ) . ' set no_club_rate = "' . $no_club_rate . '", club_rate = "' . $club_rate . '", club_rate2 = "' . $club_rate2 . '"';
    Query::sqlQuery($q, array(), false);
  }

  function getExtra(&$result)
  {
    $q = 'select * from ' . Query::tableName('config_extra') . ' limit 1';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      $result = $temp[0];

      return true;
    } else {
      $result = null;

      return false;
    }
  }
}