<?php

namespace AC\app\config;

use AC\core\system\config\BaseConfig;

class DoorCodeConfig extends BaseConfig
{
  public function numberCharactersInDoorCodes(): int
  {
    return defined('NUMBER_CHARACTERS_IN_DOOR_CODES') && NUMBER_CHARACTERS_IN_DOOR_CODES ? NUMBER_CHARACTERS_IN_DOOR_CODES : 4;
  }
}