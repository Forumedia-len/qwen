<?php

namespace AC\core\modules\stocks\config;

use AC\core\system\config\BaseConfig;
use AC\core\system\db\Query;
use Service;

class StocksGroupsConfig extends BaseConfig
{
  public function showMenuStocksGroup()
  {
    return (SHOW_GROUPS_STOCKS || Service::auth()->checkRights(0));
  }
}