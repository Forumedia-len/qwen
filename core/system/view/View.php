<?php

namespace AC\core\system\view;

use AC\core\system\App;
use AC\core\system\helpers\FileHelper;
use Service;

/** Представления модулей и общие шаблоны интерфейса. */
class View extends MessageView
{
  public $key;
  public $title;
  public $h1;
  
  public    $useH1      = true;
  public    $content;
  protected $template   = '';
  public    $checkError = false;
  public    $css        = [];
  public    $cssCode    = [];
  public    $js         = [];
  public    $jsCode     = [];
  
  protected $tpl_view;
  protected $default_template;
  protected $priorityPaths = [];
  
  public $parent_key;
  public $default;
  public $title_image;
  public $href;
  public $id;
  public $sort;
  public $content_menu;
  
  public function __construct($params = [])
  {
    parent::__construct();
    
    foreach ($params as $key => $value) {
      $this->setByKey($key, $value);
    }
  }
  
  
  public function setTemplate($template = null)
  {
    if (!empty($template)) {
      $this->template = $template;
    }
  }
  
  public function instanceByKey($page_key = null)
  {
    if ($page_key != null) {
      $this->key = $page_key;
    }
    if ($this->key != null) {
      $structure = Service::structure();
      if ($structure->isPageKey($this->key)) {
        foreach ($structure->getPageDataByKey($this->key) as $key => $value) {
          if ($key != 'template' && $key != 'key') {
            $this->{$key} = $value;
          }
        }
      }
    }
    if (!isset($this->h1)) {
      $this->h1 = $this->title;
    }
    $this->instanceContentMenu();
    
  }
  
  private function instanceContentMenu(): void
  {
    $dataPage = Service::structure()->getPageDataByKey($this->key);
    if (isset($dataPage['content_menu']) && $dataPage['content_menu']) {
      $this->key = $dataPage['parent_key'];
    }
    if($menu = Service::structure()->getStructureContentMenu($this->key)) {
      foreach ($menu as $key => $value) {
        $this->setContentMenu($value, $key);
      }
    }
  }
  
  public function setContentMenu(?array $menu = null, int $contentKey = 0): void
  {
    $contentMenu = [];
    if(is_array($this->content_menu) && !empty($this->content_menu)) {
      $contentMenu = $this->content_menu;
    }
    if(!empty($menu)) {
      $contentMenu[$contentKey] = $menu;
    }
    if(!empty($contentMenu)) {
      $this->setByKey('content_menu', $contentMenu);
    }
  }
  
  public function getTemplate()
  {
    return FileHelper::trimFilePath($this->template);
  }
  
  public function render($views, $params = [], $fileExtension = 'php')
  {
    if (!empty($params)) {
      $params['variables']   = array_keys($params);
      $params['variables'][] = 'variables';
    }
    $viewFile = $this->getPathFile($views . '.' . $fileExtension);
    /** @todo заглушка выводящая пустую строку, возможно стоит подумать что стоит выводить */
    if (!$viewFile) {
      return '';
    }
    ob_start();
    ob_implicit_flush(false);
    extract($params, EXTR_OVERWRITE);
    echo $this->renderFile($viewFile, $params);
    
    return ob_get_clean();
  }
  
  public function renderFile($file, $params = [])
  {
    ob_start();
    ob_implicit_flush(false);
    extract($params, EXTR_OVERWRITE);
    require $file;
    
    return ob_get_clean();
  }
  
  /** Подгрузить нужный шаблон
   *  указываем прямой путь до него
   *  ищем по этрму пути сначала локальную версию,
   *  затем основную.
   *
   * @param string $files
   * @param array  $params
   *
   * @return false|string|void
   */
  public function renderer($files, $params = [])
  {
    if (!is_array($files)) {
      $files = [$files];
    }
    
    foreach ($files as $file) {
      if ($path = Service::templates()->resolvePath($file)) {
        return $this->renderFile($path, $params);
      }
    }
  }
  
  public function getToArray()
  {
    if (!isset($this->h1)) {
      $this->h1 = $this->title;
    }
    
    return (array)$this;
  }
  
