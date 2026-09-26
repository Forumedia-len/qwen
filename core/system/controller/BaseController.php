<?php

namespace AC\core\system\controller;

use AC\core\system\App;
use AC\core\system\exceptions\InvalidConfigException;
use AC\core\system\helpers\FileHelper;
use AC\core\system\helpers\StringHelper;
use AC\core\system\http\response\ResponseInterface;
use AC\core\system\model\BaseModel;
use AC\core\system\model\InitializableModelInterface;
use AC\core\system\object\BaseObject;
use AC\core\system\view\Page;
use Service;


/** Базовый контроллер с подготовкой модели перед выполнением действия. */
abstract class BaseController extends BaseObject
{
  public $action;

  /**
   * @var BaseModel
   */
  public $model;
  protected $base_model = 'BaseModel';
  /**
   * @var Page
   */
  public $view;
  public $baseView = 'Page';
  public $default_action = 'show';
  public $default_template = '';
  public $admin = false;
  public $touch = false;
  protected $_name = null;

  protected $tpl_view = '';
  protected $useBaseModel = true;

  protected $segments = [];

  public function __construct($action = false, $runController = true, $segments = [])
  {
    parent::__construct();
    $this->parserSegments($segments);
    $this->action = $action ?: Service::request()->_('action', $this->default_action);
    if ($runController) {
      $this->runController();
    }
  }

  protected function parserSegments($segments = [])
  {
    $this->segments = $segments;
  }

  /** Подготовить модель и представление, затем выполнить действие контроллера. */
  public function runController()
  {
    if ($this->useBaseModel) {
      $this->setBaseModel();
    }
    if (is_object($this->model)) {
      $this->initializeModel($this->model);
    }


    $this->setView();
    $html = $this->start();
    if (!($html instanceof ResponseInterface)) {
      if ($this->model !== null && method_exists($this->model, 'isErrors') && $this->model->isErrors()) {
        $this->view->addMessages($this->model->getErrors(), 'error');
//      $this->redirectDefaultAction();
      }

      if ($this->view->issetMessages()) {
        $this->view->content['message'] = $this->view->getMessages();
      }

      if (is_array($html)) {
        foreach ($html as $content) {
          $this->view->content[] = $content;
        }
      } else {
        $this->view->content[(int)(!$this->admin)] = $html;
      }
    }
  }

  /** Передать контекст устройства до загрузки данных модели. */
  protected function initializeModel(object $model): void
  {
    if ($this->admin) {
      $model->admin = true;
    }
    if ($this->touch) {
      $model->touch = true;
    }
    if ($model instanceof InitializableModelInterface) {
      $model->initialize();
    }
  }

  /** Установить базовую модель для контроллера
   *
   * @param $modelName
   * @param $module
   *
   * @return BaseModel|mixed
   */
  public function setBaseModel($modelName = null, $module = null)
  {
    if (static::className() !== 'BaseController') {
      $this->_name = explode('Controller', FileHelper::getBasename(static::className()))[0];
      if ($this->base_model == 'BaseModel') {
        $this->base_model = $this->_name . 'Model';
      }
    }

    $this->base_model = useClass($this->getModulePath($module) . 'models\\' . ($modelName ?: $this->base_model));
    if ($this->base_model) {
      $this->model = new $this->base_model();
      if (method_exists($this, 'getPrimaryKey') && ($id = $this->getSegmentByKey($this->model->getPrimaryKey()))) {
        $this->model->setDataById($id);
      }
    }

    return $this->model;
  }

  /** Получить модель данного или выбранного модуля
   *
   * @param      $modelName - имя модели (с приставкой Model или без)
   * @param null $module    - название модуля
   * @param bool $instance
   *
   * @return BaseModel|null
   */
  public function getModel($modelName, $module = null, bool $instance = true): ?BaseModel
  {
    $priority = [
      $this->getModulePath($module) . 'models\\' . ucfirst($modelName),
      $this->getModulePath($module) . 'models\\' . ucfirst($modelName) . 'Model',
    ];
    foreach ($priority as $path) {
      if ($model = useClass($path)) {
        return new $model();
      }
    }

    return null;
  }


  /** Возвращает Segment адреса или переданного параметра
   *
   * @param $key
   *
   * @return mixed|null
   */
  public function getSegmentByKey($key)
  {
    return $this->segments[$key] ?? null;
  }

  /** Запустить какое-то действие
   *
   */
  public function start()
  {
    if (!method_exists($this, $this->action)) {
      $this->action = $this->default_action;
    }
    if ($this->checkActionData()) {
      return $this->{$this->action}();
    } else {
      return $this->view->getInfoBlockContent();
    }
  }

  public function setBaseView($baseView = null)
  {
    $baseView = Service::autoloader()->getPathFile($this->getModulePath() . 'views\\' . $this->baseView);
    if ($baseView) {
      $this->baseView = useClass($this->getModulePath() . 'views\\' . $this->baseView);
    } else {
      $this->baseView = useClass('view\Page');
    }

    return $this->baseView;
  }

