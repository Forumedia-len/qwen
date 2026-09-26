<?php

namespace AC\core\system\entities\dto;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Базовый класс DTO, реализующий интерфейс DtoInterface.
 */
class Dto implements DtoInterface, JsonSerializable
{
  /**
   * Статический кэш для параметров конструктора.
   * @var array<class-string, array<array-key, mixed>>
   */
  private static array $constructorParamsCache = [];
  
  /**
   * Создаёт экземпляр DTO из массива данных.
   *
   * @param array $data Массив данных, должен содержать значения для всех публичных свойств
   * @return static
   * @throws InvalidArgumentException Если количество или типы данных не соответствуют ожидаемым
   */
  public static function fromArray(array $data): self
  {
    $className = static::class;
    
    // Кэшируем параметры конструктора
    if (!isset(self::$constructorParamsCache[$className])) {
      $reflection  = new \ReflectionClass($className);
      $constructor = $reflection->getConstructor();
      
      if (!$constructor) {
        throw new InvalidArgumentException('Constructor is missing');
      }
      
      $params = [];
      foreach ($constructor->getParameters() as $i => $param) {
        $name     = $param->getName();
        $type     = $param->getType()?->getName();
        $default  = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
        $required = !$param->isOptional();
        
        $params[$i] = [
          'name'     => $name,
          'type'     => $type,
          'default'  => $default,
          'required' => $required,
        ];
      }
      
      self::$constructorParamsCache[$className] = $params;
    } else {
      $params = self::$constructorParamsCache[$className];
    }
    
    $args            = [];
    $missingRequired = [];
    
    foreach ($params as $i => $paramInfo) {
      $value = $data[$i] ?? $paramInfo['default'];
      
      // Проверка обязательных параметров
      if ($paramInfo['required'] && !isset($data[$i])) {
        $missingRequired[] = $paramInfo['name'];
        continue;
      }
      
      // Валидация типов
      if ($paramInfo['type']) {
        $expectedType = $paramInfo['type'];
        $actualValue  = $value;
        
        if (is_array($actualValue)) {
          throw new InvalidArgumentException("Parameter '{$paramInfo['name']}' cannot be an array");
        }
        
        if ($expectedType === 'int') {
          if (!is_int($actualValue)) {
            throw new InvalidArgumentException("Parameter '{$paramInfo['name']}' must be an integer");
          }
        } elseif ($expectedType === 'string') {
          if (!is_string($actualValue)) {
            throw new InvalidArgumentException("Parameter '{$paramInfo['name']}' must be a string");
          }
        } elseif ($expectedType === 'float') {
          if (!is_float($actualValue)) {
            throw new InvalidArgumentException("Parameter '{$paramInfo['name']}' must be a float");
          }
        } elseif ($expectedType === 'bool') {
          if (!is_bool($actualValue)) {
            throw new InvalidArgumentException("Parameter '{$paramInfo['name']}' must be a boolean");
          }
        } elseif (class_exists($expectedType)) {
          if (!$actualValue instanceof $expectedType) {
            throw new InvalidArgumentException("Parameter '{$paramInfo['name']}' must be an instance of '$expectedType'");
          }
        } else {
          throw new InvalidArgumentException("Unknown type for parameter '{$paramInfo['name']}'");
        }
      }
      
      $args[] = $value;
    }
    
    if (!empty($missingRequired)) {
      throw new InvalidArgumentException("Missing required parameters: " . implode(', ', $missingRequired));
    }
    
    return new static(...$args);
  }
  
  /**
   * Преобразует объект DTO в ассоциативный массив.
   *
   * @return array
   */
  public function toArray(): array
  {
    $result = [];
    
    // Получаем список свойств, которые нужно сериализовать
    $serializableProperties = $this->getSerializableProperties();
    
    foreach ($serializableProperties as $property) {
      $result[$property] = $this->$property;
    }
    
    return $result;
  }
  
  /**
   * Возвращает данные объекта для сериализации в JSON.
   *
   * @return array
   */
  public function jsonSerialize(): array
  {
    return $this->toArray();
  }
  
  /**
   * Определяет, какие свойства должны быть сериализованы.
   * По умолчанию возвращаются все публичные свойства.
   *
   * @return array<string>
   */
  protected function getSerializableProperties(): array
  {
    return array_keys(get_object_vars($this));
  }
}

