<?php

namespace AC\app\config;

class AutoloaderConfig
{
  /** Namespace для ядра системы
   * @var string
   */
  public $sharedNamespace = SHARED_NAMESPACE;

  /** Путь до namespace ядра системы
   * @var string
   */
  public $pathSharedNamespace = SHARED_PATH;

  /** Namespace для сайта
   * @var string
   */
  public $rootNamespace = ROOT_NAMESPACE;

  /** Путь до namespace ядра системы
   * @var string
   */
  public $pathRootNamespace = ROOT_PATH;

  /**
   * -------------------------------------------------------------------
   * Namespaces
   * -------------------------------------------------------------------
   * This maps the locations of any namespaces in your application to
   * their location on the file system. These are used by the autoloader
   * to locate files the first time they have been instantiated.
   *
   * The '/app' and '/system' directories are already mapped for you.
   * you may change the name of the 'App' namespace if you wish,
   * but this should be done prior to creating any namespaced classes,
   * else you will need to modify all of those classes for this to work.
   *
   * Prototype:
   *```
   *   $psr4 = [
   *       ROOT_NAMESPACE    => ROOT_PATH,
   *       SHARED_NAMESPACE  => SHARED_PATH
   *   ];
   *```
   *
   * @var array<string, string>
   */
  public $psr4
    = [
    ];

  /**
   * -------------------------------------------------------------------
   * Class Map
   * -------------------------------------------------------------------
   * The class map provides a map of class names and their exact
   * location on the drive. Classes loaded in this manner will have
   * slightly faster performance because they will not have to be
   * searched for within one or more directories as they would if they
   * were being autoloaded through a namespace.
   *
   * Prototype:
   *```
   *   $classmap = [
   *       'MyClass'   => '/path/to/class/file.php'
   *   ];
   *```
   *
   * @var array<string, string>
   */
  public $classmap = [];

  /**
   * -------------------------------------------------------------------
   * Files
   * -------------------------------------------------------------------
   * The files array provides a list of paths to __non-class__ files
   * that will be autoloaded. This can be useful for bootstrap operations
   * or for loading functions.
   *
   * Prototype:
   * ```
   *    $files = [
   *       '/path/to/my/file.php',
   *    ];
   * ```
   *
   * @var array<int, string>
   */
  public $files = [];
  /**
   * -------------------------------------------------------------------
   * Namespaces
   * -------------------------------------------------------------------
   * This maps the locations of any namespaces in your application to
   * their location on the file system. These are used by the autoloader
   * to locate files the first time they have been instantiated.
   *
   * Do not change the name of the CodeIgniter namespace or your application
   * will break.
   *
   * @var array<string, string>
   */
  protected $corePsr4
    = [
      'PHPMailer' => SHARED_PATH . 'classes' . DIRECTORY_SEPARATOR . 'PHPMailer' . DIRECTORY_SEPARATOR,
      'Psr' => SHARED_PATH . 'core' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'thirdParty' . DIRECTORY_SEPARATOR . 'PSR' . DIRECTORY_SEPARATOR
    ];

  /**
   * -------------------------------------------------------------------
   * Class Map
   * -------------------------------------------------------------------
   * The class map provides a map of class names and their exact
   * location on the drive. Classes loaded in this manner will have
   * slightly faster performance because they will not have to be
   * searched for within one or more directories as they would if they
   * were being autoloaded through a namespace.
   *
   * @var array<string, string>
   */
  protected $coreClassmap = [];

  /**
   * -------------------------------------------------------------------
   * Core Files
   * -------------------------------------------------------------------
   * List of files from the framework to be autoloaded early.
   *
   * @var array<int, string>
   */
  protected $coreFiles = [];

  public function __construct()
  {
    $this->psr4     = array_merge($this->corePsr4, $this->psr4);
    $this->classmap = array_merge($this->coreClassmap, $this->classmap);
    $this->files    = array_merge($this->coreFiles, $this->files);
  }
}