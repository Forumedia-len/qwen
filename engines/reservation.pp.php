<?php

use AC\core\system\db\Query;

//проводимые акции
class reservation_pp
{

  function __construct()
  {
    $this->table = Query::tableName('config_pp');
  }

  //добавить
  function insertPP($sort, $price_real, $price_account)
  {
    $q = 'insert into ' . $this->table . ' set sort = ' . $sort . ', price_real = "' . $price_real . '", price_account = "' . $price_account . '"';

    return Query::sqlQuery($q, array(), false);
  }

  //изменить
  function changePP($pp_id, $sort, $price_real, $price_account)
  {
    $q = 'update ' . $this->table . ' set sort = ' . $sort . ', price_real = "' . $price_real . '", price_account = "' . $price_account . '" where pp_id = ' . $pp_id;

    return Query::sqlQuery($q, array(), false);
  }

  //удалить
  function removePP($pp_id)
  {
    $q = 'delete from ' . $this->table . ' where pp_id = ' . $pp_id;

    return Query::sqlQuery($q, array(), false);
  }

  function getPPs(&$result)
  {
    $q      = 'select * from ' . $this->table . ' order by sort';
    $result = Query::sqlQuery($q);
    if (!empty($result)) {
      return true;
    } else {
      $result = null;

      return false;
    }
  }

  function getPP($pp_id, &$result)
  {
    $q    = 'select * from ' . $this->table . ' where pp_id = ' . $pp_id;
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

?>