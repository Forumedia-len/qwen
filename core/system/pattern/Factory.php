<?php

namespace AC\core\system\pattern;

use AC\core\system\config\BaseConfig;

/**
 * Factories Configuration file.
 *
 * Provides overriding directives for how
 * Factories should handle discovery and
 * instantiation of specific components.
 * Each property should correspond to the
 * lowercase, plural component name.
 */
class Factory extends BaseConfig
{
  /**
   * Supplies a default set of options to merge for
   * all unspecified factory components.
   *
   * @var array
   */
  public static $default
    = [
      'component'  => null,
      'path'       => null,
      'instanceOf' => null,
      'getShared'  => true,
      'preferApp'  => true,
      'useSubPath' => false,
    ];

  /**
   * Specifies that Models should always favor child
   * classes to allow easy extension of module.
   *
   * @var array
   */
  public $module
    = [
      'component'  => 'module',
      'path'       => 'modules',
      'instanceOf' => null,
      'getShared'  => true,
      'preferApp'  => true,
      'useSubPath' => true,
    ];
}
