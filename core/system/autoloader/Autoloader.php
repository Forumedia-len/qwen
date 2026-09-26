<?php

namespace AC\core\system\autoloader;

use AC\app\config\AutoloaderConfig;


class Autoloader
{
  /** Namespace для ядра системы
   * @var string
   */
  protected $sharedNamespace;

  /** Путь до namespace ядра системы
   * @var string
   */
  protected $pathSharedNamespace;

  /** Namespace для сайта
   * @var string
   */
  protected $rootNamespace;

  /** Путь до namespace ядра системы
   * @var string
   */
  protected $pathRootNamespace;

  /** Массив базовых значений по каким путям искать
   *  в исходном виде имеет два значения
   *  локальный (namespace => path) ['' => ROOT_PATH]
   *  системный (namespace => path) ['AC' => SHARED_PATH]
   *  если ROOT_PATH == SHARED_PATH , то остается только системное значение
   *  нужно для упрощения переподключения локальной версии файлов
   * Namespace как ключ , путь как значение
   * @var array
   */
  protected $baseNamespace = [];


  /**
   * Stores namespaces as key, and path as values.
   *
   * @var array<string, array<string>>
   */
  protected $prefixes = [];

  /**
   * Stores class name as key, and path as values.
   *
   * @var array<string, string>
   */
  protected $classmap = [];

  /**
   * Stores files as a list.
   *
   * @var array<int, string>
   */
  protected $files = [];

  /**
   * @param AutoloaderConfig $config
   *
   * @return Autoloader
   */
  public function initialize(AutoloaderConfig $config)
  {
    $this->sharedNamespace     = $config->sharedNamespace;
    $this->rootNamespace       = $config->rootNamespace;
    $this->pathSharedNamespace = $config->pathSharedNamespace;
    $this->pathRootNamespace   = $config->pathRootNamespace;

    $this->baseNamespace[$this->sharedNamespace] = $this->pathSharedNamespace;

    if ($this->pathRootNamespace !== $this->pathSharedNamespace) {
      $this->baseNamespace[$this->rootNamespace] = $this->pathRootNamespace;
    }
    $this->addBaseNamespaces(array_reverse($this->baseNamespace));

    if (isset($config->psr4)) {
      $this->addNamespace($config->psr4);
    }

    if (isset($config->classmap)) {
      $this->classmap = $config->classmap;
    }

    if (isset($config->files)) {
      $this->files = $config->files;
    }

    /**
     *  todo доработать подключение из composer - два варианта с локальной версии и из системной
     */
    //    $this->discoverComposerNamespaces();

    return $this;
  }

  private function addBaseNamespaces($namespaces)
  {
    $baseNamespaces = [];
    foreach ($namespaces as $prefix => $namespace) {
      foreach ([paths()->appDir, '', paths()->coreDir, paths()->systemDir] as $path) {
        $baseNamespaces[$prefix . '\\' . $path] = $namespace  . pathAs($path);
      }
    }
    $this->addNamespace($baseNamespaces);
  }


  /**
   * Register the loader with the SPL autoloader stack.
   */
  public function register()
  {
    spl_autoload_extensions('.php,.inc');

    // Prepend the PSR4 autoloader for maximum performance.
    spl_autoload_register([$this, 'useClass'], true, true);

    // Load our non-class files
    foreach ($this->files as $file) {
      if (is_string($file)) {
        $this->loadFile($file);
      }
    }
  }

  /**
   * Подключаем класс - сначала ищем по нашим стандартным путям,
   * потом по стандартным папкам расположения подключаемым классам,
   * или если используется composer, то в папке vendor
   *
   * @param string $className
   *
   * @return string|bool - Возвращаем имя класса или false
   */
  public function useClass($className)
  {
    // убираем расширение в конце если есть
    $className = preg_replace('/\.[\da-z]{1,5}$/i', '', $className);

    if (class_exists($className, false)) {
      return $className;
    }

    if (class_exists(basename($this->trimFile($className)), false)) {
      return basename($this->trimFile($className));
    }

    if (str_starts_with($className, 'AC\\')) {
      $this->useFile(str_replace('AC\\', '', $className), [], 'php', 'require', true, SHARED_PATH);

      return $className;
    }

    if ($this->useFile($className)) {
      $file = $this->actualPath($className);

      return isset($file['className']) ? $file['className'] : $className;
    }

    return $this->loadInNamespace($className);
  }

  /**
   * Подключить файл используя стандартные функции php
   * можно управлять каким образом подключать файл.
   *
   * @param string $fileName
   * @param string $ext
   * @param string $type
   * @param bool   $once
   *
   * @return null|mixed Возвращаем содержимое файла или null
   */
  public function useFile($fileName, $variables = [], $ext = 'php', $type = 'require', $once = true, $readyPath = null)
  {
    return $this->loadFile($this->getPathFile($fileName, $ext, $readyPath), $type, $once, $variables);
  }

