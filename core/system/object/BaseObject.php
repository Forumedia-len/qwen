<?php

namespace AC\core\system\object;

use AC\core\engines\Engines;
use AC\core\system\App;

use AC\core\system\helpers\FileHelper;
use AC\core\system\helpers\StringHelper;
use AC\core\system\pattern\Factories;
use Service;

class BaseObject extends Entity
{
  protected $baseEngine = 'Engines';
  /**
   * @var Engines|mixed
   */
  protected $engine;

  public function __construct()
  {

    $this->setEngine();
  }

  public function setEngine($engineName = null)
  {
    static $engines;
    if ($engineName) {
      $this->baseEngine = $engineName;
    }
    $priority = [
      $this->getModulePath() . 'engines\\' . $this->baseEngine,
      paths()->enginesDir . $this->baseEngine,
      paths()->enginesDir . 'Engines',
    ];
    $path     = Service::locator()->findPriorityPathToFile($priority, false);
    $basename = basename($path);
    if (!array_key_exists($basename, $engines ?? [])) {
      $engines[$basename] = useClass($path, true, $this);
    }

    $this->engine = $engines[$basename] ?? Service::engines();
  }

  protected function getModulePath($modulePath = null)
  {
    if (!$modulePath) {
      foreach ([FileHelper::getBasename(static::className()), $this->baseEngine] as $name) {
        $pathNameItems = [];
        foreach (explode('_', StringHelper::camelCaseToUnderscore($name)) as $item) {
          $pathNameItems[] = $item;
          $path            = Service::autoloader()->getActualDir(paths()->modulesDir . StringHelper::underscoreToCamelCase(implode('_',
              $pathNameItems)));
          if ($path) {
            return $path['path'] . '\\';
          }
        }
      }
    }

    return Service::autoloader()->getActualDir(paths()->modulesDir . $modulePath)['path'] . '\\';
  }

  public function getEngine(): mixed
  {
    return $this->engine;
  }
}