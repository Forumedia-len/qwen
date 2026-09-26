<?php

use AC\core\system\db\Query;

//проводимые акции
class reservation_nds
{

//	function reservation_nds () {
//		uses ('mysql');
//	}

  //добавить
  function insertNds($rate, $comment)
  {
    $q = 'insert into ' . Query::tableName('config_nds') . ' set rate = "' . $rate . '", comment = "' . $comment . '"';

    return Query::sqlQuery($q, array(), false);
  }

  //изменить
  function changeNds($rate, $comment, $nds_id)
  {
    $q = 'update ' . Query::tableName('config_nds') . ' set rate = "' . $rate . '", comment = "' . $comment . '" where nds_id = "' . $nds_id . '"';

    return Query::sqlQuery($q, array(), false);
  }

  function getNdss(&$result)
  {
    $q    = 'select * from ' . Query::tableName('config_nds') . ' order by rate';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      $result = array();
      foreach ($temp as $row) {
        $result[$row['nds_id']] = $row;
      }

      return true;
    } else {
      $result = null;

      return false;
    }
  }

  function getNdssbyProc($nds, &$result)
  {
    $q    = 'select * from ' . Query::tableName('config_nds') . ' where rate = "' . $nds . '" order by rate';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      $result                     = array();
      $result[$temp[0]['nds_id']] = $temp[0];

      return true;
    } else {
      $result = null;

      return false;
    }
  }

  function getNds($nds_id, &$result)
  {
    $temp = Query::sqlQuery('select * from ' . Query::tableName('config_nds') . ' where nds_id = ' . $nds_id);
    if (!empty($temp)) {
      $result = $temp[0];

      return true;
    } else {
      $result = null;

      return false;
    }
  }

  function getDefaultNdsId()
  {
    $temp = Query::sqlQuery('select nds_id from ' . Query::tableName('config_nds') . ' where set_default = "1"');
    if (!empty($temp)) {
      return (int)$temp[0]['nds_id'];
    } else {
      return false;
    }
  }

  function checkNdsExists($nds_id)
  {
    $temp = Query::sqlQuery('select count(*) as cnt from ' . Query::tableName('config_nds') . ' where nds_id = ' . $nds_id);
    $cnt  = $temp[0]['cnt'];

    return $cnt > 0;
  }

}

?>