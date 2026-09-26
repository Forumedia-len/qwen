<?php

use AC\app\locators\Paths;
use AC\core\system\config\BaseConfig;
use AC\core\system\db\Query;
use AC\core\system\debug\VDebugs;
use AC\core\system\module\BaseModule;
use AC\core\system\pattern\Factories;
use AC\core\system\thirdParty\escaper\Escaper;
use AC\core\system\view\Layouts;
use AC\core\system\view\View;

//удаляем слеши
if (ini_get('magic_quotes_gpc')) {
  function strips(&$el)
  {
    if (is_array($el)) {
      foreach ($el as $k => $v) {
        if ($k != 'GLOBALS' && $k != '_FILES') {
          strips($el[$k]);
        }
      }
    } else {
      $el = stripslashes($el);
    }
  }

  strips($_GET);
  strips($_REQUEST);
  strips($_POST);
  strips($_COOKIE);
}

if (!function_exists('pathAs')) {
  function pathAs($path, $as = null): string
  {
    $path = $path ?? '';
    return match ($as) {
      'namespace' => str_replace(['\\', '/'], '\\', preg_replace('/\.[\da-z]{1,5}$/i', '', trim($path, '\\/'))),
      'url'       => str_replace(['\\', '/'], '/', ltrim($path, '\\/')),
      default     => str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path),
    };
  }
}

/**
 * @return VDebugs|mixed
 */
function Debug()
{
  require_once pathAs(SHARED_PATH . paths()->systemDir . 'debug/VDebugs.php');

  return func_num_args() > 0 ? VDebugs::dvD(...func_get_args()) : new VDebugs();
}

function loadClass($className, $dirNamespace, $instance = true)
{
  $className       = ucfirst($className);
  $actualClassName = null;
  if ($className && $dirNamespace) {
    if (file_exists(pathAs(SHARED_PATH . $dirNamespace . $className . '.php'))) {
      require_once pathAs(SHARED_PATH . $dirNamespace . $className . '.php');
      $actualClassName = pathAs(SHARED_NAMESPACE . '\\' . $dirNamespace . $className, 'namespace');
    }

    if (SHARED_PATH != ROOT_PATH
      && file_exists(pathAs(ROOT_PATH . $dirNamespace . $className . '.php'))) {
      require_once pathAs(ROOT_PATH . $dirNamespace . $className . '.php');
      $actualClassName = pathAs(ROOT_NAMESPACE . '\\' . $dirNamespace . $className, 'namespace');
    }
  }

  return $actualClassName ? ($instance ? new $actualClassName() : $actualClassName) : null;
}

if (!function_exists('paths')) {
  /**
   * Получить установленные пути
   *
   * @param string $dirLocators - путь к папке с путями для локаторов
   * @param bool   $new         - создать новый экземпляр
   *
   * @return Paths
   */
  function paths(string $dirLocators = DIR_LOCATORS_PATHS, bool $new = false): Paths
  {
    static $paths;

    if (!isset($paths) || $new) {
      loadClass('PathsNames', $dirLocators);
      $paths = loadClass('Paths', $dirLocators);
    }

    return $paths;
  }
}

//инициализация всей мега-системы
if (!function_exists('getEngine')) {
  /**
   * @template T
   *
   * @param class-string<T{'Engine'}> $engine_alias
   *
   * @return T{'Engine'}|null
   */
  function getEngine(string $engine_alias, $useOld = true, $getShared = true)
  {
    static $engines;

    if (!isset ($engines)) {
      $engines = [];
    }

    //уже есть
    if (isset($engines[$engine_alias])) {
      return $engines[$engine_alias];
    } else {
      $engine = null;
      if ($useOld) {
        //нет - пытаемся достать
        $engine_path = Service::autoloader()->getPathFile('engines/' . $engine_alias . '.php');
        if (file_exists($engine_path)) {
          //доступен
          include_once($engine_path);
          $alias  = class_exists($engine_alias) ? $engine_alias : str_replace('.', '_', $engine_alias);
          $engine = new $alias;
        }
      }

      if (!$engine) {
        $engine = Factories::engines(ucfirst($engine_alias . 'Engine'), null);
      }
      if ($engine) {
        //ключ на старт
        if (method_exists($engine, 'Initialize')) {
          $engine->Initialize();
        }
        if ($getShared) {
          $engines[$engine_alias] = $engine;
        }

        return $engine;
      }
    }

    return null; //файл не найден
  }
}

//использовать какую-то библиотеку
if (!function_exists('uses')) {
  function uses(): void
  {
    foreach (func_get_args() as $lib) {
      foreach (array_merge(Service::autoloader()->getNamespace(),
        ['SharedUses' => pathAs((str_starts_with(SHARED_PATH, SHARED) ? SHARED : dirname(SHARED_PATH) . '/'))]) as $path) {
        foreach (['.loc', ''] as $filePrefix) {
          if ($file = Service::autoloader()->getPathFile(paths()->constantDir . $lib . $filePrefix, 'php', $path)) {
            Service::autoloader()->loadFile($file);
          }
        }
      }
    }
  }
}

