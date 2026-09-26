<?php

namespace AC\core\system\validators;

use AC\core\system\helpers\ValidatorHelper;
use AC\core\system\object\Entity;
use InvalidArgumentException;

/**
 * Адаптер модельных валидаторов для проверки произвольных массивов данных.
 */
class Validation extends Entity
{
  /** @var array<int, array{0: array<int, string>, 1: string, 2: array}> */
  private array $rules = [];

  /** @var array<string, string> */
  private array $labels = [];

  /** @var array<string, string|array<string, string>> */
  private array $customErrors = [];

  /** @var array<string, array<int, string>> */
  private array $validationErrors = [];

  /** @var array<string, mixed> */
  private array $validated = [];

  private ?string $currentAttribute = null;
  private ?string $currentRule = null;

  /**
   * Устанавливает правила валидации.
   *
   * Поддерживаются родной формат моделей и сокращённый формат по имени поля:
   *
   * <code>
   * [
   *   [['cell_id'], 'required'],
   *   [['cell_id'], 'integer', ['min' => 1]],
   * ]
   *
   * [
   *   'cell_id' => ['required', ['integer', ['min' => 1]]],
   * ]
   * </code>
   *
   * @param array $rules    Правила проверки
   * @param array $messages Пользовательские сообщения по полям и типам правил
   *
   * @return $this
   */
  public function setRules(array $rules, array $messages = []): static
  {
    $this->labels = [];
    $this->customErrors = [];
    $this->rules = array_is_list($rules)
      ? $this->normalizeModelRules($rules)
      : $this->normalizeFieldRules($rules);
    $this->customErrors = array_replace_recursive($this->customErrors, $messages);

    return $this;
  }

  /**
   * Добавляет правила одного поля в стиле CI Validation.
   *
   * @param string       $attribute Имя поля
   * @param string|null  $label     Подпись поля
   * @param string|array $rules     Правила поля
   * @param array        $errors    Сообщения по типам правил
   *
   * @return $this
   */
  public function setRule(
    string $attribute,
    ?string $label,
    string|array $rules,
    array $errors = []
  ): static {
    $this->rules = array_merge($this->rules, $this->normalizeFieldRules([
      $attribute => [
        'label'  => $label,
        'rules'  => $rules,
        'errors' => $errors,
      ],
    ]));

    return $this;
  }

  /**
   * Проверяет переданные данные установленными правилами.
   *
   * @param array<string, mixed>|null $data Данные или null для повторной проверки последних данных
   * @param bool $strict Строгая проверка HTTP-ввода с остановкой правил поля после ошибки
   *
   * @return bool
   */
  public function run(?array $data = null, bool $strict = false): bool
  {
    if ($data !== null) {
      $this->validated = $data;
    }

    $this->validationErrors = [];
    $this->_params = [];

    if ($this->rules === []) {
      return false;
    }

    foreach ($this->validated as $attribute => $value) {
      if (is_string($attribute)) {
        $this->{$attribute} = $value;
      }
    }

    foreach ($this->getRuleAttributes() as $attribute) {
      if (!$this->issetProperty($attribute)) {
        $this->{$attribute} = null;
      }
    }

    foreach ($this->rules as [$attributes, $type, $params]) {
      foreach ($attributes as $attribute) {
        $validator = ValidatorHelper::getValidator($type, $attribute, $params);
        if ($validator === null) {
          throw new InvalidArgumentException('Unknown validator type: ' . $type);
        }

        if ($strict && $this->hasErrors($attribute)) {
          continue;
        }
        if ($strict) {
          $validator->strict = true;
        }

        $this->currentAttribute = $attribute;
        $this->currentRule = $type;
        $validator->validateAttribute($this, $this->getAttributeLabel($attribute));
      }
    }

    foreach ($this->getRuleAttributes() as $attribute) {
      $this->validated[$attribute] = $this->{$attribute};
    }

    $this->currentAttribute = null;
    $this->currentRule = null;

    return !$this->hasErrors();
  }

  /**
   * Устанавливает подписи полей для сообщений об ошибках.
   *
   * @param array<string, string> $labels
   *
   * @return $this
   */
  public function setLabels(array $labels): static
  {
    $this->labels = $labels;

    return $this;
  }

  /**
   * Возвращает подпись поля.
   */
  public function getAttributeLabel(string $attribute, ?string $default = null): string
  {
    return $this->labels[$attribute] ?? $default ?? ucfirst($attribute);
  }

  /**
   * Возвращает имя условного первичного ключа для совместимости с модельными валидаторами.
   */
  public function getPrimaryKey(): string
  {
    return 'id';
  }

  /**
   * Возвращает поля, для которых установлен указанный тип правила.
   *
   * @return array<int, string>
   */
  public function getValidateRulesByType(string $type): array
  {
    $attributes = [];
    foreach ($this->rules as [$ruleAttributes, $ruleType]) {
      if ($ruleType === $type) {
        $attributes = array_merge($attributes, $ruleAttributes);
      }
    }

    return array_values(array_unique($attributes));
  }

