<?php

namespace AC\core\system\handlers;

class FileRepository
{
  private string          $filePath;
  private array           $data   = [];
  private bool            $loaded = false;
  private JsonFileHandler $jsonHandler;

  public function __construct(string $filePath, JsonFileHandler $jsonHandler = null, ?bool $compactMode = null)
  {
    $this->filePath    = $filePath;
    $this->jsonHandler = $jsonHandler ?? new JsonFileHandler($compactMode);
  }

  /**
   * Загружает данные из файла, если они еще не загружены
   */
  private function load(): void
  {
    if (!$this->loaded) {
      $this->data   = $this->jsonHandler->read($this->filePath);
      $this->loaded = true;
    }
  }

  /**
   * Сохраняет данные в файл
   * @return bool Успешность операции
   */
  private function save(): bool
  {
    return $this->jsonHandler->write($this->filePath, $this->data);
  }

  /**
   * Получает значение по ключу
   *
   * @param string $key     Ключ в формате 'user.name' для вложенных элементов
   * @param mixed  $default Значение по умолчанию
   *
   * @return mixed Значение или значение по умолчанию
   */
  public function get(string $key, $default = null)
  {
    $this->load();
    $keys  = explode('.', $key);
    $value = $this->data;

    foreach ($keys as $k) {
      if (!is_array($value) || !array_key_exists($k, $value)) {
        return $default;
      }
      $value = $value[$k];
    }

    return $value;
  }

  /**
   * Устанавливает значение по ключу
   *
   * @param string $key   Ключ в формате 'user.name'
   * @param mixed  $value Значение для установки
   *
   * @return self
   */
  public function set(string $key, $value): self
  {
    $this->load();
    $keys    = explode('.', $key);
    $current = &$this->data;

    // Создаем промежуточные массивы при необходимости
    foreach (array_slice($keys, 0, -1) as $k) {
      if (!isset($current[$k]) || !is_array($current[$k])) {
        $current[$k] = [];
      }
      $current = &$current[$k];
    }

    // Устанавливаем значение
    $current[end($keys)] = $value;

    return $this;
  }

  /**
   * Удаляет элемент по ключу
   *
   * @param string $key Ключ в формате 'user.name'
   *
   * @return self
   */
  public function remove(string $key): self
  {
    $this->load();
    $keys    = explode('.', $key);
    $current = &$this->data;

    // Проходим по всем ключам, кроме последнего
    foreach (array_slice($keys, 0, -1) as $k) {
      if (!isset($current[$k]) || !is_array($current[$k])) {
        return $this; // Ключ не существует
      }
      $current = &$current[$k];
    }

    // Удаляем последний ключ
    unset($current[end($keys)]);

    return $this;
  }

  /**
   * Проверяет существует ли ключ
   *
   * @param string $key Ключ в формате 'user.name'
   *
   * @return bool
   */
  public function has(string $key): bool
  {
    return $this->get($key) !== null;
  }

  /**
   * Получает все данные
   * @return array
   */
  public function all(): array
  {
    $this->load();

    return $this->data;
  }

  /**
   * Устанавливает все данные
   *
   * @param array $data Новые данные
   *
   * @return self
   */
  public function setAll(array $data): self
  {
    $this->load();
    $this->data = $data;

    return $this;
  }

  /**
   * Очищает все данные
   * @return self
   */
  public function clear(): self
  {
    $this->load();
    $this->data = [];

    return $this;
  }

  /**
   * Сохраняет изменения в файл
   * @return bool Успешность операции
   */
  public function commit(): bool
  {
    return $this->save();
  }

  /**
   * Перезагружает данные из файла
   * @return self
   */
  public function refresh(): self
  {
    $this->loaded = false;
    $this->load();

    return $this;
  }

  /**
   * Возвращает путь к файлу
   * @return string
   */
  public function getFilePath(): string
  {
    return $this->filePath;
  }
}