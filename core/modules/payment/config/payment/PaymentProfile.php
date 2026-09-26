<?php

namespace AC\core\modules\payment\config\payment;

use AC\core\modules\config\helpers\BindingConfigTypeHelper;
use AC\core\system\config\BaseConfig;

abstract class PaymentProfile extends BaseConfig implements PaymentProfileInterface
{

  public function initData(?array $params = []): PaymentProfileInterface
  {
    $typeKey = $this->priorityTypeKey($params);
    $data = $this->getAllProfileVariants()[$typeKey];
    foreach ($this->profileFields() as $field) {
      $this->$field = match ($field) {
        'typeKey' => $typeKey,
        default   => $data[$field] ?? $this->useDefault()[$field]
      };
    }

    return $this;
  }

  public function priorityTypeKey(?array $params = []): string
  {
    $variants = $this->getAllProfileVariants();
    foreach (BindingConfigTypeHelper::prioritiesByTypeKeyInConfig($params) as $key) {
      foreach ([$key, $this->baseTypeKey() . '|' . $key] as $bildKey) {
        if (array_key_exists($bildKey, $variants) && $variants[$bildKey][$this->baseTypeKey() . '_use']) {
          return $bildKey;
        }
      }
    }

    return $this->baseTypeKey();
  }

  public function getData(): array
  {
    $data = [];
    foreach ($this->profileFields() as $field) {
      $data[$field] = $this->$field ?? $this->useDefault()[$field];
    }
    return $data;
  }

  protected function getAllProfileVariants(?bool $all = false): array
  {
    static $profileVariants;
    $keyContext = basename($this->profileContext()::class);
    if (empty($profileVariants[$keyContext])) {
      $default = $this->useDefault();
      foreach ($this->profileContext()::listProfileRows() as $typeKey => $profileVariant) {
        foreach ($this->profileFields() as $field) {
          $profileVariants[$keyContext][$typeKey][$field] = $profileVariant[$field]['useValue']
            ?? ($profileVariant[$field]['value'] ?? $default[$field]);
        }
      }
      if (empty($profileVariants[$keyContext]) || !isset($profileVariants[$keyContext][$this->baseTypeKey()])) {
        $profileVariants[$keyContext][$this->baseTypeKey()] = $default;
      }
    }
    return $all ? $profileVariants : $profileVariants[$keyContext];
  }

  /**
   * Основные редактируемые поля профиля + правила валидации/нормализации.
   */
  public function profileFields(): array
  {
    return array_keys($this->profileContext()->profileFields());
  }

  public function isOperational(): bool
  {
    return $this->useProfile() && $this->checkData();
  }

  abstract protected function baseTypeKey(): string;

  abstract protected function useDefault(): array;
}