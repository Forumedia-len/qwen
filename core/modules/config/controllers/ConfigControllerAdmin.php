<?php

namespace AC\core\modules\config\controllers;

use AC\core\system\controller\BaseController;

use AC\core\system\helpers\ObjectHelper;


use AC\core\modules\areas\models\AreasModel;
use AC\core\modules\config\models\ConfigModel;
use Service;

class ConfigControllerAdmin extends BaseController
{
  public    $default_action   = 'showList';
  public    $tpl_view         = 'config';
  protected $base_model       = 'ConfigModel';
  public    $default_template = 'default';
  /**
   * @var ConfigModel
   */
  public $model;


  public function setViewParams()
  {
    $this->view->key = 'config_' . Service::request()->_('mode', '');
  }

  public function checkActionData()
  {
    return true;
  }

  public function showList()
  {
    $this->model->getListData($data);
    $models = ObjectHelper::createObject(array_keys($data));
    $models->loadParams($data);

    return $this->render(
      'index',
      array(
        'models'      => (object)$models,
        'active_type' => AreasModel::selectActiveType()
      )
    );
  }

  public function update($new = false)
  {
    $out = parent::update($new);
    if (Service::request()->_('action') !== null) {
      $out = array($out, $this->backButton());
    }

    return $out;
  }

  public function save()
  {
    $data = Service::request()->load($this->model->getTypes());
    if ($this->model->load($data)) {
      if ($this->model->save()) {
        $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
      }
    }
    if ($this->model->isErrors()) {
      $this->view->addMessages($this->model->getErrors(), 'error');
    }

    return $this->showList();
  }

  public function backButton()
  {
    return '<span class="back"><a href="config.php' . (Service::request()->_('mode') ? '?mode=' . Service::request()->_('mode') : '') . '">' . lang(
        'back'
      ) . '</a></span>';
  }


}