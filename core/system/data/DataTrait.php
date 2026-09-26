<?php

namespace AC\core\system\data;

use AC\core\system\helpers\StringHelper;
use AC\core\system\modules\modComm\http\ModCommResponse;

/**
 * Трейт DataTrait
 *
 * Предоставляет функциональность для работы с данными запроса и алиасами ключей
 * добавляет дополнительные ключи в массив данных для упрощения работы с данными.
 * Не производит валидации и преобразований данных, а также проверок на хакерские атаки.
 */
trait DataTrait
{
  /**
   * @var array Данные, хранящиеся в запросе.
   */
  private array $data = [];
  
  /**
   * @var array Список используемых разделителей для генерации алиасов ключей.
   */
  private array $usedSeparators = ['_'];
  
  /**
   * Получить хранимые данные.
   *
   * @return array Данные запроса.
   */
  public function getData(): array
  {
    return $this->data;
  }
  
  /**
   * Получить значение из данных по ключу.
   *
   * @param string $key     Ключ, по которому ищется значение
   * @param mixed  $default Значение по умолчанию, если ключ не найден
   * @return mixed
   */
  public function getDataValue(string $key, mixed $default = null): mixed
  {
    return $this->data[$key] ?? $default;
  }
  
  /**
   * Проверить, содержит ли данные указанный ключ.
   *
   * @param string $key Ключ для проверки
   * @return bool
   */
  public function hasDataKey(string $key): bool
  {
    return array_key_exists($key, $this->data);
  }
  
  /**
   * Установить данные запроса.
   *
   * @param array      $data       Данные для сохранения.
   * @param array|null $separators Необязательный список разделителей для генерации алиасов ключей.
   * @return void
   */
  public function setData(array $data, ?array $separators = null): void
  {
    foreach ($data as $key => $value) {
      $this->setDataValue($key, $value, $separators);
    }
  }
  
  /**
   *  Добавить данные в запрос.
   *
   * @param string     $alias
   * @param mixed      $value
   * @param array|null $separators
   */
  public function addDataValue(string $alias, mixed $value, ?array $separators = null): self
  {
    $this->setDataValue($alias, $value, $separators);
    
    return $this;
  }
  
  /**
   * Установить конкретное значение данных и его алиасы.
   *
   * @param string     $alias      Алиас ключа.
   * @param mixed      $value      Значение для сохранения.
   * @param array|null $separators Необязательный список разделителей для генерации алиасов ключей.
   * @return void
   */
  private function setDataValue(string $alias, mixed $value, ?array $separators = null): void
  {
    foreach ($this->keyAliasesUsed($alias, $separators) as $key) {
      $this->data[$key] = $value;
    }
  }
  
  /**
   * Сгенерировать все алиасы ключей на основе заданных разделителей.
   *
   * @param string     $key        Оригинальный ключ.
   * @param array|null $separators Необязательный список разделителей для генерации алиасов ключей.
   * @return array Массив алиасов ключей.
   */
  public function keyAliasesUsed(string $key, ?array $separators = null): array
  {
    $keys = [];
    foreach ($separators ?? $this->getUsedSeparators() as $separator) {
      $keys[] = $this->generateKeyBySeparator($key, $separator);
    }
    
    return array_unique(array_merge(...$keys));
  }
  
  /**
   * Сгенерировать варианты ключей с использованием заданного разделителя.
   *
   * @param string $key       Оригинальный ключ.
   * @param string $separator Разделитель для преобразования (по умолчанию '_').
   * @return array Массив преобразованных ключей.
   */
  public function generateKeyBySeparator(string $key, string $separator = '_'): array
  {
    return [
      StringHelper::camelCaseToUnderscore($key, $separator),
      lcfirst(StringHelper::underscoreToCamelCase($key, $separator)),
    ];
  }
  
  /**
   * Получить список используемых разделителей.
   *
   * @return array Используемые разделители.
   */
  public function getUsedSeparators(): array
  {
    return $this->usedSeparators;
  }
  
  /**
   * Добавить данные к текущему ответу.
   *
   * @param array $data Данные для добавления
   */
  public function withData(array $data): self
  {
    $this->setData($data);
    
    return $this;
  }
  
}
