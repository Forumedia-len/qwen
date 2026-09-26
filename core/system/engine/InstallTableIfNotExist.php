<?php

namespace AC\core\system\engine;

use AC\core\system\db\DB;
use AC\core\system\db\GenerateQueryDB;

trait InstallTableIfNotExist
{
  /**
   * @var array Список таблиц для проверки
   */
  protected array $checkTables = [];
  
  /**
   * Добавляет таблицы в список для проверки
   *
   * @param string|array $tableNames Имя таблицы или массив имен
   * @return void
   */
  protected function setCheckTable(string|array $tableNames): void
  {
    $this->checkTables = array_merge($this->getCheckTables(), (array)$tableNames);
  }
  
  /**
   * Проверяет и устанавливает указанные таблицы
   *
   * @param string|array $tables Имя таблицы или массив имен
   * @return void
   */
  protected function checkAndInstallTables(string|array $tables = []): void
  {
    $tablesToCheck = array_unique(array_merge($this->getCheckTables(), (array)$tables));
    
    foreach ($tablesToCheck as $table) {
      if ($table && !$this->checkInstalledTable($table)) {
        $this->installTable($table);
      }
    }
  }
  
  /**
   * Возвращает список таблиц для проверки
   *
   * @return array
   */
  protected function getCheckTables(): array
  {
    return $this->checkTables;
  }
  
  /**
   * Проверяет существование таблицы в базе данных
   *
   * @param string $tableName Имя таблицы
   * @return bool
   */
  protected function checkInstalledTable(string $tableName): bool
  {
    return DB::instance()->getDbo()->checkTable($tableName);
  }
  
  /**
   * Устанавливает таблицу, если она отсутствует
   *
   * @param string $tableName Имя таблицы
   * @return void
   */
  protected function installTable(string $tableName): void
  {
    $baseDB = DB::instance('base');
    $siteDB = DB::instance();
    
    if (!$baseDB->getDbo()->checkTable($tableName)) {
      throw new \RuntimeException("The base table {$tableName} does not exist");
    }
    $queries = $this->generateCreateQueries($baseDB, $siteDB, $tableName);
    $this->updateLinkedTables($queries);
    $siteDB->executingAllQuery($queries, false);
  }
  
  /**
   * Генерирует SQL-запросы для создания таблицы и вставки данных
   *
   * @param DB     $baseDB    Базовая БД
   * @param DB     $siteDB    Целевая БД
   * @param string $tableName Имя таблицы
   * @return array Массив SQL-запросов
   */
  private function generateCreateQueries(DB $baseDB, DB $siteDB, string $tableName): array
  {
    $queries = [];
    $gQDBS   = new GenerateQueryDB($siteDB);
    
    // Создание структуры таблицы
    $queries[] = $baseDB->getDbo()->generateCreateTableAsString(
      $tableName,
      $siteDB->getDbo()->getPrefixTable(),
      $siteDB->getDbo()->getNameDB()
    );
    
    // Копирование данных, если они существуют
    $tableData = $baseDB->getDataTableByFieldsAndValues($tableName);
    
    if (!empty($tableData['values'])) {
      $queries[] = $gQDBS->generateInsertData($tableName, $tableData);
    }
    
    return $queries;
  }
  
  /**
   * Добавляет запросы для обновления связанных таблиц
   *
   * @param array &$queries Массив SQL-запросов
   * @return void
   */
  protected function updateLinkedTables(array &$queries): void
  {
  // используется в потомках
  }
}
