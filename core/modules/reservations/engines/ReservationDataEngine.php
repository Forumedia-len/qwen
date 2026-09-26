<?php

namespace AC\core\modules\reservations\engines;

use AC\core\engines\MetaDataEngine;
use AC\core\modules\reservations\entities\dto\ReservationDataDto;
use AC\core\system\db\Query;

class ReservationDataEngine extends MetaDataEngine
{
  protected string $typeBlock = 'reservation';
  
  public function setTableName($table_name = null)
  {
    $table_name = $table_name ?? 'reservation_data';
    
    parent::setTableName($table_name);
  }
  
  /**
   * Добавляем данные по бронированию
   *
   * @param array<ReservationDataDto> $data
   * @param ?int                      $reservationId
   * @param ?int                      $tmpReservationId
   * @return bool
   */
  public function insertReservationData(array $data, ?int $reservationId = null, ?int $tmpReservationId = null): bool
  {
    $values = $params = [];
    foreach ($data as $item) {
      $values[] = '(?, ?, ?, ?, ?, ?)';
      $params[] = [
        $reservationId,
        $tmpReservationId,
        $item->typeBlock,
        $item->entryId,
        $item->name,
        $item->value,
      ];
    }
    if (!empty($values)) {
      $q = 'INSERT INTO ' . Query::tableName($this->tableName()) . ' (`reservation_id`, `tmp_reservation_id`, `type_block`, `entry_id`, `name`, `value`) VALUES ' . implode(', ',
          $values) . ';';
      return Query::sqlQuery($q, array_merge(...$params), false);
    }
    return false;
  }
  
  /**
   * Удалим все данные, если бронирование не было оплачено по paypal
   *
   * @param int $reservationId
   * @return bool
   */
  public function removeReservationData(int $reservationId): bool
  {
    //Удаляем все данные по бронированию и не связанные с временными бронированиями по paypal
    return Query::sqlQuery('DELETE FROM ' . Query::tableName($this->tableName()) . ' WHERE reservation_id = ? AND tmp_reservation_id IS NULL;',
      [$reservationId], false);
  }
  
  /**
   * Получаем данные по бронированию
   *
   * @param int   $reservationId
   * @param bool $asTmp - ключ для временных бронирований или основных
   * @return array
   */
  public function getReservationData(int $reservationId, bool $asTmp = false): array
  {
    $out = [];
    $q   = 'SELECT * FROM ' . Query::tableName($this->tableName()) . ' WHERE ' . ($asTmp ? 'tmp_' : '') . 'reservation_id = ?;';
    foreach (Query::sqlQuery($q, [$reservationId]) as $row) {
      $out[$row['type_block']][$row['entry_id']][$row['name']] = $row['value'];
    }
    
    return $out;
  }
  
  /**
   * Добавляем постоянный id бронирования, если бронирование было оплачено по paypal
   *
   * @param $reservationId
   * @param $tmpReservationId
   * @return bool
   */
  public function setReservationId($reservationId, $tmpReservationId): bool
  {
    return Query::sqlQuery('UPDATE ' . Query::tableName($this->tableName()) . ' SET reservation_id = ? WHERE tmp_reservation_id = ?;',
      [$reservationId, $tmpReservationId], false);
  }
}