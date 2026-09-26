<?php

namespace AC\core\modules\accounts\controllers\admin;

use AC\core\modules\accounts\actions\MarkExecutionAccount;
use AC\core\modules\accounts\models\AccountsModel;
use AC\core\system\controller\BaseController;
use Service;

class AccountsController extends BaseController
{
  protected $accountTypeAlias = '';

  /**
   * @var AccountsModel
   */
  public $model;

  public function list()
  {
    if($action = Service::request()->checkPregMatchPost('submit_')){
      $outData[] = $this->getMarkExecutionAction()->markExecution(str_replace('submit_', '', $action));
    }
    $outData[] = $this->getShowListAccount();

    return $outData;
  }

  public function archives()
  {
    if($action = Service::request()->_get('action')){
      $this->getMarkExecutionAction()->markExecution($action);
    }
    return $this->getShowListAccount(true);
  }

  protected function getShowListAccount($archive = false)
  {
    $baseActionName = $archive ? paths()->actionsDir . 'ShowListArchivedAccount' : paths()->actionsDir . 'ShowListAccount';
    $className      = $baseActionName;
    if (useClass($baseActionName . ucfirst($this->accountTypeAlias))) {
      $className = $baseActionName . ucfirst($this->accountTypeAlias);
    }

    return useClass($className, true, $this->model)->getRenderTableList($this->getDataForTableList($archive));
  }

  protected function getDataForTableList($archive = false)
  {
    return $this->model->getEngine()->getAccounts($archive, ($archive ? $this->model->getCurrentYear($archive) : false), Service::request()->_get('type', module('areas')->useModel()->getFirstActiveType())) ?: [];
  }

  public function setViewParams()
  {
    parent::setViewParams();
    $this->view->addJsFile('accounts');
    $this->view->addJsFile('clients', 'admin', false, 'cdn');
    $this->view->addJsFile('visualeditor/ckeditor');
    $this->view->addJsFile('popup');
  }

  public function delete()
  {

  }

  protected function getMarkExecutionAction():MarkExecutionAccount
  {
    $baseActionName = paths()->actionsDir . 'MarkExecutionAccount';
    $className      = $baseActionName;
    if (useClass($baseActionName . ucfirst($this->accountTypeAlias))) {
      $className = $baseActionName . ucfirst($this->accountTypeAlias);
    }

    return useClass($className, true, $this->model);
  }
}