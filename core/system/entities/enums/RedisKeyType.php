<?php

namespace AC\core\system\entities\enums;

enum RedisKeyType: int
{
  case NONE   = 0;
  case STRING = 1;
  case SET    = 2;
  case LIST   = 3;
  case ZSET   = 4;
  case HASH   = 5;

  /**
   * Получить строковое представление типа.
   */
  public function getName(): string
  {
    return match ($this) {
      self::NONE   => 'none',
      self::STRING => 'string',
      self::SET    => 'set',
      self::LIST   => 'list',
      self::ZSET   => 'zset',
      self::HASH   => 'hash',
    };
  }

  /**
   * Создать enum из числового значения Redis.
   */
  public static function fromInt(int $type): self
  {
    return match ($type) {
      1       => self::STRING,
      2       => self::SET,
      3       => self::LIST,
      4       => self::ZSET,
      5       => self::HASH,
      default => self::NONE,
    };
  }

  /**
   * Проверить, существует ли ключ (тип не NONE).
   */
  public function exists(): bool
  {
    return $this !== self::NONE;
  }

  // ==========================================
  // Методы проверки типа
  // ==========================================

  public function isString(): bool
  {
    return $this === self::STRING;
  }

  public function isHash(): bool
  {
    return $this === self::HASH;
  }

  public function isList(): bool
  {
    return $this === self::LIST;
  }

  public function isSet(): bool
  {
    return $this === self::SET;
  }

  public function isZSet(): bool
  {
    return $this === self::ZSET;
  }

  /**
   * Проверка, является ли тип коллекцией (список, множество, хэш).
   */
  public function isCollection(): bool
  {
    return in_array($this, [self::HASH, self::LIST, self::SET, self::ZSET], true);
  }

  /**
   * Проверка, поддерживает ли тип команду TTL.
   */
  public function supportsTTL(): bool
  {
    return $this !== self::NONE;
  }
}