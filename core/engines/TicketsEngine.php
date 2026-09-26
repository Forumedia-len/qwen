<?php

namespace AC\core\engines;

use AC\app\services\DataService;
use AC\core\modules\tickets\entities\enums\TicketDisabledTimeSource;
use AC\core\system\autoloader\Autoloader;
use AC\core\system\db\Query;

useClass('engines\reservation.tickets');

class TicketsEngine extends \reservation_tickets
{

  public function addArchive($ticket_data, $periods_data)
  {
    $query = 'insert into ' . Query::$prefix . 'tickets_deleted (old_ticket_id, ticket_data, periods_data, date_delete) values (' . $ticket_data['ticket_id'] . ', \'' . addslashes(json_encode(
        $ticket_data
      )) . '\', \'' . addslashes(json_encode($periods_data)) . '\', \'' . date('Y-m-d H:i') . '\')';
    Query::sqlQuery($query, array(), false);
  }


  public function getPriceInfoAndCountGame($ticket_id, &$price_info = null, &$count_game = null)
  {
    $areas = new AreasEngine(1);
    if ($this->getTicketFullDataWithPeriodsAndPriceById($ticket_id, $ticket_data, $periods_data)) {
      foreach ($periods_data as $k => $pd) {
        if (is_array($pd['count_game'])) {
          foreach ($pd['count_game'] as $weekday => $games) {
            if (is_array($games)) {
              foreach ($games as $g_date => $t_periods) {
                if (is_array($t_periods)) {
                  foreach ($t_periods as $start => $count) {
                    if ($count) {
                      if ($p_id = $areas->getPeriodByDate($g_date)) {
                        $periods_data[$k]['sum_price']                                      += $pd['price'][$weekday][$start][$p_id];
                        $price_info[$weekday][$g_date][$start]['price']                     = $pd['price'][$weekday][$start][$p_id];
                        $price_info[$weekday][$g_date][$start]['extra']                     = $ticket_data['extra'];
                        $price_info[$weekday][$g_date][$start]['discount_client']           = $ticket_data['discount_client'];
                        $price_info[$weekday][$g_date][$start]['discount_client_dimension'] = $ticket_data['discount_client_dimension'];
                        $price_info[$weekday][$g_date][$start]['discount_ticket']           = $ticket_data['discount_ticket'];
                        $price_info[$weekday][$g_date][$start]['discount_ticket_dimension'] = $ticket_data['discount_ticket_dimension'];
                      }
                    }
                  }
                }
              }
            }
          }
        }
        $count_game[date('Y-m-d', strtotime($pd['start']))] = $pd['count_game'];
      }
    }
  }

  /**
   * Возвращает оплаченные отменённые часы абонементов клиента.
   *
   * @param int $client_id
   *
   * @return array<int, array<string, mixed>>
   */
  public function getArchTicketsByUser(int $client_id): array
  {
    $sourceCondition = $this->hasDisabledTimeSource()
      ? ' AND tdt.source_type = ' . TicketDisabledTimeSource::Cancellation->value
      : '';
    $q = 'SELECT tdt.*,CAST(CONCAT(tdt.disabled_date, " ", tdt.disabled_time) AS DATETIME) AS start,
      DATE_ADD(
            CAST(CONCAT(tdt.disabled_date, " ", tdt.disabled_time) AS DATETIME),
            INTERVAL a.period MINUTE
        ) AS finish,
    t.*,an.title as type_title,a.title as area_title
    FROM ' . Query::tableName('tickets_disabled_time') . ' tdt
    INNER JOIN ' . Query::tableName('tickets') . ' t ON tdt.ticket_id = t.ticket_id
    INNER JOIN ' . Query::tableName('areas') . ' a ON a.area_id = t.area_id
    INNER JOIN ' . Query::tableName('areas_types') . ' an ON an.type_id = a.type_id
    WHERE t.client_id = ? AND tdt.price > 0' . $sourceCondition . ' ORDER BY start DESC;';

    return Query::sqlQuery($q, [$client_id]);
  }

}