  public function setErrorMessage($message)
  {
    $this->key        = 'error';
    $this->checkError = true;
    $this->title      = lang('text_error');
    $this->message->setErrorMessage($message);
  }
  
  public function setErrorCode($code)
  {
    $this->key        = 'error';
    $this->checkError = true;
    $this->title      = lang('text_error');
    $this->message->setErrorCode($code);
  }
  
  
  public function getErrorMessage()
  {
    return $this->message->getErrors();
  }
  
  public function checkErrors()
  {
    return $this->checkError;
  }
  
  public function addJsFile($fileName, $template = null, $notUseDefaultPath = false, $url = 'base'): self
  {
    $this->js[] = [$fileName, $template, $notUseDefaultPath, $url];
    return $this;
  }
  
  public function addJsCode(string $code): self
  {
    $this->jsCode[] = $code;
    return $this;
  }
  
  public function addCssCode(string $code): self
  {
    $this->cssCode[] = $code;
    return $this;
  }
  
  public function addCssFile($fileName, $template = null, $notUseDefaultPath = false, $url = 'base'): self
  {
    $this->css[] = [$fileName, $template, $notUseDefaultPath, $url];
    return $this;
  }
  
  /** Находит представление устройства, родительского интерфейса или общий шаблон. */
  public function getPathFile($fileName)
  {
    /**
     *  todo разобраться и проверить все пути файлов
     */
    $fileName         = FileHelper::trimFilePath($fileName, DIRECTORY_SEPARATOR, false);
    $tpl_view         = FileHelper::trimFilePath(isset(App::$app->module) ? App::$app->module->getModulePath() : $this->tpl_view);
    $default_template = FileHelper::trimFilePath(!empty($this->default_template) ? $this->default_template : 'default', DIRECTORY_SEPARATOR);
    $commonPath       = FileHelper::trimFilePath(paths()->getTplDir('views', 'common'), DIRECTORY_SEPARATOR);
    $modulePath       = FileHelper::trimFilePath(paths()->modulesDir . $tpl_view . 'views', DIRECTORY_SEPARATOR);
    $priority = Service::templates()->buildPaths(static function (string $device) use ($tpl_view, $default_template, $fileName, $modulePath): array {
      $deviceLocalPath = FileHelper::trimFilePath(paths()->getTplDir('views', $device), DIRECTORY_SEPARATOR);
      $devicePath = FileHelper::trimFilePath($device, DIRECTORY_SEPARATOR);
      return [
        $deviceLocalPath . $tpl_view . $default_template . $fileName,
        $deviceLocalPath . $tpl_view . $fileName,
        $deviceLocalPath . $fileName,
        $modulePath . $devicePath . $default_template . $fileName,
        $modulePath . $devicePath . $fileName
      ];
    });
    $priority = array_merge($priority, [
      $modulePath . 'common' . '\\' . $default_template . $fileName,
      $modulePath . 'common' . '\\' . $fileName,
      $modulePath . $default_template . $fileName,
      $modulePath . $fileName,
      paths()::MODULES_DIR . DIRECTORY_SEPARATOR . $fileName,
      $commonPath . $tpl_view . $default_template . $fileName,
      $commonPath . $tpl_view . $fileName,
      $commonPath . $fileName,
    ]);
    if (!empty($this->priorityPaths)) {
      foreach ($this->priorityPaths as $priorityPath) {
        array_push($priority, ...Service::templates()->buildPaths(static fn(string $device): array => [
          $priorityPath . FileHelper::trimFilePath($device, DIRECTORY_SEPARATOR) . $default_template . $fileName,
        ]));
        $priority[] = $priorityPath . $default_template . $fileName;
        $priority[] = $priorityPath . $fileName;
      }
    }
    
    return Service::locator()->findPriorityPathToFile($priority);
  }
  
  public function setByKey($key, $value)
  {
    $this->{$key} = $value;
  }
  
  public function addPathToView($path)
  {
    $this->priorityPaths[] = $path;
  }
}
