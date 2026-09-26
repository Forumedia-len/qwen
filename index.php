<?php

defined('SHARED_PATH') || define('SHARED_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);

require_once SHARED_PATH . 'config.php';

Service::app()->run();