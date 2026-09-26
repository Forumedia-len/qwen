<?php

namespace AC\core\modules\config\controllers;


use AC\core\system\helpers\JsonHelper;


use AC\core\modules\areas\models\AreasModel;
use AC\core\modules\webIo\models\WebIoModel;
use AC\core\modules\webIo\models\WebIoOutputsModel;
use AC\core\modules\webIo\models\WebIoTypeSatesModel;
use Service;

class ConfigWebIoControllerAdmin extends ConfigControllerAdmin
{
  protected $base_model       = 'WebIoModel';
  public    $default_template = 'webIo';

  /**
   * @var WebIoModel
   */
  public $model;

  public function setBaseModel($modelName = null, $module = null)
  {
    return parent::setBaseModel($modelName, 'webIo');
  }

  /**
   * {@inheritDoc}
   */
  public function showList()
  {
    $out   = array();
    $model = new WebIoModel();
    $out[] = $this->render('index', array('models' => $model->getAllWebIo()));
    $out[] = $this->showWebIo($model);

    return $out;
  }

  public function edit()
  {
    $model = new WebIoModel(Service::request()->_('id'));

    return array($this->backButton(), $this->showOutput($model), $this->showWebIo($model), $this->backButton());
  }

  /**
   *  Показать данные конфига
   * @return mixed
   */
  public function showWebIo($model)
  {
    return $this->render('_form', array('model' => $model));
  }

  /**
   *  Отобразить таблицу с выбором данных webIo в отношении порта
   * @return mixed
   */
  public function showOutput($model)
  {
    return $this->render(
      'output',
      array(
        'model'         => $model,
        'webIo_types'   => WebIoTypeSatesModel::getTypeSates('alias', true, false),
        'areas'         => AreasModel::all(),
        'webIo_outputs' => WebIoOutputsModel::getByWebIoId($model->id),
      )
    );
  }

  /**
   *  Обновить данные для конфига
   */
  public function save()
  {
    $request = Service::request()->all();

    $request['pre_start_time'] = JsonHelper::encode($request['pre_start_time']);
    if ($this->model->load((object)$request)) {
      if ($this->model->save()) {
        $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
      }
    }

    $this->redirectDefaultAction();
  }

  public function updateOutput()
  {
    $types   = Service::request()->_('type', array());
    $outputs = array();
    foreach (Service::request()->_('area', array()) as $port => $area) {
      if ($area != 0 && $types[$port] != 0) {
        foreach ($area as $area_id) {
          $outputs[] = array('area_id' => $area_id, 'webio_type_id' => $types[$port], 'port' => $port);
        }
      }
    }
    if (WebIoOutputsModel::save(Service::request()->_('webio_id'), $outputs)) {
      $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
    }
    $this->redirectDefaultAction();
  }

}