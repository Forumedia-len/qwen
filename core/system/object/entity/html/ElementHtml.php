<?php

namespace AC\core\system\object\entity\html;

use AC\app\helpers\LayoutHelper;
use AC\core\system\object\entity\Entity;

/**
 * @todo переделать чтобы параметры были массивом а при запросе отдавали строку
 *
 */
class ElementHtml extends Entity
{
  public ?string $style           = null;
  public ?string $class           = null;
  public ?string $id              = null;
  public array   $otherAttributes = [];

  public function addOtherAttribute($name, $value): ElementHtml
  {
    $this->otherAttributes[$name] = $value;

    return $this;
  }

  public function getAttributes(): array
  {
    return ['style', 'class', 'id'];
  }

  public function getOtherAttributes(): array
  {
    $properties = [];
    foreach ($this->otherAttributes as $name => $value) {
      $properties[] = $this->attributeAsString($name, $value);
    }

    return $properties;
  }

  public function asString(): string
  {
    return '';
  }

  public function getAttributesAsString(): string
  {
    $attributes = array_merge($this->getAttributes(), $this->getOtherAttributes());
    foreach ($attributes as $key => $attribute) {
      if (property_exists($this, $attribute)) {
        $attribute = $this->attributeAsString($attribute);
      }
      if ($attribute == 'name') {
        $attribute = $this->attributeAsString($attribute, $this->getName());
      }
      $attributes[$key] = $attribute;

      if (empty($attribute)) {
        unset($attributes[$key]);
      }
    }

    return implode(' ', $attributes);
  }

  public function attributeAsString($name, $value = null): string
  {
    return LayoutHelper::attributeElementHtml($name, $value ?: $this->$name ?? null);
  }

}