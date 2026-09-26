<?php

namespace AC\app\config;

use AC\core\system\config\BaseConfig;

class StocksConfig extends BaseConfig
{
  /**
   * Использовать ли для всех типов сортов
   *
   * @return bool
   */
  public function useForAllSorts(): bool
  {
    return defined('USE_OPTION_AND_SPEC_PRICE_FOR_ALL_TYPES') && (USE_OPTION_AND_SPEC_PRICE_FOR_ALL_TYPES['stocks'] ?? USE_OPTION_AND_SPEC_PRICE_FOR_ALL_TYPES);
  }
}