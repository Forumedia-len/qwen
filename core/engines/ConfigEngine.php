<?php

namespace AC\core\engines;

use AC\core\system\db\Query;
use AC\core\system\engine\BaseEngine;
use PDO;

/** Класс с обращением к базе данных для работы с конфигом
 * Class ConfigEngine
 *
 * @todo добавить в базу столбец с типом значения (строка, число , json и т.д.) для его автоматического определения
 */
class ConfigEngine extends BaseEngine
{
  static private array $config = [];

  /** Забрать из базы весь конфиг и разрендерить его по типам, записать в статическую переменную
   *
   */
  private function renderConfig(): void
  {
    $configs = Query::sqlQuery(
      "select c.type, c.alias , c.value from " . Query::tableName('config') . " as c order by c.type",
      [],
      true,
      ['style' => PDO::FETCH_GROUP | PDO::FETCH_CLASS]
    );
    foreach ($configs as $type => $config) {
      $_config = [];
      foreach ($config as $value) {
        $_config[$value->alias] = $value->value;
      }
      self::$config[$type] = (object)$_config;
    }
  }

  /** Получить весь конфиг
   *
   * @param ?array $data
   * @param bool   $new
   *
   * @return bool
   */
  public function getDataConfig(?array &$data = [], $new = false): bool
  {
    if (empty(self::$config) || $new) {
      $this->renderConfig();
    }
    if (!empty(self::$config)) {
      $data = self::$config;

      return true;
    }

    return false;
  }

  public static function getConfig()
  {
    if (self::$config === null) {
      $config = new ConfigEngine();
      $config->renderConfig();
    }

    if (self::$config !== null) {
      return self::$config;
    }

    return [];
  }

  /** Получить переменные конфига по его типу
   *
   * @param      $type
   * @param bool $new
   *
   * @return bool|object|array
   */
  public function getConfigByType($type, $new = false)
  {
    if ($type) {
      $this->getDataConfig($config, $new);

      return isset($config[$type]) ? $config[$type] : false;
    }

    return false;
  }

  /** Получить все названия типов конфига
   *
   * @return array
   */
  public function getTypesConfig()
  {
    $this->getDataConfig($data);

    return array_keys($data);
  }

  /**
   *  Сохранить элемент конфига в базе данных
   *  если такой элемент существует то обновляем,
   *  если нет то добавляем.
   *
   * @param string $alias
   * @param string $value
   * @param string $type
   *
   * @return bool
   */
  public function saveItem(string $alias, string $value, string $type = 'common'): bool
  {
    if ((isset(self::$config[$type]->{$alias}) ? $this->updateItem($alias, $value, $type) : $this->insertItem($alias, $value, $type))) {
      if (!isset(self::$config[$type])) {
        self::$config[$type] = new \stdClass();
      }
      self::$config[$type]->{$alias} = $value;

      return true;
    }

    return false;
  }

  /** Обновить элемент конфига
   *
   * @param string $alias
   * @param string $value
   * @param string $type
   *
   * @return bool
   */
  public function updateItem($alias, $value, string $type = 'common')
  {
    return Query::sqlQuery(
      "update " . Query::tableName('config') . " set value='" . $value . "' where alias = '" . $alias . "' and type = '" . $type . "'",
      [],
      false
    );
  }

  /** Добавить элемент конфига
   *
   * @param string $alias
   * @param string $value
   * @param string $type
   *
   * @return bool
   */

  public function insertItem($alias, $value, $type = 'common')
  {
    return Query::sqlQuery(
      "insert into " . Query::tableName('config') . " set value='" . $value . "', alias = '" . $alias . "', type = '" . $type . "'",
      [],
      false
    );
  }

  static public function loadMinMaxParams(&$config)
  {
    $temp = Query::sqlQuery('SELECT * FROM ' . Query::tableName('areas_min_max'));
    foreach ($temp as $item) {
      $config['min_max'][$item['type_id']][$item['sport_id']] = $item;
    }
  }
}