//подключить класс
if (!function_exists('prepareClass')) {
  function prepareClass(): void
  {
    foreach (func_get_args() as $lib) {
      useClass('classes/' . $lib);
    }
  }
}

//структура
if (!function_exists('getStructure')) {
  function getStructure($alias = null, $getShared = true)
  {
    return Service::structure($alias, $getShared);
  }
}

//тем-билдер
if (!function_exists('getThemeBuilder')) {
  function getThemeBuilder()
  {
    static $obj;

    if (!isset ($obj)) {
      //если тема еще не загружена - загружаем
      useFile(paths()->themesDir . THEME . '.php');
      $class_name = 'theme_' . THEME;
      $obj        = new $class_name;
    }

    return $obj;
  }
}

if (!function_exists('setLogConfig')) {
  function setLogConfig()
  {
//    $path       = debug_backtrace();
//    $path_parts = pathinfo($path[0]['file']);
//    $file       = $path_parts['dirname'] . '/' . $path_parts['filename'] . '.loc.' . $path_parts['extension'];
//    file_exists($file) && require_once $file;
  }
}

if (!function_exists('useClass')) {
//подключить класс по namespace
  function useClass($className, $instance = false, ...$arguments)
  {
    $className = Service::autoloader()->useClass($className);

    return $instance && $className ? new $className(...$arguments) : $className;
  }
}

if (!function_exists('useFile')) {
//подключить файл
  function useFile($fileName, $variables = [], $ext = 'php', $type = 'require', $once = true, $readyPath = null)
  {
    return Service::autoloader()->useFile($fileName, $variables, $ext, $type, $once, $readyPath);
  }
}

if (!function_exists('lang')) {
  function lang($message, $group = null, $params = [], $default = null, $locale = null): string
  {
    return Service::lang($locale)->_($message, $group, $params, $default, $locale);
  }
}

if (!function_exists('langByAreaType')) {
  /**
   * Получает языковое сообщение с fallback-цепочкой
   * Сначала ищет в секции {alias_type}_{type_id}, затем в {alias_type}, затем использует значение по умолчанию
   *
   * @param string      $message    Ключ сообщения
   * @param string      $alias_type Базовый тип (close, open, mc_arena)
   * @param int         $type_id    ID типа площадки
   * @param array       $params     Параметры для подстановки в сообщение
   * @param string|null $default    Значение по умолчанию
   * @param string|null $locale     Локаль
   * @param string      $prefix
   *
   * @return string
   */
  function langByAreaType(
    string $message,
    string $alias_type,
    int $type_id,
    array $params = [],
    ?string $default = null,
    ?string $locale = null,
    string $prefix = 'messages'
  ): string {
    $lang             = Service::lang($locale);
    $specific_section = $prefix . '_' . $alias_type . '_' . $type_id;
    $base_section     = $prefix . '_' . $alias_type;
    return match (true) {
      $lang->has($message, $specific_section) => $lang->_($message, $specific_section, $params, null, $locale),
      $lang->has($message, $base_section)     => $lang->_($message, $base_section, $params, null, $locale),
      default                                 => $lang->_($message, $prefix . '_' . 'default', $params, $default, $locale),
    };
  }
}

if (!function_exists('site_url')) {
  function site_url($url = null)
  {
    return Service::url()->siteUrl(pathAs($url, 'url'));
  }
}

if (!function_exists('base_url')) {
  function base_url($url = null): string
  {
    return Service::url()->baseUrl(pathAs($url, 'url'));
  }
}

if (!function_exists('cdn_url')) {
  function cdn_url($url = null): string
  {
    return Service::url()->cdnUrl(pathAs($url, 'url'));
  }
}

if (!function_exists('array_key_first')) {
  function array_key_first(array $arr)
  {
    foreach ($arr as $key => $unused) {
      return $key;
    }

    return null;
  }
}

if (!function_exists('is_cli')) {
  /**
   * Check if PHP was invoked from the command line.
   *
   */
  function is_cli()
  {
    if (PHP_SAPI === 'cli') {
      return true;
    }

    if (defined('STDIN')) {
      return true;
    }

    if (stristr(PHP_SAPI, 'cgi') && getenv('TERM')) {
      return true;
    }

    if (!isset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['argv']) && count($_SERVER['argv']) > 0) {
      return true;
    }

    // if source of request is from CLI, the `$_SERVER` array will not populate this key
    return !isset($_SERVER['REQUEST_METHOD']);
  }
}

