<?php

namespace AC\core\modules\stocks\controllers\admin;

use AC\app\controllers\AdminController;
use AC\core\engines\StocksEngine;
use AC\core\modules\stocks\entities\entity\StocksGroup;
use AC\core\system\helpers\ObjectHelper;
use Service;

class StocksGroupsController extends AdminController
{
  public    $default_action = 'showList';
  protected $base_model     = 'StocksGroupsModel';
  
  public $tpl_view         = 'groups';
  public $default_template = 'groups';
  
  public function showList(): array
  {
    return [$this->list(), $this->form($this->getEntity())];
  }
  
  /**
   * @return mixed
   * @todo переделать на ShowList
   */
  protected function list()
  {
    $data = [];
    $this->model->getEngine()->getData($data);
    
    return $this->render('list', ['groups' => $data, 'types' => $this->getTypes()]);
  }
  
  protected function form($item)
  {
    return $this->render('form',
      [
        'action' => $this->view->href . '/' . (!empty($item->id) ? 'update' : 'create'),
        'item'   => $item,
        'types'  => $this->getTypes()
      ]);
  }
  
  public function edit($item = null): array
  {
    /** @var StocksGroup $item */
    if (!$item) {
      $item = $this->getEntity(Service::request()->_('id'));
    }
    
    return [$this->form($item), $this->backButton()];
  }
  
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
    $item = $this->getEntity();
    $item->addProperties(Service::request()->_post());
    
    return $this->edit($item);
  }
  
  public function option()
  {
    $idGroup = Service::request()->_('id');
    /** @var StocksEngine $stocksEngine */
    $stocksEngine = $this->getModel('stocks')?->getEngine();
    $stocksGroup  = $this->getEntity($idGroup);
    
    if (Service::request()->isPost() && $idGroup) {
      if (Service::request()->_post('act') == 'addOptions'
        && ($stocks = Service::request()->_post('stocks'))
        && $stocksEngine->addGroup($idGroup, $stocks)) {
        $this->view->addMessage(
          lang('team assigned', 'message_success'),
          'success'
        );
      }
      if (Service::request()->_post('act') == 'condition') {
        $this->checkCondition($stocksGroup);
      }
    }
    
    return [
      $this->optionForm($idGroup),
      $stocksGroup->getType()->getConditionAsHtml($stocksGroup),
      $this->backButton()
    ];
  }
  
  protected function optionForm($idGroup = null)
  {
    /** @var StocksController $stocksController */
    /** @var StocksEngine $stocksEngine */
    $stocksEngine = $this->getModel('stocks')?->getEngine();
    
    return $this->render('groups/option', [
      'action' => Service::structure()->getPageHrefByKey('stocks_groups') . '/option/id/' . $idGroup,
      'stocks' => module('stocks', [
        'useRouting' => false,
        'params'     => ['selectedStocks' => $stocksEngine->getStockByGroup($idGroup)]
      ], false)->execContent('getStocksAsSelect')
    ]);
  }
  
  public function condition(): array
  {
    $stocksGroup = $this->getEntity(Service::request()->_('id'));
    $this->checkCondition($stocksGroup);
    
    return [$stocksGroup->getType()->getConditionAsHtml($stocksGroup), $this->backButton(Service::structure()->getPageHrefByKey('config_stocks'))];
  }
  
  protected function checkCondition(StocksGroup $stocksGroup): bool
  {
    if (Service::request()->_post('act') == 'condition') {
      if (!$stocksGroup->getType()->updateCondition($stocksGroup, $error)) {
        $this->view->addMessage(
          $error,
          'error'
        );
        return false;
      }
      
      $this->view->addMessage(
        lang('message_element_base_update', 'message_success'),
        'success'
      );
    }
    
    return true;
  }
  
  public function getEntity($id = null): StocksGroup
  {
    if (!$this->model) {
      $this->setBaseModel();
    }
    $entity = ObjectHelper::createEntity('stocksGroup');
    if (!empty($id)) {
      $this->model->getEngine()->getDataById($id, $entity, ['argument' => get_class($entity)]);
    }
    $entity->setTypes($this->getTypes());
    
    return $entity;
  }
  
  public function getTypes(): array
  {
    return $this->model->getEngine()->getTypes();
  }
}