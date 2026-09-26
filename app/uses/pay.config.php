<?php
/**
 *  Использовать платежную систему payone
 *  заменяет paypal
 */
defined('USE_PAYONE_PAYMENT')       || define('USE_PAYONE_PAYMENT', false);
defined('PAYONE_MID')               || define('PAYONE_MID', '');
defined('PAYONE_AID')               || define('PAYONE_AID', '');
defined('PAYONE_PORTALID')          || define('PAYONE_PORTALID', '');
defined('PAYONE_SKEY')              || define('PAYONE_SKEY', '');
defined('PAYONE_MODE')              || define('PAYONE_MODE', 'live');
defined('PAYONE_ENCODING')          || define('PAYONE_ENCODING', 'UTF-8');
defined('PAYONE_VERSION')           || define('PAYONE_VERSION', '3.11');
defined('PAYONE_USE_URL_CREATION')  || define('PAYONE_USE_URL_CREATION', true);
defined('PAYONE_PARAM')             || define('PAYONE_PARAM', '');
