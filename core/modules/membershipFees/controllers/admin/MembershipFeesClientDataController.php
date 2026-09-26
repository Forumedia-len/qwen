<?php

namespace AC\core\modules\membershipFees\controllers\admin;

use AC\app\locators\Service;
use AC\core\modules\membershipFees\models\ClientDataModel;
use AC\core\modules\membershipFees\models\MembershipFeesClientDataModel;
use AC\core\modules\membershipFees\models\MembershipFeesGroupsModel;
use AC\core\system\controller\BaseController;

use AC\core\system\helpers\ObjectHelper;

class MembershipFeesClientDataController extends BaseController
{
  protected $tpl_view         = 'membershipFees';
  public    $default_template = 'client_data';
  protected $base_model       = 'MembershipFeesClientDataModel';
  /**
   * @var MembershipFeesClientDataModel
   */
  public $model;

  public function viewForm()
  {
    $client                 = $this->getSegmentByKey('client');
    $membership_fees_groups = [0 => '- ' . lang('None') . ' -'];
    $this->getModel('MembershipFeesGroups')->getListData($dataGroups, ['active' => 1]);
    foreach ($dataGroups as $dataGroup) {
      $membership_fees_groups[$dataGroup->id] = $dataGroup->title . ' : ' . $dataGroup->rate . CURR_VALUTE;
    }
    $data = $this->getClientData($client['client_id'] ?? null);
    return $this->render('_form', [
      'data' =>
        [
          'gender'                 => [
            'name'    => 'gender',
            'values'  => array_merge(['not' => '- ' . lang('None') . ' -'], config('gender')->getGender()),
            'current' => $data['gender']->value ?? 'not',
          ],
          'membership_fees_groups' => [
            'name'    => 'membership_fees_group',
            'values'  => $membership_fees_groups,
            'current' => $data['membership_fees_group']->value ?? 0,
          ],
          'entry_date'             => $data['entry_date']->value ?? '',
          'actual_age'             => $this->getActualAge($client),
          'auto_transition_group'  => isset($data['auto_transition_group']->value) && $data['auto_transition_group']->value ? 'checked' : '',
          'disabled' => $client['club_state'] > 1,
        ]
    ]);
  }

  public function update($new = false)
  {
    $client_id = Service::request()->_('client_id', $this->getSegmentByKey('client_id'));
    $data      = $this->getClientData($client_id);
    if ($this->model->load(\Service::request()->_post())) {
      foreach ($this->model->attributes(true) as $attribute) {
        $clientData = $this->getModel('ClientData');

        if ($clientData->load(
          [
            'id' => $data[$attribute]->id ?? null,
            'client_id' => $client_id,
            'name' => $attribute,
            'value' => $this->model->{$attribute},
          ]
        )) {
          $clientData->save();
        }
      }
    }
  }

  public function remove()
  {
    $this->getModel('ClientData')->removeClientData(Service::request()->_('client_id'));

  }

  protected function getActualAge($client)
  {
    return isset($client['birthday']) ? (date('Y') - date('Y', strtotime($client['birthday']))) : '';
  }

  protected function getClientData($client_id)
  {
    $data = [];
    foreach ($this->getModel('ClientData')->getClientData($client_id) as $clientData)
    {
      $data[$clientData->name] = $clientData;
    }
    return $data;
  }
}