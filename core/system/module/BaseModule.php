<?php

namespace AC\core\system\module;

use AC\core\system\controller\BaseController;
use AC\core\system\exceptions\module\ModuleException;
use AC\core\system\helpers\FileHelper;
use AC\core\system\helpers\StringHelper;
use AC\core\system\model\BaseModel;
use AC\core\system\object\Entity;
use Service;
use stdClass;


/** Выбирает контроллеры, маршруты и переводы функционального модуля. */
class BaseModule extends Entity implements ModuleInterface
{
  protected string $mode = '';
  protected        $controller;
  protected        $action;

  protected ?string $subName       = null;
  protected ?string $baseName      = null;
  protected string  $baseNamespace;
  protected bool    $useRouting    = false;
  protected bool    $runController = false;
  protected array   $segments      = [];

  protected array $options = [];

  /**
   * BaseModule constructor.
   *
   * @param array $options
   */
  public function __construct(array $options = [])
  {
    Service::app()->setUsedModule($this->getNameModule(true));
    $this->baseNamespace = paths()->modulesDir;
    $this->segments      = Service::url()->getSegments();
    $this->setOptions($options);
    $this->baseName = $this->useRouting && ($this->segments[0] ?? null) === $this->getNameModule(true)
      ? array_shift($this->segments)
      : $this->getNameModule(true);
    if ($this->useRouting) {
      $this->initRouting();
    }
    $this->setBaseNamespace();
    $this->instanceLang();
  }

  protected function initRouting()
  {
    $this->setMode();
    $this->setSubName();
  }

  /**
   * Установить опции модуля
   *
   * @param array $options
   *
   * @return void
   */
  public function setOptions(array $options = []): void
  {
    $this->options = $options;
    foreach ($options as $key => $option) {
      $setter = 'set' . ucfirst($key);
      if (method_exists($this, $setter)) {
        $this->{$setter}($option);
      } else {
        $this->addProperty($key, $option);
      }
    }
  }

  /**
   * Задать дополнительную подпапку для модулей (subName)
   * например как для оплаты может быть несколько модулей оплаты
   * опционально.
   *
   * @param string|null $subName
   *
   * @return void
   */
  public function setSubName(?string $subName = null): void
  {
    $this->subName = $subName ? StringHelper::underscoreToCamelCase($subName) : $subName;
  }

  /**
   * Задать базовый namespace для модуля
   *
   * @param string|null $baseNameSpace
   *
   * @return void
   */
  public function setBaseNamespace(?string $baseNameSpace = null): void
  {
    $baseNameSpace = $baseNameSpace ? : $this->baseNamespace . $this->baseName . ($this->subName ? '\\' . $this->subName : '');
    if ($path = Service::autoloader()->actualPath($baseNameSpace, false)) {
      $this->baseNamespace = $path['dynamicNamespace'] . '\\';
    }
  }

  /**
   * Задать суффикс для контроллеров (mode)
   *
   * @param string|null $mode
   *
   * @return void
   */
  public function setMode(?string $mode = null): void
  {
    $this->mode = $mode ? StringHelper::underscoreToCamelCase($mode) : '';
  }

  public function exec($action = null, $key = null, $controllerName = null)
  {
    $return     = [];
    $controller = $this->useController($controllerName, ['action' => $action]);
    if ($controller) {
      if (method_exists($controller, 'runController')) {
        $controller->runController();

        $return = $controller->view->getToArray();
      } else {
        $return = $controller->{$this->action}();
      }
    }

    return is_array($return) && $key && isset($return[$key]) ? $return[$key] : $return;
  }

  public function execContent($action = null, $wrapper = ['', ''])
  {
    $return = $this->exec($action, 'content');
    if ($return) {
      $return = $wrapper[0] . implode($wrapper[1] . $wrapper[0], $return) . $wrapper[1];
    } else {
      $return = '';
    }

    return $return;
  }

  public function getController()
  {
    return $this->controller;
  }

  public function getNameModule($dir = false)
  {
    $name = $this->baseName ? : str_replace('Module', '', FileHelper::getBasename(get_class($this)));

    return $dir ? lcfirst($name) : ucfirst($name);
  }

  public function getPrefixName()
  {
    return ucfirst($this->subName ? : '') . $this->getNameModule();
  }