if (!function_exists('esc')) {
  /**
   * Performs simple auto-escaping of data for security reasons.
   * Might consider making this more complex at a later date.
   *
   * If $data is a string, then it simply escapes and returns it.
   * If $data is an array, then it loops over it, escaping each
   * 'value' of the key/value pairs.
   *
   * Valid context values: html, js, css, url, attr, raw, null
   *
   * @param array|string $data
   * @param string       $encoding
   *
   * @return array|string
   * @throws InvalidArgumentException
   *
   */
  function esc($data, $context = 'html', $encoding = null)
  {
    if (is_array($data)) {
      foreach ($data as &$value) {
        $value = esc($value, $context);
      }
    }

    if (is_string($data)) {
      $context = strtolower($context);

      // Provide a way to NOT escape data since
      // this could be called automatically by
      // the View library.
      if (empty($context) || $context === 'raw') {
        return $data;
      }

      if (!in_array($context, ['html', 'js', 'css', 'url', 'attr'], true)) {
        throw new InvalidArgumentException('Invalid escape context provided.');
      }

      $method = $context === 'attr' ? 'escapeHtmlAttr' : 'escape' . ucfirst($context);

      static $escaper;
      if (!$escaper) {
        $escaper = new Escaper($encoding);
      }

      if ($encoding && $escaper->getEncoding() !== $encoding) {
        $escaper = new Escaper($encoding);
      }

      $data = $escaper->{$method}($data);
    }

    return $data;
  }
}

if (!function_exists('config')) {
  /**
   * @param string $name Название конфигурации (например: 'DB', 'auth')
   * @param array  $options
   * @param bool   $getShared
   *
   * @return BaseConfig
   *
   * @see \AC\app\config\LangConfig for config('lang')
   * @see \AC\app\config\PaymentConfig for config('payment')
   * @see \AC\core\modules\payment\config\PayoneConfig for config('payone')
   * @see \AC\core\modules\payment\config\PaypalConfig for config('paypal')
   */
  function config(string $name, array $options = [], bool $getShared = true)
  {
    $className = ucfirst($name) . 'Config';
    return Factories::config($className, ['getShared' => $getShared], $options);
  }
}

if (!function_exists('module')) {
  /**
   * More simple way of getting module instances from Factories
   *
   * @return BaseModule
   */
  function module($name, $options = [], $getShared = true)
  {
    return Factories::module(ucfirst($name) . 'Module', ['getShared' => $getShared], $options);
  }
}

if (!function_exists('view')) {
  /**
   *
   * @return View
   * w
   */
  function view($getShared = true)
  {
    return Service::view($getShared);
  }
}

if (!function_exists('useLayout')) {
  /**  Подключаем макет многоразового использования
   *
   * @return Layouts
   * w
   */
  function useLayout()
  {
    return new Layouts();
  }
}

if (!function_exists('query')) {
  /**
   *
   * @param array $options
   * @param bool  $getShared
   *
   * @return Query
   * w
   */
  function query($options = [], $getShared = true)
  {
    return Service::query($options, $getShared);
  }
}

if (!function_exists('getFilenames')) {
  /**
   * Get Filenames
   *
   * Reads the specified directory and builds an array containing the filenames.
   * Any sub-folders contained within the specified path are read as well.
   *
   * @param string    $sourceDir   Path to source
   * @param bool|null $includePath Whether to include the path as part of the filename; false for no path, null for a relative path, true for full path
   * @param bool      $hidden      Whether to include hidden files (files beginning with a period)
   */
  function getFilenames($sourceDir, $includePath = false, $hidden = false)
  {
    $files = [];

    $sourceDir = realpath($sourceDir) ?: $sourceDir;
    $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    try {
      foreach (
        new RecursiveIteratorIterator(
          new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
          RecursiveIteratorIterator::SELF_FIRST
        ) as $name => $object
      ) {
        $basename = pathinfo($name, PATHINFO_BASENAME);
        if (!$hidden && $basename[0] === '.') {
          continue;
        }

        if ($includePath === false) {
          $files[] = $basename;
        } elseif ($includePath === null) {
          $files[] = str_replace($sourceDir, '', $name);
        } else {
          $files[] = $name;
        }
      }
    } catch (Throwable $e) {
      return [];
    } catch (Exception $e) {
      return [];
    }

    sort($files);

    return $files;
  }
}

function getDataOfCombinedSites($device = 'site', $key = 'reservations')
{
  return config('combinedSites')->geStructureBookingLinks($device, $key);
}

if (!function_exists('clean_path')) {
  /**
   * A convenience method to clean paths for
   * a nicer looking output. Useful for exception
   * handling, error logging, etc.
   */
  function clean_path(string $path): string
  {
    // Resolve relative paths
    try {
      $path = realpath($path) ?: $path;
    } catch (ErrorException|ValueError) {
      $path = 'error file path: ' . urlencode($path);
    }

    return match (true) {
      str_starts_with($path, APPPATH)                             => 'APPPATH' . DIRECTORY_SEPARATOR . substr($path, strlen(APPPATH)),
      str_starts_with($path, SHARED_PATH)                         => 'SHARED_PATH' . DIRECTORY_SEPARATOR . substr($path, strlen(SHARED_PATH)),
//      str_starts_with($path, FCPATH)                              => 'FCPATH' . DIRECTORY_SEPARATOR . substr($path, strlen(FCPATH)),
      defined('VENDORPATH') && str_starts_with($path, VENDORPATH) => 'VENDORPATH' . DIRECTORY_SEPARATOR . substr($path, strlen(VENDORPATH)),
      str_starts_with($path, ROOT_PATH)                           => 'ROOT_PATH' . DIRECTORY_SEPARATOR . substr($path, strlen(ROOT_PATH)),
      default                                                     => $path,
    };
  }
}