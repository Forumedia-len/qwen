<?php

namespace AC\core\system\object\entity;

class Entity
{
  private array   $_properties = [];
  private ?string $name;
  
  public function __construct($name = null, $properties = [])
  {
    $this->name = $name;
    $this->addProperties($properties);
    $this->instance();
  }
  
  protected function instance(): void
  {
  
  }
  
  public function getName(): ?string
  {
    return $this->name;
  }
  
  /**
   *  Задать свойства
   *
   * @param object|array|string $properties - массив свойств объекта, свойства задаются null
   */
  public function addProperties(object|array|string $properties): Entity
  {
    $properties = (array)$properties;
    foreach ($properties as $property => $value) {
      if (is_numeric($property)) {
        $this->addProperty($value);
      } else {
        $this->addProperty($property, $value);
      }
    }
    
    return $this;
  }
  
  /**
   *  Добавить свойство к объекту или обновить, и задать его значение если нужно, по умолчанию null
   *
   * @param $name
   * @param $value
   */
  public function addProperty($name, $value = null): Entity
  {
    $this->$name = $value;
    
    return $this;
  }
  
  /**
   * Получить все имена свойств
   * @return array - массив названий свойств массива
   */
  public function getPropertyNames(): array
  {
    return array_keys($this->_properties);
  }
  
  /**
   * @param $propertyName
   * @param $propertyValue
   * @return Entity
   */
  public function __set($propertyName, $propertyValue)
  {
    $this->_properties[$propertyName] = $propertyValue;
    
    return $this;
  }
  
  /**
   * @param $propertyName
   * @return mixed|null
   */
  public function __get($propertyName)
  {
    if ($propertyName == 'name') {
      return $this->getName();
    }
    return $this->_properties[$propertyName] ?? null;
  }
  
  /**
   * @param string $propertyName
   * @return bool
   */
  public function __isset(string $propertyName): bool
  {
    return $this->isset($propertyName);
  }
  
  
  /**
   *  Проверка существования свойства объекта
   *
   * @param $propertyName
   *
   * @return bool
   */
  public function isset($propertyName): bool
  {
    return property_exists($this, $propertyName) || array_key_exists($propertyName, $this->_properties);
  }
  
  public function setName(?string $name): Entity
  {
    $this->name = $name;
    
    return $this;
  }
  
  public function toArray(): array
  {
    return array_merge($this->_properties, ['name' => $this->getName()]);
  }
}