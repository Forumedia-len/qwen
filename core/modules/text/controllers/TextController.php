<?php

namespace AC\core\modules\text\controllers;

use AC\core\modules\text\models\TextModel;
use AC\core\system\controller\BaseController;
use Service;

class TextController extends BaseController
{
  protected $tpl_view     = 'default';
  /**
   * @var TextModel
   */
  public $model;

  protected function setViewKey($key = 'index')
  {
    parent::setViewKey(Service::structure()->isPageKey($key . '_' . $this->getSegmentByKey('alias')) ? $key . '_' . $this->getSegmentByKey('alias') : $key);
  }

  public function show()
  {
    $content = '';
    if (($alias = $this->getSegmentByKey('alias'))) {
      $content = $this->model->getText($alias)->content;
    }
    
    return $this->render('index', ['content' => $content]);
  }
}