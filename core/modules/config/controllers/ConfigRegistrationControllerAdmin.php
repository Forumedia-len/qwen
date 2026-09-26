<?php

namespace AC\core\modules\config\controllers;

use AC\core\modules\clients\models\ClientsModel;
use AC\core\modules\clients\models\ClientsRegistrationModel;
use AC\core\modules\config\models\ConfigModel;


use AC\core\system\helpers\JsonHelper;
use AC\core\system\helpers\ObjectHelper;
use Service;

class ConfigRegistrationControllerAdmin extends ConfigControllerAdmin
{
  public $default_template = 'registration';

  public function showList()
  {
    $config = new ConfigModel();
    $config->getListData($data_config);
    $config = $this->render('config', array('models' => (object)$data_config));
    /** @var ClientsRegistrationModel $registration */
    $registration        = module('clients')->useModel('clientsRegistration');
    $registration_fields = $this->render('index', array('models' => (object)$registration->getRegistrationFields('group')));
    $out                 = [$config];
    if (Service::auth()->checkRights(0)) {
      $out[] = $this->render('selecting_sport_type', array(
        'sport_by_type' => module('clients')->useModel()->getEngine()->availableSportByTypeBySeason()
      ));
    }

    return array_merge($out, [$registration_fields]);
  }


  public function saveConfig()
  {
    $model = new ConfigModel();
    $data  = Service::request()->load($model->getTypes(), array('personal_account' => ObjectHelper::createObject()));
    if ($model->load($data)) {
      if ($model->save()) {
        if ($model->isChange('registration', 'user_can_remove_abo')) {
          ClientsModel::changeParameterForAll('abo_delete', $model->getChanges('registration', 'user_can_remove_abo'));
        }
        if ($model->isChange('personal_account', 'refund_when_cancel_ticket')) {
          ClientsModel::changeParameterForAll('refund_for_ticket', $model->getChanges('personal_account', 'refund_when_cancel_ticket'));
        }
        if ($model->isChange('personal_account', 'refund_for_cancellation_reservation_paid_by_paypal')) {
          ClientsModel::changeParameterForAll(
            'refund_for_paypal',
            $model->getChanges('personal_account', 'refund_for_cancellation_reservation_paid_by_paypal')
          );
        }
//        if($model->isChange('registration', 'use_season_in_choosing_type_sport')){
//        }
        $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
      }
    }

    $this->redirectDefaultAction();
  }

  public function saveRegistrationFields()
  {
    $model = new ClientsRegistrationModel();
    $data  = Service::request()->all();
    if ($model->loadFieldsParams($data)) {
      if ($model->saveRegistrationFields()) {
        $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
      }
    }

    $this->redirectDefaultAction();
  }


  public function saveSportByType()
  {
    $model = new ConfigModel();
    $value = JsonHelper::encode(Service::request()->_post('default_values_in_selecting_sport_type', []));
    if ($model->loadByAlias('default_values_in_selecting_sport_type', $value, 'registration')) {
      if ($model->save()) {
        $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
      }
    }

    $this->redirectDefaultAction();
  }
}