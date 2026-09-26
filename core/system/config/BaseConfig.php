<?php

namespace AC\core\system\config;

use AC\core\system\object\Entity;

class BaseConfig extends Entity
{
  private static array $_data = [];

  /**
   * @param $name
   * @param $value
   *
   * @return void
   */
  public function setProperty($name, $value = null): void
  {
    self::$_data[$name] = $value;
  }

  /**
   * @param $name
   *
   * @return void
   */
  public function getProperty($name)
  {
    return $this->issetProperty($name) ? self::$_data[$name] : null;
  }

  /**
   *  Проверка существования свойства объекта
   *
   * @param      $property
   * @param bool $checkNotNull
   *
   * @return bool
   */
  public function issetProperty($property, bool $checkNotNull = false): bool
  {
    return (array_key_exists($property, self::$_data) && (!$checkNotNull || !empty($this->getProperty($property))))
      || (parent::issetProperty($property) && (!$checkNotNull || !empty($this->$property)));
  }

}