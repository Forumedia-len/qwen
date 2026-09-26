<?php

namespace AC\core\modules\membershipFees\controllers\admin;

use AC\app\controllers\AdminController;
use AC\core\modules\membershipFees\actions\FormsReport;
use AC\core\modules\membershipFees\models\ClientDataModel;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use Service;

/**
 * Собирает существующие функции управления клубом в одном административном разделе.
 */
class MembershipFeesManagementController extends AdminController
{
  public $default_template = 'management';
  protected $useBaseModel = false;

  /**
   * Показать форму назначения группы членского взноса.
   */
  public function show(): string
  {
    $clients = ModCommHelper::get(
      'clients',
      'Clients/getListNamesClients',
      ['addOptions' => true],
      'data',
      []
    );
    $groups = [0 => '- ' . lang('None') . ' -'];
    $this->getModel('MembershipFeesGroups')->getListData($groupModels, ['active' => 1]);
    foreach ($groupModels as $group) {
      $groups[$group->id] = $group->title . ' : ' . $group->rate . CURR_VALUTE;
    }

    return $this->render('setup', [
      'clients'              => [0 => '-- ' . lang('Select name', 'membership_fees_management') . ' --'] + (array)$clients,
      'groups'               => $groups,
      'selectedClientId'     => (int)Service::request()->_post('client_id', 0),
      'selectedGroupId'      => (int)Service::request()->_post('membership_fees_group', 0),
      'assignUrl'            => site_url('membershipFees/management/assign'),
      'createClientUrl'      => site_url('clients.php?action=insertClientForm&vereinsverwaltung=1'),
    ]);
  }

  /**
   * Назначить выбранному клиенту группу членского взноса.
   */
  public function assign(): mixed
  {
    $clientId = (int)Service::request()->_post('client_id', 0);
    $groupId = (int)Service::request()->_post('membership_fees_group', 0);
    $clients = (array)ModCommHelper::get(
      'clients',
      'Clients/getListNamesClients',
      ['addOptions' => true],
      'data',
      []
    );
    $this->getModel('MembershipFeesGroups')->getListData($groupModels, ['active' => 1]);
    $groups = array_fill_keys(array_map(static fn($group) => (int)$group->id, $groupModels), true);

    if (!$clientId || !isset($clients[$clientId]) || ($groupId !== 0 && !isset($groups[$groupId]))) {
      $this->view->addMessage(lang('Invalid assignment data', 'membership_fees_management'), 'error');
    } elseif ((new ClientDataModel())->assignMembershipFeesGroup($clientId, $groupId)) {
      $this->view->addMessage(lang('Contribution group assigned', 'membership_fees_management'), 'success');
    } else {
      $this->view->addMessage(lang('Contribution group not assigned', 'membership_fees_management'), 'error');
    }

    return $this->redirectDefaultAction();
  }

  /**
   * Показать форму отчёта по годам рождения.
   */
  public function membersByYearOfBirth(): string
  {
    return $this->centerReportForm((new FormsReport())->showMembersByYearOfBirthForm());
  }

  /**
   * Показать форму отчёта по составу членов клуба.
   */
  public function membership(): string
  {
    return $this->centerReportForm((new FormsReport())->showMembershipForm());
  }

  /**
   * Проверить доступ к действиям нового административного раздела.
   */
  public function checkActionData(): bool
  {
    return Service::auth()->checkRights(2);
  }

  /**
   * Настроить ключ страницы в зависимости от выбранной вкладки.
   */
  public function setViewParams(): void
  {
    $pageKey = match ($this->action) {
      'membersByYearOfBirth' => 'membership_fees_management_members_by_year_of_birth',
      'membership'           => 'membership_fees_management_membership',
      default                => 'membership_fees_management_setup',
    };
    $this->setViewKey($pageKey);
  }

  private function centerReportForm(string $form): string
  {
    return '<div class="membership-fees-management-report">' . $form . '</div>';
  }
}
