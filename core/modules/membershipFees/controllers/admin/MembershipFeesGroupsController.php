<?php

namespace AC\core\modules\membershipFees\controllers\admin;

use AC\app\controllers\AdminController;
use AC\core\modules\membershipFees\MembershipFeesModule;
use AC\core\modules\membershipFees\models\MembershipFeesGroupsModel;
use AC\core\system\exceptions\InvalidConfigException;
use AC\core\system\helpers\ObjectHelper;

class MembershipFeesGroupsController extends AdminController
{
  public    $default_template = 'groups';
  protected $base_model       = 'MembershipFeesGroupsModel';
  /**
   * @var MembershipFeesGroupsModel
   */
  public $model;

  public function show()
  {
    $this->model->getListData($data, ['active' => '1']);
    $models = ObjectHelper::createObject(array_keys($data));
    $models->loadParams($data);
    $out[] = $this->render(
      'index',
      [
        'models'  => (object)$models,
        'baseUrl' => $this->getDefaultUrl()
      ]
    );
    if(!config('membershipFees')->maxCountGroups()
      || count($models->getProperties()) < config('membershipFees')->maxCountGroups()) {
      $out[] = $this->create();
    }

    return $out;
  }

  /** Обновить элемент
   *
   * @param bool $new
   *
   * @return mixed
   * @throws InvalidConfigException
   */
  public function update($new = false)
  {
    $out = parent::update($new);

    return $new ? $out : [$out, $this->backButton()];
  }


  public function automaticTransitionGroup($clients = [])
  {
    $clients = $this->getModel('ClientData')->getClientsData($clients ?? $this->getSegmentByKey('clients'));
    $dataGroups = $this->getModel('MembershipFeesGroups')->all([], [], ['argument' => '']);

    foreach ($clients as $client_id => $client) {
      if(!isset($client['membership_fees_group']) || !isset($client['auto_transition_group']) || !$client['auto_transition_group']['value']) {
        unset($clients[$client_id]);
      }
    }
  }

  /**
   * Сохранить исходный или новый контекст навигации для страницы групп.
   */
  protected function setViewKey($key = 'membership_fees_groups')
  {
    if (MembershipFeesModule::isManagementRequest()) {
      $key = MembershipFeesModule::getManagementPageKey('groups');
    }

    parent::setViewKey($key);
  }
}