  public function loadFile($file, $type = 'require', $once = true, $variables = [])
  {
    $file = $this->sanitizeFilename($file);
    if (is_file($file)) {
      if (!empty($variables)) {
        extract($variables, EXTR_OVERWRITE);
      }
      switch ($type) {
        case 'require':
          return $once ? require_once $file : require $file;
        case 'include':
        default:
          return $once ? include_once $file : include $file;
      }
    }

    return null;
  }

  /**
   * Получить полный путь до файла
   * проверяем если есть файл в локальной версии то загружаем его,
   * если его нет то из общей - системной категории
   *
   * @param string $fileName
   * @param string $ext
   *
   * @return false|string
   */
  public function getPathFile($fileName, $ext = 'php', $readyPath = null, $checkFile = true)
  {
    $fileName = str_ireplace(['.php', '.' . $ext], '', $fileName);
    $fileName = $this->trimFile($fileName) . '.' . $ext;

    if ($readyPath) {
      return $this->checkPathFile($fileName, $readyPath, $checkFile);
    }
    if ($path = $this->actualPath($fileName, $ext, $checkFile)) {
      return $path['path'];
    }

    return false;
  }

  public function actualPath($fileName, $ext = 'php', $checkFile = true)
  {
    $className = $ext ? str_ireplace(['.php', '.' . $ext], '', $fileName) : $fileName;
    $fileName  = $this->trimFile($className) . ($ext ? '.' . $ext : '');
    foreach ($this->prefixes as $nameSpace => $basePath) {
      $path = $this->checkPathFile($fileName, $basePath, $checkFile);
      if ($path) {
        return [
          'path'             => $path,
          'namespace'        => $nameSpace,
          'className'        => $nameSpace . '\\' . $className,
          'dynamicNamespace' => $className,
        ];
      }
    }

    return null;
  }

  public function getActualDir($dir, $checkDir = true)
  {
    foreach ($this->prefixes as $nameSpace => $basePath) {
      $path = pathAs($this->checkPathFile($dir, $basePath, false));
      if ($path && is_dir($path)) {
        return [
          'pathFull'      => $path,
          'path'       => $dir,
          'namespace' => $nameSpace,
          'basePath'  => $basePath,
        ];
      }
    }

    return null;
  }

  public function checkPathFile($fileName, $basePath, $checkFile = true)
  {
    $path = pathAs($basePath . $fileName);
    return $checkFile ? realpath($path) : $path;
  }

  /**
   * Переработать путь файла без лишних символов и слешей.
   *
   * @param string $fileName Имя файла
   * @param string  $characters
   *
   * @return string
   */
  public function trimFile($fileName, $characters = " \n\r\t\v\0/\\", $functionName = 'trim')
  {
    $fileName = pathAs($fileName);

    switch ($functionName) {
      case 'rtrim':
        return rtrim($fileName, $characters);
      case 'ltrim':
        return ltrim($fileName, $characters);
      case 'trim':
      default:
        return trim($fileName, $characters);
    }
  }

