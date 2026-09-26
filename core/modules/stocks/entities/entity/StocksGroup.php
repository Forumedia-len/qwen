<?php

namespace AC\core\modules\stocks\entities\entity;

use AC\core\modules\stocks\entities\entity\groupTypes\GroupType;
use AC\core\system\helpers\ObjectHelper;
use AC\core\system\object\entity\Entity;
use Service;

/**
 * @property int    $id
 * @property string $title
 * @property string $description
 * @property string $type
 * @property string $preferences
 * @property int    $active
 */
class StocksGroup extends Entity
{
  /**
   * @var array{GroupType}
   */
  protected array $types = [];
  
  public function typesBySelect($useFirstNull = true): string
  {
    $values = $useFirstNull ? [lang('no')] : [];
    
    /** @var GroupType $item */
    foreach ($this->types as $item) {
      $values[$item->name] = $item->title;
    }
    
    return useLayout()::render('select', [
      'name'    => 'type',
      'values'  => $values,
      'class'   => ' ',
      'current' => $this->type ?? null
    ], 'common');
    
  }
  
  public function setTypes(array $types): void
  {
    /** @var GroupType $type */
    foreach ($types as $type) {
      $this->types[$type->name] = $type;
    }
  }
  
  public function getType(): GroupType
  {
    return isset($this->type, $this->types[$this->type]) ? $this->types[$this->type] : ObjectHelper::createEntity('groupTypes\groupType');
  }
  
  public function getPreference($key = null)
  {
    if (!$key && $this->type) {
      $key = $this->getType()->name ?? $this->type;
    }
    
    return Service::formatData('json')->set($this->preferences)->getItem($key);
  }
  
  public function toArray(): array
  {
    $result                = parent::toArray();
    $result['preferences'] = Service::formatData('json')->set($this->preferences)->toArray();
    return $result;
  }
}