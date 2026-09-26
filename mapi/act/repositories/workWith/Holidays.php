<?php

namespace AC\mapi\act\repositories\workWith;

use AC\core\system\db\CompareDB;
use AC\mapi\act\repositories\BaseRepository;

class Holidays extends BaseRepository
{
  private string $tableName = 'holidays';

  protected function process(): void
  {
    $compareHolidays = CompareDB::compareTwoTables(
      $this->db->getStructureTable($this->tableName),
      $this->baseDB->getStructureTable($this->tableName));
    if (isset($compareHolidays->columns)) {
      $this->query[] = $this->gQDBS->generateUpdateTable($this->tableName, $compareHolidays);
      $this->updateSundayPrices();
    }
  }

  public function updateSundayPrices(): self
  {
    $columns = $this->db->getStructureTable($this->tableName)->columns ?? [];
    if (isset($columns['sunday']) && !isset($columns['sunday_prices'])) {
      $this->query[] = $this->gQDBS->generateUpdateData($this->tableName, [
        'set'   => ['sunday_prices' => 1],
        'where' => ['sunday' => 1],
      ]);
    }

    return $this;
  }
}