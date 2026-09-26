<?php

namespace AC\core\system\session;

trait SessionValueAsArray
{
  private string $sessionKey;

  public function hasValidSessionKey(): bool
  {
    foreach (self::validKeys() as $sessionKey) {
      if (parent::exists($sessionKey) && !empty(parent::get($sessionKey))) {
        return true;
      }
    }

    return false;
  }

  public function __construct($sessionKey = null)
  {
    parent::__construct();
    $this->setSessionKey($sessionKey);
  }

  public function validKeys(): array
  {
    return [];
  }

  public function sessionKey(?string $sessionKey = null): string
  {
    $classNameArray = explode('\\', get_class($this));

    return $sessionKey ?? ($this->sessionKey ?? end($classNameArray));
  }

  /**
   * Устанавливает имя параметра сессии
   */
  public function setSessionKey(?string $sessionKey): self
  {
    if ($this->hasValidSessionKey()) {
      foreach ($this->validKeys() as $validKey) {
        if (parent::exists($validKey) && !empty(parent::get($validKey))) {
          $this->sessionKey = $validKey;
          break;
        }
      }
    } elseif (!empty($sessionKey)) {
      $this->sessionKey = $sessionKey;
    }

    return $this;
  }

  /**
   * Получает значение из сессии по ключу
   *
   * @param string      $name Ключ параметра
   * @param string|null $sessionKey
   *
   * @return mixed Значение или null
   */
  public function get(string $name, ?string $sessionKey = null): mixed
  {
    return parent::get($this->sessionKey($sessionKey))[$name] ?? null;
  }

  /**
   * Устанавливает значение в сессию
   *
   * @param string      $name
   * @param mixed       $value Значение
   * @param string|null $sessionKey
   */
  public function set(string $name, mixed $value = null, ?string $sessionKey = null): void
  {
    $sessionKey         = $this->sessionKey($sessionKey);
    $sessionData        = parent::get($sessionKey) ?? [];
    $sessionData[$name] = $value;
    parent::set($sessionKey, $sessionData);
  }

  /**
   * Проверяет существование ключа в сессии
   *
   * @param string      $name Ключ параметра
   * @param string|null $sessionKey
   *
   * @return bool true, если ключ существует
   */
  public function exists(string $name, ?string $sessionKey = null): bool
  {
    $sessionKey = $this->sessionKey($sessionKey);

    return parent::exists($sessionKey) && isset(parent::get($sessionKey)[$name]);
  }

  /**
   * Удаляет ключ из сессии
   *
   * @param string      $name Ключ параметра
   * @param string|null $sessionKey
   *
   * @return bool
   */
  public function delete(string $name, ?string $sessionKey = null): bool
  {
    if ($this->exists($name)) {
      $sessionKey  = $this->sessionKey($sessionKey);
      $sessionData = parent::get($sessionKey);
      unset($sessionData[$name]);
      parent::set($sessionKey, $sessionData);

      return true;
    }

    return false;
  }

  /**
   * Удалить все данные текущей пользовательской сесcсии.
   *
   * @param string|null $sessionKey
   * @param bool        $fullUnset
   *
   * @return bool
   */
  public function unset(?string $sessionKey = null, bool $fullUnset = true): bool
  {
    if (!$fullUnset) {
      parent::set($this->sessionKey($sessionKey), []);

      return true;
    }

    return parent::delete($this->sessionKey($sessionKey));
  }

  public function unsetValidKeys(): void
  {
    foreach ($this->validKeys() as $validKey) {
      $this->unset($validKey);
    }
  }

  /**
   * Получает значение переменной сессии и удаляет её.
   *
   * @param string      $name Название переменной
   * @param string|null $sessionKey
   *
   * @return mixed Значение переменной или null, если не существует
   */
  public function getWithDelete(string $name, ?string $sessionKey = null): mixed
  {
    if ($return = $this->get($name, $sessionKey)) {
      $this->delete($name, $sessionKey);

      return $return;
    }

    return null;
  }
}