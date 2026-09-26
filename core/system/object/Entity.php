<?php

namespace AC\core\system\object;



class Entity
{
  protected $_params    = array();
  private   $_errors    = array();
  private   $checkError = false;


  /** Добавить ошибку
   *
   * @param string $error
   * @param null   $attribute
   */
  public function addError($error = '', $attribute = null)
  {
    if ($attribute) {
      $this->_errors[$attribute][] = $error;
    } else {
      $this->_errors[] = $error;
    }
    $this->checkError = true;
  }

  /** Добавить ошибки
   *
   * @param array $errors
   * @param bool  $clear - очистить массив ошибок, по умолчанию добавляем ошибки к массиву
   */
  public function addErrors($errors = array(), $clear = false)
  {
    if ($errors) {
      if ($clear) {
        $this->_errors = $errors;
      } else {
        $this->_errors = array_merge($this->_errors, $errors);
      }
      $this->checkError = true;
    }
  }

  /** Получить все ошибки и очистить лог ошибок
   * @return mixed
   */
  public function getErrors()
  {
    $this->checkError = false;
    $error            = $this->_errors;
    unset($this->_errors);

    return $error;
  }

  /** Все ошибки для данного атрибута
   *
   * @param string $attribute
   *
   * @return mixed
   */
  public function getErrorsByAttribute($attribute)
  {
    return $this->_errors[$attribute];
  }

  /** Проверка на ошибки
   * @return bool
   */
  public function isErrors()
  {
    return $this->checkError;
  }

  /**
   * Removes errors for all attributes or a single attribute.
   *
   * @param string $attribute attribute name. Use null to remove errors for all attributes.
   */
  public function clearErrors($attribute = null)
  {
    if ($attribute === null) {
      $this->_errors = array();
    } else {
      unset($this->_errors[$attribute]);
    }
  }

  /**
   * Returns a value indicating whether there is any validation error.
   *
   * @param string|null $attribute attribute name. Use null to check all attributes.
   *
   * @return bool whether there is any error.
   */
  public function hasErrors($attribute = null)
  {
    return $attribute === null ? !empty($this->_errors) : isset($this->_errors[$attribute]);
  }

  /**
   *  Получить имя объекта
   * @return string
   */
  static public function className($full = true)
  {
    $fullClassName = get_called_class();
    $names = explode('\\', $fullClassName);

    return $full ? get_called_class() : $names[count($names) - 1];
  }

  public function __set($name, $value)
  {
    $this->_params[$name] = $value;
  }

  public function __get($name)
  {
    return isset($this->_params[$name]) ? $this->_params[$name] : null;
  }

  public function __isset(string $name): bool
  {
    return $this->issetProperty($name);
  }

  /**
   *  Задать свойства и заполнить их значениями
   *
   * @param array| string | object $properties - массив свойств объекта, свойства задаются null
   */
  public function addPropertiesAndValues($properties)
  {
    $properties = (array)$properties;
    foreach ($properties as $property => $value) {
      $this->addProperty($property, $value);
    }
  }

  /**
   *  Задать свойства
   *
   * @param array|string|object $properties - массив свойств объекта, свойства задаются null
   */
  public function addProperties($properties)
  {
    $properties = (array)$properties;
    foreach ($properties as $property => $value) {
      if(is_numeric($property)) {
        $this->addProperty($value);
      } else {
        $this->addProperty($property, $value);
      }
    }
  }

  /**
   *  Добавить свойство к объекту или обновить, и задать его значение если нужно, по умолчанию null
   *
   * @param $name
   * @param $value
   */
  public function addProperty($name, $value = null)
  {
    $this->$name = $value;
  }

  /**
   * Получить все имена свойств
   * @return array - массив названий свойств массива
   */
  public function getProperties()
  {
    return array_keys($this->_params);
  }

  /**
   * Задать свойства объекта, если их нет то сгенерировать
   *
   * @param $params
   */
  public function loadParams($params)
  {
    foreach ($params as $name => $value) {
      if ($this->issetProperty($name)) {
        $this->addProperty($name, $value);
      }
    }
  }

  /**
   *  Проверка существования свойства объекта
   *
   * @param $property
   *
   * @return bool
   */
  public function issetProperty($property)
  {
    return array_key_exists($property, $this->_params);
  }

}