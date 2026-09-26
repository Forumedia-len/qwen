<?php

namespace AC\core\modules\config\controllers;

use AC\core\system\helpers\ObjectHelper;


use AC\core\modules\areas\models\AreasModel;
use AC\core\modules\config\models\ConfigCountModel;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use Service;

class ConfigCountControllerAdmin extends ConfigControllerAdmin
{
  public function showList()
  {
    $this->model->getListData($data);
    $models = ObjectHelper::createObject(array_keys($data));
    $models->loadParams($data);
    $model_count = new ConfigCountModel;
    $model_count->loadParams();
    return $this->render(
      'index',
      [
        'models'         => (object)$models,
        'active_type'    => AreasModel::selectActiveType(),
        'sports_by_type' => ModCommHelper::get('areas', 'relevantSportsByType', [], 'sports_by_type'),
        'model_count'    => $model_count,
      ]
    );
  }
  
  public function save()
  {
    $model_count = new ConfigCountModel;
    
    $data         = Service::request()->load($this->model->getTypes());
    $data_min_max = $model_count->request();
    if ($this->model->load($data) && $model_count->load($data_min_max)) {
      if ($this->model->save() && $model_count->save()) {
        $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
      }
    }
    if ($this->model->isErrors()) {
      $this->view->addMessages($this->model->getErrors(), 'error');
    }
    
    return $this->showList();
  }
  
  
}