  protected function setView($key = null)
  {
    $this->setBaseView();

    $this->view = !Service::checkInstanceMainPage() ? Service::mainPage($this->baseView) : (new $this->baseView());
//    Debug($this::className(), $this->view);
//    $this->view->setTemplate(($this->default_template !== null ? $this->default_template : mb_strtolower(App::$app->module->getNameModule())) . '/' . ($this->tpl_view ? $this->tpl_view : (App::$app->module->mode ? App::$app->module->mode : 'default') . '/'));
    if ($key) {
      $this->setViewKey($key);
    }
    $this->view->setByKey('default_template', $this->default_template);
    $this->view->setByKey('tpl_view', $this->tpl_view);
    $this->setViewParams();
    $this->view->instanceByKey();
  }

  /** Запустить дефолтное действие
   *
   */
  public function show()
  {
    if ($this->model) {
      $this->model->getListData($models);
    }

    return $this->render('index', ['models' => $models ?? []]);
  }


  protected function getModulePath($modulePath = null)
  {
    if (!$modulePath) {
      $pathNameItems = [];
      foreach (explode('_', StringHelper::camelCaseToUnderscore(FileHelper::getBasename(static::className()))) as $item) {
        $pathNameItems[] = $item;
        $modulePath      = StringHelper::underscoreToCamelCase(implode('_', $pathNameItems));
        $path            = Service::autoloader()->getActualDir(paths()->modulesDir . $modulePath);
        if ($path) {
          return $path['path'] . '\\';
        }
      }
      $modulePath = App::$app->getModule()->getModulePath();
    }

    return paths()->modulesDir . $modulePath . '\\';
  }


  /** Рендеринг view
   *
   * @param string $view   - подключаемый вид
   * @param array  $params - передаваемые параметры в него
   *
   * @return mixed
   */
  public function render($view = 'default', $params = [])
  {
    return $this->view->render($view, $params);
  }

  /**
   *  Редерект по адресу
   *
   * @param       $path
   * @param array $param
   */
  public function redirect($path, $param = [])
  {
    $path = Service::url($path, false);
    if ($param) {
      foreach ($param as $key => $value) {
        $path->addQuery($key, $value);
      }
    }
    header('Location:' . $path->asString());
  }

  /**
   *
   */
  public function redirectDefaultAction($url = null)
  {
    if (!$url) {
      $url = $this->getDefaultUrl();
    }
    if ($this->model && $this->model->hasErrors()) {
      $this->view->addMessages($this->model->getErrors(), 'error');
    }
    if ($this->view->issetMessages()) {
      $this->view->saveMessageInSession();
    }

    return $this->redirect($url, (array)Service::request()->load(['mode', 'component', 'module']));
  }

  /** Создать элемент
   *
   * @return mixed
   * @throws InvalidConfigException
   */
  public function create()
  {
    return $this->update(true);
  }

  /** Обновить элемент
   *
   * @param bool $new
   *
   * @return mixed
   * @throws InvalidConfigException
   */
  public function update($new = false)
  {
    $this->model = new $this->base_model(Service::request()->_($this->model->getPrimaryKey()));

    if ($this->model->hasErrors()) {
      return $this->redirectDefaultAction();
    }
    if ($this->model->load(Service::request()->_post())) {
      if ($this->model->save()) {
        $this->view->addMessage(
          lang('message_element_base_' . ($new ? 'create' : 'update'), 'message_success'),
          'success'
        );

        return $this->redirectDefaultAction();
      }
    }
    if (method_exists($this, 'edit')) {
      return $this->edit();
    }

    return $this->render('_form', ['model' => $this->model, 'baseUrl' => $this->getDefaultUrl()]);
  }

  /**
   * Удалить элемент
   *
   * @throws InvalidConfigException
   */
  public function remove()
  {
    $this->model = new $this->base_model(Service::request()->_($this->model->getPrimaryKey(),
      ($this->segments[$this->model->getPrimaryKey()] ?? null)));
    if ($this->model->hasErrors()) {
      $this->redirectDefaultAction();
    }
    if ($this->model->remove()) {
      $this->view->addMessage(lang('message_element_base_remove', 'message_success'), 'success');
      $this->redirectDefaultAction();
    }
  }


  /** Проверки перед всеми действиями
   *
   * @return bool
   */
  public function checkActionData()
  {
    return true;
  }

  public function setViewParams()
  {
    if ($this->view->key == null) {
      if ($this->_name !== null) {
        $this->setViewKey(StringHelper::camelCaseToUnderscore($this->_name));
      } else {
        $this->setViewKey();
      }
    }
  }

  protected function setViewKey($key = 'index')
  {
    $this->view->key = $key;
    if (!Service::structure()->getCurrentPageKey()) {
      Service::structure()->setCurrentPageKey($key);
    }
  }

  public function setViewParam($key, $param = null)
  {
    if ($key && property_exists($this->view, $key)) {
      $this->view->{$key} = $param;
    }
  }

  protected function getDefaultUrl()
  {
    return site_url($this->view->href ?: \Service::structure()->getPageHrefByKey($this->view->key));
  }
}
