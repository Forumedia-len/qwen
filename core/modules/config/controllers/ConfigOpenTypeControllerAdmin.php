<?php

namespace AC\core\modules\config\controllers;

use AC\core\modules\config\models\ConfigOpenTypeModel;
use Service;

class ConfigOpenTypeControllerAdmin extends ConfigControllerAdmin
{
  public    $default_action   = 'showList';
  public    $default_template = 'open_type';
  protected $base_model       = 'ConfigOpenTypeModel';

  /**
   * @var ConfigOpenTypeModel
   */
  public $model;

  protected function setView($key = null)
  {
    parent::setView($key);
    $tabs = $this->model->buildAdminContentMenuItems();
    if ($tabs !== []) {
      $this->view->content_menu = [0 => $tabs];
    }
  }

  public function showList()
  {
    $typeId = $this->model->resolveAdminTypeId();
    $models = $this->model->getAdminOpenModel($typeId);
    $out    = [
      $this->showDoubleGame($typeId),
      $this->render(
        'index',
        [
          'models'            => $models,
          'TypePricingSystem' => $this->model->getTypePricingSystem(),
          'type_id'           => $typeId,
        ]
      ),
    ];

    if (!empty($models->type_pricing_system)) {
      $options = $this->model->getAdminOptionsModel($typeId, $models->type_pricing_system);
      $out[]   = $this->showOptionsTypePricingSystem($models->type_pricing_system, $options, $typeId);
    }
    $out[] = $this->showCombinationPlayers($typeId);

    return $out;
  }

  public function showOptionsTypePricingSystem($type, $options, ?int $typeId = null)
  {
    $typeId   = $typeId ?? $this->model->resolveAdminTypeId();
    $system   = $this->model->getTypePricingSystem()[$type];
    $template = '_' . $system->alias;

    return $this->render(
      $template,
      [
        'models'  => $options,
        'type'    => 'options_' . $type,
        'type_id' => $typeId,
        'caption' => lang('title_options_for_pricing_system_PFP', 'config_open_type') . '  <b>' . $system->id . '</b>  ',
      ]
    );
  }

  public function save()
  {
    $typeId = $this->model->resolveAdminTypeId();
    $open   = Service::request()->_('open');
    if (is_array($open)
      && !empty($open['type_pricing_system'])
      && $this->model->saveTypePricingSystem($typeId, (string)$open['type_pricing_system'])) {
      $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
    }

    return $this->showList();
  }

  public function saveCombination()
  {
    $typeId       = $this->model->resolveAdminTypeId();
    $combinations = Service::request()->_post('combinations');
    if (!is_array($combinations)) {
      $combinations = [];
    }
    if ($this->model->setCombinationsOfPlayers($combinations, $typeId)) {
      $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
    }

    $this->redirectDefaultAction();
  }

  public function saveOptionSystemPrice()
  {
    $typeId = $this->model->resolveAdminTypeId();
    $type   = Service::request()->_('type_option');
    $data   = Service::request()->_($type);
    if (!is_array($data)) {
      $data = [];
    }
    if ($this->model->saveOptionsSystemPrice($type, $data, $typeId)) {
      $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
    }

    $this->redirectDefaultAction();
  }

  public function showDoubleGame(?int $typeId = null)
  {
    $typeId = $typeId ?? $this->model->resolveAdminTypeId();

    return $this->render(
      '_double',
      [
        'fields'  => $this->model->buildAdminDoubleFields($typeId),
        'type_id' => $typeId,
      ]
    );
  }

  public function saveDouble()
  {
    $typeId = $this->model->resolveAdminTypeId();
    $double = Service::request()->_post('double');
    if (!is_array($double)) {
      $double = [];
    }
    if ($this->model->saveDoubleGame($typeId, $double)) {
      $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
    }

    $this->redirectDefaultAction();
  }

  public function showCombinationPlayers(?int $typeId = null)
  {
    $typeId = $typeId ?? $this->model->resolveAdminTypeId();

    return $this->render(
      '_combining',
      array_merge(
        $this->model->getCombinationsOfPlayers(false, $typeId),
        ['type_id' => $typeId],
      ),
    );
  }

  public function redirectDefaultAction($url = null)
  {
    $params = (array)Service::request()->load(['mode', 'component', 'module', 'type_id']);
    if ($url === null) {
      $url = $this->getDefaultUrl();
    }
    if ($this->model && $this->model->isErrors()) {
      $this->view->addMessages($this->model->getErrors(), 'error');
    }
    if ($this->view->issetMessages()) {
      $this->view->saveMessageInSession();
    }

    return $this->redirect($url, $params);
  }
}
