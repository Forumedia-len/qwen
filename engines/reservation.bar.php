<?php

use AC\core\system\db\Query;

class reservation_bar
{

  /* интерфейс */
  function initialize()
  {
  }

  function finalize()
  {
  }

  function getBarItems($date)
  {
    $query = 'select * from ' . Query::tableName('reservations_bar') . ' where in_date="' . $date . '" order by bar_id ';
    $items = Query::sqlQuery($query);

    return $items;
  }

  function getBarItemById($bar_id, &$item)
  {
    $item  = array();
    $query = 'select * from ' . Query::tableName('reservations_bar') . ' where bar_id = "' . $bar_id . '"';
    $items = Query::sqlQuery($query);
    if (count($items) > 0) {
      //получаем строку таблицы
      $item = $items[0];

      return true;
    } else {
      return false;
    }
  }

  //удалить
  function removeBarItemById($bar_id)
  {
    $query = 'delete from ' . Query::tableName('reservations_bar') . ' where bar_id = "' . $bar_id . '"';

    return Query::sqlQuery($query, array(), false);
  }

  //изменить
  function changeBarItemById($bar_id, $title, $sum, $encash, $nds, $comment)
  {
    $query = 'update ' . Query::tableName('reservations_bar') . ' 
				set 
					title = "' . addslashes($title) . '",
					sum = "' . $sum . '",
					encash = "' . $encash . '",
					nds = "' . $nds . '",
					comment = "' . addslashes($comment) . '" 
				where bar_id = "' . $bar_id . '"';

    return Query::sqlQuery($query, array(), false);
  }

  //добавить
  function insertBarItem($date, $title, $sum, $encash, $nds, $comment)
  {
    $query = 'insert into ' . Query::tableName('reservations_bar') . ' set 
				in_date = "' . $date . '",
				title = "' . addslashes($title) . '",
				sum = "' . $sum . '",
				encash = "' . $encash . '",
				nds = "' . $nds . '",
				comment = "' . addslashes($comment) . '"';

    return Query::sqlQuery($query, array(), false);
  }
  //добавить
  function insertBarTitle($title, $nds, $active = 1)
  {
    $sort = 0;
    $link = mysql_queryD('select max(sort) as max from ' . DB_TABLE_PREFIX . 'reservations_bar_titles');
    if (mysql_num_rows($link) > 0) {
      //получаем строки таблицы
      $row  = mysql_fetch_assoc($link);
      $sort = $row['max'] + 1;
    }

    return mysql_query(
      'insert into ' . DB_TABLE_PREFIX . 'reservations_bar_titles
			set 
				title = "' . addslashes($title) . '",
				nds = "' . $nds . '",
				active = "' . $active . '",
				sort = "' . $sort . '"'
    );
  }

  function getBarTitles($only_active = false)
  {
    $items = array();
    $link  = mysql_query(
      'select * from ' . DB_TABLE_PREFIX . 'reservations_bar_titles ' . ($only_active ? ' where active = \'1\'' : '') . ' order by sort'
    );
    if (mysql_num_rows($link) > 0) {
      //получаем строки таблицы
      while ($row = mysql_fetch_assoc($link)) {
        $items[] = $row;
      }

      return $items;
    }

    return false;
  }

  function getBarTitleById($id, &$item)
  {
    $item = array();

    $link = mysql_query('select * from ' . DB_TABLE_PREFIX . 'reservations_bar_titles where id = "' . $id . '"');

    if (mysql_num_rows($link) > 0) {
      //получаем строку таблицы
      $item = mysql_fetch_assoc($link);

      return true;
    } else {
      return false;
    }
  }


  //удалить
  function removeBarTitleById($id)
  {
    mysql_query('delete from ' . DB_TABLE_PREFIX . 'reservations_bar_titles where id = "' . $id . '"');

    return true;
  }

  //изменить
  function changeBarTitleById($id, $title, $nds)
  {
    mysql_query(
      'update ' . DB_TABLE_PREFIX . 'reservations_bar_titles 
				set 
					title = "' . addslashes($title) . '",
					nds = "' . $nds . '"
				where id = "' . $id . '"'
    );

    return true;
  }
}

?>