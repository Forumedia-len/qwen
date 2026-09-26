<?php

namespace AC\core\modules\config\scoped;

enum ScopedConfigPolicy
{
  /** payment / BindingConfigTypeHelper — unprefixed type fallback. */
  case DEFAULT;
  /** double game — lowercase alias fallback, bare prefix. */
  case DOUBLE;
  /** open type — unprefixed fallback. */
  case OPEN_TYPE;
  /** client restriction — alias chain, bare prefix. */
  case RESTRICTION;

  /** Пробовать alias в нижнем регистре при промахе (double). */
  public function tryLowercaseFallback(): bool
  {
    return match ($this) {
      self::DOUBLE => true,
      default      => false,
    };
  }

  /** Fallback на ключ только с prefix (restriction|close → restriction). */
  public function fallbackBarePrefix(): bool
  {
    return match ($this) {
      self::DOUBLE, self::RESTRICTION => true,
      default => false,
    };
  }

  /** Fallback на type без prefix (open, payment). */
  public function fallbackUnprefixedType(): bool
  {
    return match ($this) {
      self::DEFAULT, self::OPEN_TYPE => true,
      default => false,
    };
  }

  /** Нужна ли цепочка alias (param__club → param) при чтении. */
  public function usesAliasChain(): bool
  {
    return $this === self::RESTRICTION;
  }

  /** Имя type для fallback при каскаде ConfigDbValueSelector. */
  public function fallbackType(): string
  {
    return 'default';
  }
}