  /** Получить контроллер
   *  если задано $controllerName - полностью построеное имя контроллера
   *  типа PaypalPaymentPersonalAccountControllerAdmin, где:
   *  Paypal - это subName (подчинённое имя) - как бы имя подмодуля - может не использоваться(в основном не используется)
   *  Payment - это имя модуля к которому обращаются - используется всегда
   *  PersonalAccount - suffixName(суффикс к имени) - берем из перемены mode - может не использоваться
   *  Controller - используется всегда - определяет что класс принадлежит к контроллерам
   *  Admin - окончание - определяет по какому девайсу был вызван модуль
   *         (site - обычно используется как подпапка и не используется как окончание, admin, touch, display) - может не использоваться
   *
   *  Так же есть различные директории по которым ищется этот контроллер
   *  core\modules\payment\paypal\controllers\admin\PaypalPaymentPersonalAccountController
   *  core\modules\payment\paypal\controllers\PaypalPaymentPersonalAccountControllerAdmin
   *  core\modules\payment\paypal\controllers\PaypalPaymentPersonalAccountController
   *
   *  общая часть                 core\modules\payment\
   *  если есть subName -         paypal\
   *  папка для контроллеров      controllers\
   *  и три варианта названия
   *  и расположения              admin\PaypalPaymentPersonalAccountController - подпака для девайса с отсутвующем окончанием
   *                              PaypalPaymentPersonalAccountControllerAdmin  - полностью имя с окончанием без подпапки
   *                              PaypalPaymentPersonalAccountController       - если нет окончания то берем контроллер для фронтенда
   *
   * @param string $controllerName
   * @param        $options
   *
   * @return BaseController|mixed
   */
  public function useController($controllerName = null, $options = [])
  {
    $nameControllerBase = $controllerName;
    if (!$controllerName) {
      $suffixName         = $this->mode ? : ($this->useRouting && isset($this->segments[0]) ? $this->segments[0] : '');
      $nameControllerBase = $this->getPrefixName() . ucfirst($suffixName) . 'Controller';
    }

    $nameControllerBase = $this->getNameControllerByPriorities($nameControllerBase);

    if (!$nameControllerBase && !$controllerName) {
      $controllerName = $this->getNameControllerByPriorities($this->getPrefixName() . 'Controller');
    } else {
      $controllerName = $nameControllerBase;
      if ($this->useRouting) {
        array_shift($this->segments);
      }
    }
    if (!$controllerName) {
      throw ModuleException::ControllerNotFound();
    }
    $this->action = isset($options['action']) && method_exists($controllerName, $options['action'])
      ? $options['action']
      : ($this->useRouting && isset($this->segments[0]) && method_exists($controllerName, $this->segments[0]) ? array_shift($this->segments)
        : ($this->action ? : null));


    $this->controller = useClass($controllerName, true, $this->action, $this->runController, $this->getControllerSegments());

    return $this->controller;
  }

  protected function getControllerSegments()
  {
    $segments = $this->options['params'] ?? [];

    if ($this->useRouting && !empty($this->segments)) {
      $segments = Service::request()->parserSegments($this->segments);
    }

    return $segments;
  }

  protected function getNameControllerByPriorities($controllerName)
  {
    $device = $this->device ?? Service::url()->getTemplatePath();
    $prioritiesName = [
      $this->baseNamespace . 'controllers\\' . $device . '\\' . $controllerName,
      $this->baseNamespace . 'controllers\\' . $controllerName . Service::url()->getSuffixName(),
    ];
    foreach (Service::templates()->getParentTemplates($device) as $parentDevice) {
      $prioritiesName[] = $this->baseNamespace . 'controllers\\' . $parentDevice . '\\' . $controllerName;
    }
    $prioritiesName[] = $this->baseNamespace . 'controllers\\' . $controllerName;

    if (Service::url()->getDevicePath() !== config('app')->apiDevice) {
      $prioritiesName[] = $this->baseNamespace . 'controllers\\' . $controllerName;
    }

    foreach ($prioritiesName as $nameController) {
      if ($name = useClass($nameController, false, null, false)) {
        return $name;
      }
    }

    return null;
  }

  /**
   * @param       $modelName
   * @param mixed ...$arguments
   *
   * @return BaseModel
   */
  public function useModel($modelName = null, ...$arguments)
  {
    $_modelName = ucfirst($modelName ? : ucfirst($this->subName ?? '') . ucfirst($this->baseName ?? '') . ucfirst($this->mode ?? ''));
    $_modelName .= !str_contains($_modelName, 'Model') ? 'Model' : '';
    foreach ([$_modelName, $modelName] as $name) {
      if ($model = useClass($this->baseNamespace . 'models\\' . $name, true, ...$arguments)) {
        return $model;
      }
    }

    return null;
  }

  /**
   * @return string
   */
  public function getBaseNamespace()
  {
    return $this->baseNamespace;
  }

  public function getModulePath($fullPath = false)
  {
    return $fullPath ? $this->baseNamespace : str_replace(paths()->modulesDir, '', $this->baseNamespace);
  }

  public function getFullModelName($modelName = null)
  {
    return useClass($this->getModulePath(true) . 'models\\' . ($modelName ? : '')) ?? stdClass::class;
  }

  public function getFullControllerName($controllerName)
  {
    return $this->getModulePath(true) . 'controllers\\' . ($controllerName ? : '');
  }

  /** Установить рабочие языки
   * @return void
   */
  protected function instanceLang(): void
  {
    $keys = array_unique(array_merge([
      '',
      lcfirst($this->getNameModule()),
      StringHelper::camelCaseToUnderscore($this->getNameModule()),
      StringHelper::camelCaseToUnderscore($this->getNameModule(), '.'),
    ], $this->getLangNameFiles()));

    foreach ($keys as $name) {
      Service::lang()->addFile($name, ($this->getModulePath(true) . paths()->getLangDir()));
    }
  }

  protected function getLangNameFiles(): array
  {
    return [];
  }
}
