<?php

namespace AC\app\config;

use RuntimeException;

uses('mysql.config');

class DBConfig
{
  private static array $config
    = [
      'base' => [
        'dbname'      => 'at_base_active_court',
        'prefixTable' => 'at_base_active_court_',
      ],
      'site' => [
        'dbname'      => DB_DATABASE_NAME,
        'prefixTable' => DB_TABLE_PREFIX,
      ],
      'old'  => [
        'dbname'      => 'old_' . DB_DATABASE_NAME,
        'prefixTable' => 'old_' . DB_TABLE_PREFIX,
      ],
      'test' => [
        'dbname'      => 'test_' . DB_DATABASE_NAME,
        'prefixTable' => 'test_' . DB_TABLE_PREFIX,
      ],
    ];

  public static function getConfig(string $alias, array $config = []): array
  {
    $configDB = [
      'host'        => DB_HOST,
      'user'        => !LOCAL_SERVER && defined('DB_ROOT') && DB_ROOT ? DB_ROOT_USERNAME : DB_USERNAME,
      'password'    => !LOCAL_SERVER && defined('DB_ROOT') && DB_ROOT ? DB_ROOT_PASSWORD : DB_PASSWORD,
      'dbname'      => self::$config[$alias]['dbname'] ?? self::$config['site']['dbname'],
      'prefixTable' => self::$config[$alias]['prefixTable'] ?? self::$config['site']['prefixTable'],
      'charset'     => defined('DB_CHARSET') ? DB_CHARSET : 'utf8',
      'type'        => defined('DB_TYPE') ? DB_TYPE : 'mysql',
    ];

    foreach ($config as $key => $value) {
      if (isset($configDB[$key]) && !empty($value)) {
        $configDB[$key] = $value;
      }
    }

    return $configDB;
  }

  /**
   * Получить конфигурацию отдельного подключения с расширенными правами.
   *
   * Переданные параметры могут изменить базу и префикс, но не учётные данные.
   */
  public static function getExtendedPrivilegesConfig(string $alias = 'site', array $config = []): array
  {
    if (!defined('DB_ROOT_USERNAME') || !defined('DB_ROOT_PASSWORD')
      || trim((string)DB_ROOT_USERNAME) === '' || (string)DB_ROOT_PASSWORD === '') {
      throw new RuntimeException('Database credentials with extended privileges are not configured.');
    }

    $config             = array_intersect_key($config, ['dbname' => true, 'prefixTable' => true]);
    $config['user']     = !LOCAL_SERVER ? DB_ROOT_USERNAME : DB_USERNAME;
    $config['password'] = !LOCAL_SERVER ? DB_ROOT_PASSWORD : DB_PASSWORD;

    return self::getConfig($alias, $config);
  }
}
