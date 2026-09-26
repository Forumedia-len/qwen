<?php

namespace AC\core\modules\areas\config;

use AC\core\system\config\BaseConfig;

class TypeAreasConfig extends BaseConfig
{
  public function currentTypeAlias(int $type_id): string
  {
    return module('areas')->useModel()?->getEngine()?->getAliasType($type_id, true);
  }
}