<?php
defined('SHARED')      || define('SHARED', '/var/www/shared/');
defined('ROOT_PATH')   || define('ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
file_exists(SHARED_PATH . 'config.loc.php') && require_once SHARED_PATH .  'config.loc.php';

defined('HOST_NAME')     || define('HOST_NAME', 'base-active-court.de');
defined('BASE_HREF')     || define('BASE_HREF', 'https://'. HOST_NAME.'/');
defined('CDN_HREF')      || define('CDN_HREF', 'https://cdn.active-court.com/active-court/main/');
defined('SESSION_COURT') || define('SESSION_COURT', 'active_court_de');
defined('LOCAL_SERVER')  || define('LOCAL_SERVER', false);



//image upload define
defined('UPLOAD_URL') || define('UPLOAD_URL', BASE_HREF . 'uploads/'); //use http
defined('UPLOAD_DIR') || define('UPLOAD_DIR', ROOT_PATH . 'uploads/');
defined('EXPORT_COURT') || define('EXPORT_COURT', 'active_court');
/**
 *  Для проверки двухфакторной аутентификации на локальном сервере
 */
//defined('USE_2FA_LOC') || define('USE_2FA_LOC' , true);
/**
 *  Для отключения полной обработки платежей через PayPal - не шлем запросы сразу все запросы дают верное значение
 */
//defined('USE_LOCAL_FULL_PROCESSING_PAYPAL') || define('USE_LOCAL_FULL_PROCESSING_PAYPAL' , false);


require_once SHARED_PATH . 'bootstrap.php';
