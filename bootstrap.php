<?php

use AC\app\locators\Helper;
use AC\app\locators\Service;

defined('SHARED_PATH') || define('SHARED_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
defined('ROOT_PATH') || define('ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
require_once SHARED_PATH . 'headers.php';

defined('SHARED_NAMESPACE') || define('SHARED_NAMESPACE', 'AC');
defined('ROOT_NAMESPACE') || define('ROOT_NAMESPACE', '');

defined('DIR_LOCATORS_PATHS') || define('DIR_LOCATORS_PATHS', 'app\locators\\');

require_once SHARED_PATH . 'core/main.php';
$paths = paths();

$config = loadClass('AutoloaderConfig', $paths->configDir);

require_once pathAs(SHARED_PATH . $paths->systemDir . 'autoloader/Autoloader.php');

require_once pathAs(SHARED_PATH . $paths->systemDir . 'pattern/Locator.php');
require_once pathAs(SHARED_PATH . $paths->systemDir . 'service/BaseService.php');
require_once pathAs(SHARED_PATH . $paths->systemDir . 'service/Services.php');


if (!class_alias(loadClass('Service', $paths->locatorsDir, false), 'Service')) {
  //заглушка для phpstorm
  class_alias(Service::class, 'Service');
}

Service::autoloader()->initialize($config)->register();


if (!class_alias(loadClass('Helper', $paths->locatorsDir, false), 'Helper')) {
  //заглушка для phpstorm
  class_alias(Helper::class, 'Helper');
}

uses('defined', 'baseConstants', 'authorization', 'holidays');

if(defined('USE_HEADERS') && USE_HEADERS){
// Быстрая инициализация
  $security = new SecurityHeaders();

  $security->apply();
}