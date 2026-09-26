<?php

namespace AC\app\controllers;

use AC\core\modules\stocks\engines\StocksGroupsEngine;
use AC\core\system\actions\admin\show_table\RowList;
use AC\core\system\controller\BaseController;
use AC\core\system\helpers\StringHelper;
use Service;

class AdminController extends BaseController
{
  public $admin = true;
  protected function backButton($backButtonUrl = null): string
  {

    return useLayout()->render('backButton', ['backButtonUrl' => $backButtonUrl ?? $this->getDefaultUrl()]);
  }


  /** Получить данных как таблицу
   * @return string
   */
  protected function showListTable(): string
  {
    $className = $this->searchActualAction('show_table\ShowList');

    return useClass($className, true, $this->model, $this->getRowListClass())
      ->setProperties($this->commonProperties())
      ->getRenderTableList($this->getDataForTableList());
  }

  protected function commonProperties(): object
  {
    return new \stdClass();
  }

  protected function getRowListClass(...$arguments): RowList
  {
    return useClass($this->searchActualAction('show_table\RowList'), true, ...$arguments);
  }


  /** Данные для создания таблицы списка
   * @return array
   */
  protected function getDataForTableList(): array
  {
    return [];
  }

  protected function markExecution()
  {
    $baseActionName = paths()->actionsDir . 'MarkExecution';
    $className      = $baseActionName;

    return useClass($className, true, $this->model);
  }

  protected function searchActualAction($actionName = ''): string
  {
    if (!empty($actionName)) {
      $_names = explode('_', StringHelper::camelCaseToUnderscore($this->_name));
      $search = array_merge(
        Service::locator()->search(paths()->actionsDir . paths()->getTemplateDir() . $actionName . $this->_name),
        Service::locator()->search(paths()->actionsDir . $actionName . $this->_name),
        Service::locator()->search(paths()->actionsDir . paths()->getTemplateDir() . $actionName . $_names[0]),
        Service::locator()->search(paths()->actionsDir . $actionName . $_names[0]),
        Service::locator()->search(paths()->actionsDir . paths()->getTemplateDir() . $actionName),
        Service::locator()->search(paths()->actionsDir . $actionName)
      );
      
      foreach ($search as $path) {
        return Service::locator()->getClassname($path);
      }
    }

    return '';
  }

  public function active()
  {
    /** @var StocksGroupsEngine $engine*/
    $engine = $this->model->getEngine();
    $engine->getDataById(Service::request()->_($this->model->getPrimaryKey()), $data);
    $data->active = (int)!$data->active;
//    Debug($data);
    $engine->active($data);

    return $this->redirectDefaultAction();
  }

}