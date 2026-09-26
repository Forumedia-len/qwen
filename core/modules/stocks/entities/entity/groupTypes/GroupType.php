<?php

namespace AC\core\modules\stocks\entities\entity\groupTypes;

use AC\core\modules\stocks\entities\entity\StocksGroup;
use AC\core\system\object\entity\Entity;

/**
 * @property  string $title
 * @property string $name
 * @property string $description
 */
class GroupType extends Entity
{
  /**
   * @var string
   */
  public string $description;

  /**
   * @param StocksGroup $group
   * @return null
   */
  public function getCondition(StocksGroup $group)
  {
    return null;
  }

  /**
   * @param StocksGroup $group
   * @return string
   */
  public function getConditionAsHtml(StocksGroup $group): string
  {
    return '';
  }

  /**
   * @param StocksGroup $group
   * @param $error
   * @return bool
   */
  public function updateCondition(StocksGroup $group, &$error = null): bool
  {
    return true;
  }

  /**
   * @return string
   */
  public function getAsString(): string
  {
    return '';
  }
}