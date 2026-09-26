<?php

namespace AC\core\modules\news\engines;

use AC\core\system\db\Query;
use AC\core\system\engine\BaseEngine;


class NewsEngine extends BaseEngine
{
  public function tableName()
  {
    return 'news_simplest';
  }
  
  function getNewsItems(&$result = [], $conditions = [], $_order = ['date desc'], $params = [])
  {
    $result    = [];
    $limit     = isset($params['limit']) ? (int)$params['limit'] : 0;
    $paramsDto = $params;
    if ($limit > 0) {
      unset($paramsDto['limit']);
    }
    $query  = 'select * from ' . Query::tableName($this->tableName());
    $query  .= !empty($conditions) ? ' where ' . implode(' and ', $conditions) : '';
    $query  .= !empty($_order) ? ' order by ' . implode(', ', $_order) : '';
    if ($limit > 0) {
      $query .= ' limit ' . $limit;
    }
    $items  = Query::sqlQuery($query);
    if (!empty($items)) {
      $result = $this->getDataAs($items, $paramsDto);
      
      return true;
    }
    return false;
  }
  
  //новость по ID
  function getNewsItemById($news_id, &$item)
  {
    return $item = Query::sqlQuery(
      'select *
			from ' . Query::tableName($this->tableName()) . '
			where news_id = ' . $news_id, [], true, ['onlyOne' => true]
    );
  }
  
  //удалить новость
  function removeNewsItemById($news_id)
  {
    $where = '';
    if (count(config('lang')->getActiveLanguages()) > 1) {
      $where .= ' OR main_news_id = "' . $news_id . '"';
    }
    return Query::sqlQuery(
      'DELETE FROM ' . Query::tableName($this->tableName()) . '
			WHERE news_id = "' . $news_id . '"' . $where, [], false
    );
  }
  
  //изменить новость
  public function changeNewsItemById($news_id, $date, $title, $content, $publish, $language = null, $main_news_id = null): bool
  {
    $temp = Query::sqlQuery(
      'select *
			from ' . Query::tableName($this->tableName()) . '
			where news_id = ' . $news_id
    );
    if (!empty($temp)) {
      //новость найдена
      $addSet           = $language ? ', language = "' . addslashes($language) . '"' : '';
      $addSet           .= $main_news_id !== null ? ', main_news_id = ' . (int)$main_news_id : '';
      
      Query::sqlQuery(
        'update ' . Query::tableName($this->tableName()) . '
				set
					date = "' . $date . '",
					title = "' . addslashes($title) . '",
					content = "' . addslashes($content) . '",
					publish = "' . $publish . '"' . $addSet . '
				where news_id = ' . $news_id,
        [], false
      );
      
      return true;
    } else {
      //новость не найдена
      return false;
    }
  }
  
  //добавить новость
  function insertNewsItem($date, $title, $content, $publish, &$news_id, $language = null, $main_news_id = null)
  {
    $addSet           = $language ? ', language = "' . addslashes($language) . '"' : '';
    $addSet           .= $main_news_id !== null ? ', main_news_id = ' . (int)$main_news_id : '';
    
    Query::sqlQuery(
      'insert into ' . Query::tableName($this->tableName()) . '
			set
				date = "' . $date . '",
				title = "' . addslashes($title) . '",
				content = "' . addslashes($content) . '",
				publish = "' . $publish . '"' . $addSet ,
      [], false);
    
    $news_id = Query::getLastId();
    
    return true;
  }
  
}