<?php

namespace AC\core\system\object\entity\triggerConditions;

use AC\core\system\helpers\ValidatorHelper;
use AC\core\system\object\entity\Entity;

class Condition extends Entity
{
  public ?int   $id          = null;
  public string $title;
  public string $description = '';
  public mixed  $value;
  public string $typeForm    = 'checkbox';
  
  public function asHtml(): string
  {
    return match ($this->typeForm) {
      'checkbox' => '<input type="checkbox" name="conditions[' . $this->getName() . ']"  class="input" value="1" ' . ($this->value ? 'checked' : '') . '>',
      default    => '',
    };
  }
  
  public function loadValue($value): void
  {
    $this->value = $this->validate($value);
  }
  
  public function validate($value)
  {
    foreach ($this->getRulesLoadValue() as $rule) {
      $value = match ($rule) {
        'bool'  => empty($value) ? 0 : (int)$value,
        default => $value
      };
    }
    
    return $value;
  }
  
  protected function getRulesLoadValue(): array
  {
    return $this->rules ?? [];
  }
}