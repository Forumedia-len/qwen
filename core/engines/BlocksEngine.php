<?php

namespace AC\core\engines;

use AC\core\system\db\Query;

useClass('engines\reservation.blocks');

class BlocksEngine extends \reservation_blocks
{
  public function getBlocksAreas(&$areas)
  {
    $q = 'select type_id, type_title, sport_title, area_id, sport_id, area_title, cnt from 
        (select at.type_id, at.title as type_title, asp.title as sport_title, a.area_id, a.sport_id, a.title as area_title, count(b.block_id) as cnt, 
           at.sort as at_sort, asp.sort as asp_sort, a.sort as a_sort  
			from ' . Query::tableName('areas') . ' a 
			join ' . Query::tableName('areas_types') . ' at ON at.type_id IS NOT NULL
			join ' . Query::tableName('areas_sports') . ' asp ON asp.sport_id IS NOT NULL
			left join ' . Query::tableName('blocks') . ' b on b.area_id = a.area_id
			where a.type_id = at.type_id and b.area_id is not null and a.sport_id = asp.sport_id 
			group by a.area_id) t  
			order by at_sort, asp_sort, a_sort';

    $areas = Query::sqlQuery($q);
    if (!empty($areas)) {
      return true;
    } else {
      $areas = null;

      return false;
    }
  }

  public function getPeriodicalBlocksAreas(&$areas)
  {
    $q = 'select type_id, type_title, sport_title, area_id, sport_id, area_title, cnt from 
        (select at.type_id, at.title as type_title, asp.title as sport_title, a.area_id, a.sport_id, a.title as area_title, count(b.block_id) as cnt, 
           at.sort as at_sort, asp.sort as asp_sort, a.sort as a_sort  
			from ' . Query::tableName('areas') . ' a 
			join ' . Query::tableName('areas_types') . ' at ON at.type_id IS NOT NULL
			join ' . Query::tableName('areas_sports') . ' asp ON asp.sport_id IS NOT NULL
			left join ' . Query::tableName('blocks_periodical') . ' b on b.area_id = a.area_id
			where a.type_id = at.type_id and b.area_id is not null and a.sport_id = asp.sport_id 
			and unlimited_block=0 
			group by a.area_id) t  
			order by at_sort, asp_sort, a_sort';

    $areas = Query::sqlQuery($q);
    if (!empty($areas)) {
      return true;
    } else {
      $areas = null;

      return false;
    }
  }

  public function addArchive($blocks_data, $type_block = 'normal')
  {
    $query = 'insert into ' . Query::tableName(
        'blocks_deleted'
      ) . ' (old_block_id, type_block ,blocks_data, date_delete) values (:old_block_id, :type_block ,:blocks_data, :date_delete)';
    $param = [
      'old_block_id' => $blocks_data['block_id'],
      'type_block'   => $type_block,
      'blocks_data'  => json_encode($blocks_data),
      'date_delete'  => date('Y-m-d H:i')
    ];
    Query::sqlQuery($query, $param, false);
  }

  public function getBlocksDataByBlockIds($block_ids, $type = '')
  {
    $query = 'select * from ' . Query::tableName('blocks' . $type) . ' where block_id in( ' . implode(', ', $block_ids) . ')';

    return Query::sqlQuery($query, array(), true);
  }

}
