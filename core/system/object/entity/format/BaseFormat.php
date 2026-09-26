<?php

namespace AC\core\system\object\entity\format;

/**
 *
 */
abstract class BaseFormat implements FormatInterface
{
  /**
   * @var array
   */
  private array $_data = [];

  /**
   * Get
   *
   * @param array $params Additional param
   *
   * @return mixed
   */
  public function get(array $params = [])
  {
    return $this->_data;
  }

  /**
   * Set
   *
   * @param mixed $value Data
   * @param array $params Additional param
   *
   * @return mixed
   */
  public function set($value, array $params = []): BaseFormat
  {
    $this->_data = $value;

    return $this;
  }

  /**
   * @return $this
   */
  public function unset(): BaseFormat
  {
    $this->_data = [];

    return $this;
  }

  /**
   * @param $key
   * @return mixed|null
   */
  public function getItem($key)
  {
    return $this->_data[$key] ?? null;
  }

  /**
   * @param $key
   * @param $value
   * @return $this
   */
  public function setItem($key, $value = null): BaseFormat
  {
    $this->_data[$key] = $value;

    return $this;
  }

  /**
   * @param $key
   * @return $this
   */
  public function unsetItem($key): BaseFormat
  {
    if ($this->isset($key)) {
      unset($this->_data[$key]);
    }

    return $this;
  }

  /**
   * @param $key
   * @return bool
   */
  public function isset($key): bool
  {
    return isset($this->_data[$key]);
  }

  /**
   * @param $key
   * @param $value
   */
  public function __set($key, $value = null)
  {
    $this->setItem($key, $value);
  }

  /**
   * @param $key
   * @return mixed|null
   */
  public function __get($key)
  {
    return $this->getItem($key);
  }

  /**
   * @param string $key
   * @return bool
   */
  public function __isset(string $key): bool
  {
    return $this->isset($key);
  }

  /**
   * @param string $key
   */
  public function __unset(string $key)
  {
    $this->unsetItem($key);
  }
  
  
  public function toArray(): array
  {
    return $this->_data;
  }
}