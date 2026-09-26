<?php

use AC\core\system\db\Query;

//проводимые акции
class reservation_discount
{
  
  //добавить
  function insertDiscount($type, $title, $dimension, $retail, $ticket, $comment)
  {
    if($retail >= 100) {
      $retail = 99.999;
    }
    if($ticket >= 100) {
      $ticket = 99.999;
    }
    $q = 'insert into ' . Query::tableName('config_discount') . ' set type= "' . $type . '", title = "' . $title . '", dimension ="' . $dimension . '", retail = "' . $retail . '", ticket = "' . $ticket . '" , comment = "' . $comment . '"';
    return Query::sqlQuery($q, [], false);
  }
  
  //изменить
  function changeDiscount($title, $dimension, $retail, $ticket, $comment, $discount_id)
  {
    if($retail >= 100) {
      $retail = 99.999;
    }
    if($ticket >= 100) {
      $ticket = 99.999;
    }
    $q = 'update ' . Query::tableName('config_discount') . ' set title = "' . $title . '", dimension ="' . $dimension . '", retail = "' . $retail . '", ticket = "' . $ticket . '" , comment = "' . $comment . '" where discount_id = "' . $discount_id . '"';
    return Query::sqlQuery($q, [], false);
  }
  
  //удалить
  function removeDiscount($discount_id)
  {
    $q1 = 'update ' . Query::tableName('clients') . ' set discount=NULL where discount = ' . $discount_id;
    $q2 = 'delete from ' . Query::tableName('config_discount') . ' where discount_id = ' . $discount_id;
    Query::sqlQuery($q1, [], false);
    Query::sqlQuery($q2, [], false);
  }
  
  function getDiscounts($type, &$result)
  {
    $q      = 'select * from ' . Query::tableName('config_discount') . ' WHERE type="' . $type . '" order by discount_id';
    $result = Query::sqlQuery($q);
    if (!empty($result)) {
      return true;
    } else {
      $result = null;
      
      return false;
    }
  }
  
  function getDiscount($discount_id, &$result)
  {
    $q    = 'select * from ' . Query::tableName('config_discount') . ' where discount_id = ' . $discount_id;
    $temp = Query::sqlQuery($q);
    if (!empty($temp[0])) {
      $result = $temp[0];
      return true;
    } else {
      $result = null;
      
      return false;
    }
  }
  
  function getFullAboDiscount($client_discount_id, $ticket_discount_id, &$result)
  {
    $q    = 'select * from ' . Query::tableName('config_discount') . ' where discount_id = ' . $client_discount_id . ' OR discount_id = ' . $ticket_discount_id;
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      foreach ($temp as $row) {
        $result[($row['discount_id'] == $client_discount_id ? 'client_discount' : 'ticket_discount')] = $row;
      }
      
      return true;
    } else {
      $result = null;
      
      return false;
    }
  }
  
  function checkDiscountExists($discount_id)
  {
    $q    = 'select count(*) as cnt from ' . Query::tableName('config_discount') . ' where discount_id = ' . $discount_id;
    $temp = Query::sqlQuery($q);
    $cnt  = $temp[0]['cnt'];
    return $cnt > 0;
  }
  
}

?>