  /**
   * Добавляет ошибку текущего правила к проверяемому полю.
   */
  public function addError($error = '', $attribute = null): void
  {
    $attribute = $attribute ?: $this->currentAttribute;
    if ($attribute === null) {
      return;
    }

    $customError = $this->customErrors[$attribute] ?? null;
    if (is_array($customError)) {
      $customError = $customError[$this->currentRule] ?? null;
    }

    $message = is_string($customError) && $customError !== '' ? $customError : (string)$error;
    if ($message !== '') {
      $this->validationErrors[$attribute][] = $message;
    }
  }

  /**
   * Возвращает ошибки без очистки состояния адаптера.
   *
   * @return array<string, array<int, string>>
   */
  public function getErrors(): array
  {
    return $this->validationErrors;
  }

  /**
   * Возвращает первую ошибку поля.
   */
  public function getError(string $attribute): ?string
  {
    return $this->validationErrors[$attribute][0] ?? null;
  }

  /**
   * Проверяет наличие ошибок.
   */
  public function hasErrors($attribute = null): bool
  {
    return $attribute === null
      ? $this->validationErrors !== []
      : isset($this->validationErrors[$attribute]);
  }

  /**
   * Очищает все ошибки или ошибки одного поля.
   */
  public function clearErrors($attribute = null): void
  {
    if ($attribute === null) {
      $this->validationErrors = [];
      return;
    }

    unset($this->validationErrors[$attribute]);
  }

  /**
   * Возвращает данные после преобразований валидаторов.
   *
   * @return array<string, mixed>
   */
  public function getValidated(): array
  {
    return $this->validated;
  }

  /**
   * Полностью очищает состояние shared-сервиса.
   *
   * @return $this
   */
  public function reset(): static
  {
    $this->rules = [];
    $this->labels = [];
    $this->customErrors = [];
    $this->validationErrors = [];
    $this->validated = [];
    $this->currentAttribute = null;
    $this->currentRule = null;
    $this->_params = [];

    return $this;
  }

  /**
   * @param array $rules
   *
   * @return array<int, array{0: array<int, string>, 1: string, 2: array}>
   */
  private function normalizeModelRules(array $rules): array
  {
    $normalized = [];
    foreach ($rules as $rule) {
      if (!is_array($rule) || !isset($rule[0], $rule[1]) || !is_string($rule[1])) {
        throw new InvalidArgumentException('Validation rule must specify fields and a validator type.');
      }

      $attributes = array_values(array_filter((array)$rule[0], 'is_string'));
      if ($attributes === []) {
        throw new InvalidArgumentException('Validation rule must specify at least one field.');
      }

      $normalized[] = [$attributes, $rule[1], (array)($rule[2] ?? [])];
    }

    return $normalized;
  }

  /**
   * @param array<string, string|array> $rules
   *
   * @return array<int, array{0: array<int, string>, 1: string, 2: array}>
   */
  private function normalizeFieldRules(array $rules): array
  {
    $normalized = [];
    foreach ($rules as $attribute => $fieldRules) {
      if (!is_string($attribute)) {
        throw new InvalidArgumentException('Validation rule field name must be a string.');
      }

      if (is_array($fieldRules) && array_key_exists('rules', $fieldRules)) {
        if (isset($fieldRules['label']) && is_string($fieldRules['label'])) {
          $this->labels[$attribute] = $fieldRules['label'];
        }
        if (isset($fieldRules['errors']) && is_array($fieldRules['errors'])) {
          $this->customErrors[$attribute] = $fieldRules['errors'];
        }
        $fieldRules = $fieldRules['rules'];
      }

      if (is_string($fieldRules)) {
        $fieldRules = array_values(array_filter(explode('|', $fieldRules)));
      } elseif (
        isset($fieldRules[0], $fieldRules[1])
        && is_string($fieldRules[0])
        && is_array($fieldRules[1])
        && ($fieldRules[1] === [] || !array_is_list($fieldRules[1]))
        && count($fieldRules) === 2
      ) {
        $fieldRules = [$fieldRules];
      }

      foreach ((array)$fieldRules as $rule) {
        if (is_string($rule)) {
          $normalized[] = [[$attribute], $rule, []];
          continue;
        }

        if (!is_array($rule) || !isset($rule[0]) || !is_string($rule[0])) {
          throw new InvalidArgumentException('Invalid validation rule for field: ' . $attribute);
        }

        $normalized[] = [[$attribute], $rule[0], (array)($rule[1] ?? [])];
      }
    }

    return $normalized;
  }

  /**
   * @return array<int, string>
   */
  private function getRuleAttributes(): array
  {
    $attributes = [];
    foreach ($this->rules as [$ruleAttributes]) {
      $attributes = array_merge($attributes, $ruleAttributes);
    }

    return array_values(array_unique($attributes));
  }
}
