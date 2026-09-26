<?php

namespace AC\core\modules\webIo\controllers;

use AC\core\system\controller\BaseController;

use AC\core\modules\webIo\models\WebIoModel;
use Service;


class WebIoControllerAdmin extends BaseController
{
  public    $default_action   = 'showList';
  protected $base_model       = 'WebIoModel';
  public    $default_template = 'default';
  /**
   * @var WebIoModel
   */
  public $model;

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
    $this->view->key = 'webIo_' . Service::request()->_('mode', '');
  }

  public function showList()
  {
    return '';
  }
}