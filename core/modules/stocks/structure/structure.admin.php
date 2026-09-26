<?php
$admin = [];

//настройки акций
$admin['config_stocks']['parent_key'] = 'config_';
$admin['config_stocks']['template']   = 'internal';
$admin['config_stocks']['title']      = lang('config_stocks_title', 'structure');
$admin['config_stocks']['href']       = 'stocks';
$admin['config_stocks']['sort']       = 500;

$admin['stocks']['parent_key']   = 'config_stocks';
$admin['stocks']['template']     = 'internal';
$admin['stocks']['title']        = lang('config_stocks_title', 'structure');
$admin['stocks']['href']         = 'stocks';
$admin['stocks']['content_menu'] = true;
$admin['stocks']['sort']         = 100;
$admin['stocks']['visible']      = config('stocksGroups')->showMenuStocksGroup();

$admin['stocks_groups']['parent_key']   = 'config_stocks';
$admin['stocks_groups']['template']     = 'internal';
$admin['stocks_groups']['title']        = lang('stocks_groups', 'structure');
$admin['stocks_groups']['href']         = 'stocks/groups';
$admin['stocks_groups']['content_menu'] = true;
$admin['stocks_groups']['sort']         = 200;
$admin['stocks_groups']['visible']      = config('stocksGroups')->showMenuStocksGroup();

return $admin;