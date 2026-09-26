<?php

namespace AC\core\modules\membershipFees\controllers\admin;

use AC\app\locators\Service;
use AC\core\modules\accounts\controllers\admin\AccountsController;
use AC\core\modules\membershipFees\MembershipFeesModule;
use AC\core\modules\membershipFees\models\MembershipFeesAccountModel;
use AC\core\modules\membershipFees\models\MembershipFeesClientModel;
use AC\core\modules\membershipFees\models\MembershipFeesItemAccountModel;
use AC\core\system\helpers\LayoutHelper;

class MembershipFeesAccountsController extends AccountsController
{
  protected $tpl_view         = 'membershipFees';
  public    $default_template = 'accounts';
  protected $base_model       = 'MembershipFeesAccountModel';
  /**
   * @var MembershipFeesAccountModel
   */
  public $model;

  public $default_action = 'generate';

  protected $accountTypeAlias = 'membershipFees';


  /**
   * Показать журнал счетов с корректным расположением внутреннего меню в Vereinsverwaltung.
   */
  public function list()
  {
    $content = parent::list();

    return MembershipFeesModule::isManagementRequest() ? implode('', $content) : $content;
  }

  public function generate()
  {
    $textBlockValue[0] = lang('no');
    foreach ($this->model->getTextBlocks() as $textBlock) {
      $textBlockValue[$textBlock['config_id']] = $textBlock['title'];
    }
    $this->getModel('MembershipFeesGroups')->getListData($dataGroups, ['active' => 1], [], ['select'=> 'id']);
    $currentYear = (int)(Service::request()->_post('year', date('Y')));

    $clientData = $this->getModel('MembershipFeesClient')->getClientsDataByMembershipFees($currentYear,
      config('membershipFees')->verificationPropertiesMembershipFees('client_data'));
    $clients    = [];
    //Клиенты
    foreach ($clientData as $client_data) {
      if(in_array($client_data['membership_fees_group'], array_keys($dataGroups))) {
        $tmp = $client_data['surname'] . ' ' . $client_data['name'];
        if (strlen($tmp) > 75) {
          $tmp = substr($tmp, 0, 75) . '...';
        }
        $clients[$client_data['client_id']] = $tmp;
      }
    }

    return $this->render('generate_form', [
      'selectYears'      => LayoutHelper::getSelectYears((int)date('Y') - 1, (int)date('Y') + 1, $currentYear),
      'selectClients'    => !empty($clients) ? useLayout()->render(
        'select',
        [
          'name'     => 'clients[]',
          'values'   => $clients,
          'current'  => 0,
          'size'     => 5,
          'multiple' => true,
          'id'       => 'clientList'
        ],
        'common'
      ) : '',
      'selectTextConfig' => useLayout()->render(
        'select',
        [
          'name'    => 'text_config',
          'values'  => $textBlockValue,
          'current' => 0
        ],
        'common'
      ),
      'urlFormYear'      => site_url($this->getAccountsHref()),
      'urlForm'          => site_url(MembershipFeesModule::appendPath($this->getAccountsHref(), 'generateAccount')),
      'currentYear'      => $currentYear
    ]);
  }

  public function generateAccount()
  {
    if (Service::request()->checkPost('year') && Service::request()->checkPost('clients')) {
      $clients = Service::request()->_post('clients');
      $year    = Service::request()->_post('year');
      if (is_array($clients)) {
        if ($clientData = $this->getModel('MembershipFeesClient')->getClientsDataById($clients)) {
          /** @var MembershipFeesClientModel $client */
          $check = 0;
          foreach ($clientData as $client) {
            /** @var MembershipFeesAccountModel $model */
            $model = $this->getModel('MembershipFeesAccount');
            if ($model->setClientAccount($client)) {
              $model->date_start  = date('Y-m-d', strtotime($year . '-01-01'));
              $model->date_finish = date('Y-m-d', strtotime($year . '-12-31'));
              $model->text_config = Service::request()->_post('text_config');
              /** @var MembershipFeesItemAccountModel $itemAccount */
              $itemAccount                              = $this->getModel('MembershipFeesItemAccount');
              $itemAccount->price                       = $client->membership_fees_group->rate;
              $itemAccount->membership_fees_group       = $client->membership_fees_group->id;
              $itemAccount->membership_fees_group_title = $client->membership_fees_group->title;
              $model->addItemAccount($itemAccount);
              if ($model->insertAccount($error_code)) {
                $check++;
              } else {
                if ($error_code) {
                  $this->view->addMessage($error_code, 'error');
                }
              }
            }
          }
          if ($check == count($clientData)) {
            $this->view->addMessage(lang('Account generate', 'message_success'), 'success');
          } else {
            $this->view->addMessage(lang('Account not generate', 'message_error'), 'error');
          }
        } else {
          $this->view->addMessage(lang('Clients not found', 'message_error'), 'error');
        }
      } else {
        $this->view->addMessage(lang('Clients not found', 'message_error'), 'error');
      }
    } else {
      $this->view->addMessage(lang('Incorrect query', 'message_error'), 'error');
    }

    $href = MembershipFeesModule::isManagementRequest()
      ? MembershipFeesModule::asManagementHref('membershipFees/accounts/list')
      : 'membershipFees/accounts/list';
    $this->redirect(site_url($href));
  }

  public function setViewKey($key = 'index')
  {
    if (MembershipFeesModule::isManagementRequest()) {
      parent::setViewKey(MembershipFeesModule::getManagementPageKey('accounts'));
      return;
    }

    parent::setViewKey(Service::structure()->isPageKey($key . '_' . $this->action) ? $key . '_' . $this->action : $key);
  }

  public function setViewParams()
  {
    parent::setViewParams();
    $this->view->addPathToView(paths()->modulesDir . 'accounts\views\admin\default\\');
  }

  protected function getAccountsHref(): string
  {
    return MembershipFeesModule::isManagementRequest()
      ? MembershipFeesModule::asManagementHref('membershipFees/accounts')
      : 'membershipFees/accounts';
  }
}