  /**
   * Loads the class file for a given class name.
   *
   * @param string $class The fully-qualified class name
   *
   * @return string|false The mapped file name on success, or boolean false on fail
   */
  protected function loadInNamespace($class)
  {
    foreach ($this->prefixes as $namespace => $directory) {
      if (in_array($namespace, array_keys($this->baseNamespace))) {
        continue;
      }
      $directory = rtrim($directory, '\\/');

      if (strpos($class, $namespace) === 0) {
        $filePath = $directory . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($namespace))) . '.php';
        $filename = $this->loadFile($filePath);

        if ($filename) {
          return $filename;
        }
      }
    }

    // never found a mapped file
    return false;
  }


  /**
   * Sanitizes a filename, replacing spaces with dashes.
   *
   * Removes special characters that are illegal in filenames on certain
   * operating systems and special characters requiring special escaping
   * to manipulate at the command line. Replace spaces and consecutive
   * dashes with a single dash. Trim period, dash and underscore from beginning
   * and end of filename.
   *
   * @param string $filename
   *
   * @return string       The sanitized filename
   */
  public function sanitizeFilename($filename)
  {
    // Only allow characters deemed safe for POSIX portable filenames.
    // Plus the forward slash for directory separators since this might
    // be a path.
    // http://pubs.opengroup.org/onlinepubs/9699919799/basedefs/V1_chap03.html#tag_03_278
    // Modified to allow backslash and colons for on Windows machines.
    $filename = preg_replace('/[^0-9\p{L}\s\/\-\_\.\:\\\\]/u', '', $filename);

    // Clean up our filename edges.
    $filename = trim($filename, '.-_');

    return $filename;
  }

  /**
   * Registers namespaces with the autoloader.
   *
   * @param array|string $namespace
   * @param null|string  $path
   *
   * @return $this
   */
  public function addNamespace($namespace, $path = null)
  {
    if (is_array($namespace)) {
      foreach ($namespace as $prefix => $namespacedPath) {
        $prefix = trim($prefix, '\\');

        if (is_array($namespacedPath)) {
          foreach ($namespacedPath as $dir) {
            $this->addPrefix($prefix, $dir);
          }

          continue;
        }

        $this->addPrefix($prefix, $namespacedPath);
      }
    } else {
      $this->addPrefix($namespace, $path);
    }

    return $this;
  }

  private function addModuleNamespaces($basePrefix, $basePath)
  {
    $priorityPath = 'modules';
    if (($path = $this->checkPathFile($priorityPath, $basePath)) && is_dir($path)) {
      foreach (scandir($path) as $dir) {
        if ($dir !== '.' and $dir !== '..' &&  is_dir($path . DIRECTORY_SEPARATOR . $dir)) {
          $this->addPrefix($basePrefix . '\\' . $priorityPath . '\\' .  $dir, $path . DIRECTORY_SEPARATOR . $dir, false);
        }
      }
    }
  }

  protected function addPrefix($prefix, $path, $addModuleNamespaces = true)
  {
    if ($addModuleNamespaces) {
      $this->addModuleNamespaces($prefix, $path);
    }
    $this->prefixes[trim($prefix, '\\')] = rtrim($path, '\\/') . DIRECTORY_SEPARATOR;
  }

  /**
   * Get namespaces with prefixes as keys and paths as values.
   *
   * If a prefix param is set, returns only paths to the given prefix.
   *
   * @param null|string $prefix
   *
   * @return array
   */
  public function getNamespace($prefix = null)
  {
    if ($prefix === null) {
      return $this->prefixes;
    }
    $prefix = trim($prefix, '\\');

    return isset($this->prefixes[$prefix]) ? $this->prefixes[$prefix] : [];
  }

  /**
   * Removes a single namespace from the psr4 settings.
   *
   * @param string $namespace
   *
   * @return $this
   */
  public function removeNamespace($namespace)
  {
    if (isset($this->prefixes[trim($namespace, '\\')])) {
      unset($this->prefixes[trim($namespace, '\\')]);
    }

    return $this;
  }

  /**
   * Locates autoload information from Composer, if available.
   */
  protected function discoverComposerNamespaces()
  {
    if (!is_file(SHARED_PATH . 'vendor/autoload.php')) {
      return;
    }

    $composer = include SHARED_PATH . 'vendor/autoload.php';
    $paths    = $composer->getPrefixesPsr4();
    $classes  = $composer->getClassMap();

    unset($composer);

    $newPaths = [];

    foreach ($paths as $key => $value) {
      // Composer stores namespaces with trailing slash. We don't.
      $newPaths[rtrim($key, '\\ ')] = $value;
    }

    $this->prefixes = array_merge($this->prefixes, $newPaths);
    $this->classmap = array_merge($this->classmap, $classes);
  }

  /**
   * @return string
   */
  public function getRootNamespace()
  {
    return $this->rootNamespace;
  }

  /**
   * @return string
   */
  public function getSharedNamespace()
  {
    return $this->sharedNamespace;
  }

  /**
   * @return string
   */
  public function getPathRootNamespace()
  {
    return $this->pathRootNamespace;
  }

  /**
   * @return string
   */
  public function getPathSharedNamespace()
  {
    return $this->pathSharedNamespace;
  }


  /**
   * @param $namespace
   *
   * @return array|mixed|null
   */
  public function getBaseNamespace($namespace = null)
  {
    if ($namespace === null) {
      return $this->baseNamespace;
    }
    $namespace = trim($namespace, '\\');

    return isset($this->baseNamespace[$namespace]) ? $this->baseNamespace[$namespace] : null;
  }

  public function raisePriorityNamespaceModuleUp($moduleName)
  {
    $namespaceOut = [[], [], []];
    $module = false;
    foreach ($this->getNamespace() as $namespace => $path) {
      if (!str_contains($namespace, '\modules\\')) {
        $namespaceOut[(!$module ? 0 : 2)][$namespace] = $path;
      } else {
        $module = true;
        if (!str_contains($namespace, 'modules\\' . $moduleName)) {
          $namespaceOut[1][$namespace] = $path;
        } else {
          $namespaceOut[1] = array_merge([$namespace => $path], $namespaceOut[1]);
        }
      }
    }

    $this->prefixes = array_merge($namespaceOut[0], $namespaceOut[1], $namespaceOut[2]);
  }
}
