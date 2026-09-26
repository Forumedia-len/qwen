<?php

namespace AC\core\engines;

use AC\core\system\object\entity\format\Json;
use Service;
use AC\core\system\db\Query;

/** Preferences.
 *  Новое поле preferences для различных таблиц.
 *  Формат хранения json.
 *  Если его в таблице нет создается автоматически
 */
class PreferencesEngine
{
  private string $tableName;
  private string $primaryKey = 'id';
  private string $fieldName  = 'preferences';

  public function init(): PreferencesEngine
  {
    if (!$this->checkField()) {
      $this->installField();
    }

    return $this;
  }

  /** Проверяем есть ли такое поле в таблице
   *
   * @return bool
   */
  private function checkField(): bool
  {
    return Query::getDB()->checkField($this->getFieldName(), $this->tableName());
  }

  /**
   *  Если такого поля нет в данной таблице пробуем его инсталлировать.
   *  * @return void
   */
  protected function installField()
  {
    Query::sqlQuery('ALTER TABLE ' . Query::tableName($this->tableName()) . ' ADD COLUMN `preferences` text DEFAULT NULL;');
  }

  public function getPreference($id, $key)
  {
    return $this->getPreferences($id)->getItem($key);
  }

  public function setPreference($id, $key, $value)
  {
    return $this->setPreferences($id, $this->getPreferences($id)->setItem($key, $value));
  }

  public function delPreference($id, $key)
  {
    return $this->setPreferences($id, $this->getPreferences($id)->unsetItem($key));
  }

  public function getPreferences($id): Json
  {
    $q = 'select preferences from ' . Query::tableName($this->tableName()) . ' where `' . $this->getPrimaryKey() . '`="' . $id . '"';

    return Service::formatData('json')->set(Query::sqlQuery($q, [], true, ['onlyOne' => true])['preferences']);
  }

  private function setPreferences($id, Json $preferences): bool
  {
    return Query::sqlQuery('update ' . Query::tableName($this->tableName())
      . ' set preferences=\'' . addslashes($preferences->get())
      . '\' where `' . $this->getPrimaryKey() . '`=\'' . $id . '\'', [], false);
  }

  public function setTableName(string $tableName): PreferencesEngine
  {
    $this->tableName = $tableName;
    return $this;
  }

  public function tableName(): string
  {
    return $this->tableName;
  }

  public function getPrimaryKey(): string
  {
    return $this->primaryKey;
  }

  public function setPrimaryKey(string $primaryKey): PreferencesEngine
  {
    if ($primaryKey == 'id'
      && ($key = array_keys(Query::getDB()->getPrimaryKeys($this->tableName()))[0])
      && $key != 'id') {
      $primaryKey = $key;
    }
    $this->primaryKey = $primaryKey;

    return $this;
  }

  public function getFieldName(): string
  {
    return $this->fieldName;
  }

  public function setFieldName(string $fieldName): PreferencesEngine
  {
    $this->fieldName = $fieldName;

    return $this;
  }
}