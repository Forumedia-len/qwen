<?php

namespace AC\core\modules\config\controllers;

use AC\core\modules\areas\models\AreasModel;
use AC\core\modules\config\models\ConfigEmailModel;
use Service;
use AC\core\system\helpers\JsonHelper;

class ConfigEmailControllerAdmin extends ConfigControllerAdmin
{
  protected $base_model       = 'ConfigEmailModel';
  public    $default_template = 'emails';
  /**
   * @var ConfigModel
   */
  public $model;

  protected $dataEmails = [];

  public function __construct($action = false)
  {
    $this->dataEmails = JsonHelper::decode(Service::configDB('email', 'admins_mail_for_different_areas'));
    parent::__construct($action);
  }

  public function showList()
  {
    return [
      $this->render('index', array(
        'models'     => $this->dataEmails,
        'type_sport' => AreasModel::relevantSportsByType()
      )),
      $this->showForm()
    ];
  }

  public function showForm($model = null)
  {
    if (!$model) {
      $model = $this->model;
    }

    return $this->render('_form', array(
      'model'      => $model,
      'type_sport' => AreasModel::relevantSportsByType()
    ));
  }

  public function update($new = false)
  {
    $model = new ConfigEmailModel();

    if ($model->load(\Service::request()->all())) {
      $this->dataEmails->{$model->getAlias()} = $model->getEmail();
      if ($this->save()) {
        $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
      }
    }
    if ($this->model->isErrors()) {
      $this->view->addMessages($this->model->getErrors(), 'error');
    }

    $this->redirectDefaultAction();
  }

  public function save()
  {
    $config = new ConfigModel();
    if ($config->loadByAlias(
      'admins_mail_for_different_areas',
      JsonHelper::encode($this->dataEmails),
      'email'
    )) {
      if ($config->save()) {
        return true;
      }
    }

    return false;
  }

  public function edit()
  {
    $alias = \Service::request()->_('alias');

    if (isset($this->dataEmails->{$alias})) {
      $model = new ConfigEmailModel();
      $model->load($this->dataEmails->{$alias});

      return [$this->showForm($model), $this->backButton()];
    }
    if ($this->model->isErrors()) {
      $this->view->addMessages($this->model->getErrors(), 'error');
    }

    return $this->redirectDefaultAction();
  }

  public function remove()
  {
    $alias = \Service::request()->_('alias');

    if (isset($this->dataEmails->{$alias})) {
      unset($this->dataEmails->{$alias});
      $this->save();
      $this->view->addMessage(lang('message_element_base_remove', 'message_success'), 'success');
    }
    if ($this->model->isErrors()) {
      $this->view->addMessages($this->model->getErrors(), 'error');
    }

    return $this->redirectDefaultAction();
  }
}