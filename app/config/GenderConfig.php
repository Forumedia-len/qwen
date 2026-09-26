<?php

namespace AC\app\config;

use AC\core\system\config\BaseConfig;

class GenderConfig extends BaseConfig
{
  public function getGender(): array
  {
    return [
      'male' => lang('male'),
      'female' => lang('female'),
      'diverse' => lang('diverse'),
    ];
  }
}