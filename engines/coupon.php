<?php

use AC\core\system\db\Query;

//проводимые акции
class coupon
{

  function initialize()
  {
  }

  function finalize()
  {
  }

  //добавить
  function insertCoupon($title, $price, $status)
  {
      Query::sqlQuery(
      'insert into ' . Query::tableName('coupon') . ' set title = "' . $title . '", price = "' . $price . '", status = "' . $status . '"'
    ,[],false);
  }

  //изменить
  function changeCoupon($coupon_id, $title, $status)
  {
      Query::sqlQuery(
      'update ' . Query::tableName('coupon') . ' set title = "' . $title . '", status = "' . $status . '" where coupon_id = "' . $coupon_id . '"',
          [],false
    );
  }

  //удалить
  function removeCoupon($coupon_id)
  {
      $tmp=Query::sqlQuery('delete from ' . Query::tableName('coupon') . ' where coupon_id = "' . $coupon_id . '"',[],false);
    if ($tmp) {
        $tmp=Query::sqlQuery('delete from ' . Query::tableName('coupon_code') . ' where coupon_id = "' . $coupon_id . '"');
    }
  }

    function getCoupons(&$result)
    {
        $tmp = Query::sqlQuery('select * from ' . Query::tableName('coupon')
            . ' order by coupon_id');
        if ( ! empty($tmp)) {
            $result = array();
            foreach ($tmp as $row) {
                $result[] = $row;
            }
            return true;
        } else {
            $result = null;
            return false;
        }
    }

  function getCoupon($coupon_id, &$result)
  {
    $tmp = Query::sqlQuery('select * from ' . Query::tableName('coupon') . ' where coupon_id = "' . $coupon_id . '"');
    if (!empty($tmp))
    {
      $result = $tmp[0];
      return true;
    } else {
      $result = null;
      return false;
    }
  }

  function insertCode($coupon_id, $code, $status)
  {
    return  Query::sqlQuery(
      'insert into ' . Query::tableName('coupon_code') . ' set coupon_id = "' . $coupon_id . '", code = "' . $code . '", status = "' . $status . '"',
    [],false);
  }

  function getCouponsCodeList($coupon_id)
  {
      $temp=Query::sqlQuery('SELECT cc.*, c.name AS client_name, c.surname  AS client_surname FROM ' . Query::tableName('coupon_code') . ' cc 
				LEFT JOIN ' . Query::tableName('clients') . ' c ON c.client_id = cc.status
				WHERE cc.coupon_id = "' . $coupon_id . '" ORDER BY cc.code_id');
    if (!empty($temp)) {
      $result = array();
      foreach ($temp as $row)
      {
        $result[] = $row;
      }
      return $result;
    }
    return false;
  }

  function checkCodeExists($code)
  {
    $temp = Query::sqlQuery('select count(*) as cnt from ' . Query::tableName('coupon_code') . ' where code = "' . $code . '"');
    return $temp[0]['cnt'] > 0;
  }

  function activeCouponCode($code_id, $client_id)
  {

    return  Query::sqlQuery(
      'update ' . Query::tableName('coupon_code') . ' set status="' . $client_id . '", actived_date="' . date(
        'Y-m-d H:i:s'
      ) . '" where code_id = "' . $code_id . '"'
      ,[]
      ,false
    );
  }

  function getFullCoupons($coupon_id, $type = 0)
  {
    $temp = Query::sqlQuery(
      'SELECT c.coupon_id, c.title, c.price, cc.code_id, cc.code, cc.actived_date, cl.name AS client_name, cl.surname  AS client_surname FROM ' . Query::tableName('coupon_code') . ' cc 
								LEFT JOIN ' . Query::tableName('coupon') . ' c ON c.coupon_id = cc.coupon_id
								LEFT JOIN ' . Query::tableName('clients') . ' cl ON cl.client_id = cc.status
								WHERE c.coupon_id = "' . $coupon_id . '" ' . ($type == 2 ? ' AND cc.status<>"0"' : ($type == 1 ? ' AND cc.status="0"' : '')));

    if (!empty($temp))
    {
      foreach ($temp as $row)
      {
        $result[] = $row;
      }
      return $result;
    }
    return false;
  }

  function getCouponsByCode($code)
  {
      $temp = Query::sqlQuery(
      'SELECT c.coupon_id, c.title, c.price, cc.code_id, cc.code FROM ' . Query::tableName('coupon_code') . ' cc 
								LEFT JOIN ' . Query::tableName('coupon') . ' c ON c.coupon_id = cc.coupon_id
								WHERE cc.code = "' . $code . '" AND cc.status ="0" AND c.status="1"'
    );
    if (!empty($temp[0])) {
        return $temp[0];
    }
    return false;
  }

  function getCouponsByClient($client_id)
  {
      $result = Query::sqlQuery(
      'SELECT c.coupon_id, c.title, c.price, cc.code_id, cc.code, cc.actived_date FROM ' . Query::tableName('coupon_code'). ' cc 
								LEFT JOIN ' . Query::tableName('coupon') . ' c ON c.coupon_id = cc.coupon_id
								WHERE cc.status = "' . $client_id . '"');
    if (!empty($result))
    {
      return $result;
    }
    return false;
  }
}

?>