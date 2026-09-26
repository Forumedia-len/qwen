<?php

$admin = [];

//спеццена
$admin['spec_prices']['parent_key'] = 'config_';
$admin['spec_prices']['template']   = 'internal';
$admin['spec_prices']['title']      = lang('config_spec_price_title', 'structure');
$admin['spec_prices']['href']       = 'specPrices';
$admin['spec_prices']['sort']       = 600;

//$admin['spec_prices']['parent_key']   = 'config_spec_price';
//$admin['spec_prices']['template']     = 'internal';
//$admin['spec_prices']['title']        = lang('config_spec_price_title', 'structure');
//$admin['spec_prices']['href']         = 'specPrices';
//$admin['spec_prices']['content_menu'] = true;
//$admin['spec_prices']['sort']         = 100;
//$admin['spec_prices']['visible']      = config('stocksGroups')->showMenuStocksGroup();

//$admin['stocks_groups']['parent_key']   = 'config_stocks';
//$admin['stocks_groups']['template']     = 'internal';
//$admin['stocks_groups']['title']        = lang('stocks_groups', 'structure');
//$admin['stocks_groups']['href']         = 'stocks/groups';
//$admin['stocks_groups']['content_menu'] = true;
//$admin['stocks_groups']['sort']         = 200;
//$admin['stocks_groups']['visible']      = config('stocksGroups')->showMenuStocksGroup();

return $admin;