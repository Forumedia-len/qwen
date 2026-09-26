<?php

namespace AC\core\modules\membershipFees\actions;

use AC\app\locators\Service;
use AC\core\modules\reports\actions\FormsReport as FormsReportBase;
use AC\core\modules\reports\helpers\LayoutReportHelper;
use AC\core\system\helpers\ObjectHelper;

class FormsReport extends FormsReportBase
{
  /**
   * Получить все формы отчётов модуля для общей страницы отчётов.
   */
  public function showForms(): array
  {
    $this->prepareView();
    
    return [$this->membersByYearOfBirthForm(), $this->membershipForm()];
  }

  /**
   * Получить отдельную форму отчёта по годам рождения.
   */
  public function showMembersByYearOfBirthForm(): string
  {
    $this->prepareView();

    return $this->membersByYearOfBirthForm();
  }

  /**
   * Получить отдельную форму отчёта по составу членов клуба.
   */
  public function showMembershipForm(): string
  {
    $this->prepareView();

    return $this->membershipForm();
  }
  
  protected function membersByYearOfBirthForm()
  {
    $rows[] = ObjectHelper::createObject([
      'title' => lang('Year of birth', 'membership_fees_reports'),
      'value' => $this->getYearOfBirthMembersSelect(),
    ], true);
    $rows[] = ObjectHelper::createObject([
      'title' => lang('Gender', 'membership_fees'),
      'value' => $this->getGenderSelect(),
    ], true);
    
    return view()->render('form', [
      'form'      => ObjectHelper::createObject([
        'action' => Service::structure()->getPageHrefByKey('membership_fees_reports_membersByYearOfBirth'),
        'method' => 'post',
        'target' => '_black',
      ], true),
      'tableForm' => ObjectHelper::createObject([
        'caption' => lang('membership_fees_reports_membersByYearOfBirth', 'structure'),
        'rows'    => $rows,
        'actions' => LayoutReportHelper::buttonExecute() . LayoutReportHelper::buttonCSV()
      ], true),
    ]);
  }
  
  protected function membershipForm()
  {
    $rows[] = ObjectHelper::createObject([
      'title' => lang('Age of the club members', 'membership_fees_reports'),
      'value' => $this->getAgeMembersSelect(),
    ], true);
    $rows[] = ObjectHelper::createObject([
      'title' => lang('Gender', 'membership_fees'),
      'value' => $this->getGenderSelect(),
    ], true);
    
    return view()->render('form', [
      'form'      => ObjectHelper::createObject([
        'action' => Service::structure()->getPageHrefByKey('membership_fees_reports_membership'),
        'method' => 'post',
        'target' => '_black',
      ], true),
      'tableForm' => ObjectHelper::createObject([
        'caption' => lang('membership_fees_reports_membership', 'structure'),
        'rows'    => $rows,
        'actions' => LayoutReportHelper::buttonExecute() . LayoutReportHelper::buttonCSV()
      ], true),
    ]);
  }
  
  protected function getGenderSelect()
  {
    return LayoutReportHelper::radioTable('gender', array_merge(['all' => lang('All')], config('gender')->getGender()), 'all');
  }
  
  protected function getAgeMembersSelect()
  {
    return LayoutReportHelper::selectTable(
      ['ageMembers'],
      [array_merge(['all' => lang('All')], config('report')->getAgeMembers('age'))],
      ['all']);
  }
  
  protected function getYearOfBirthMembersSelect()
  {
    $YearOfBirthMembers = ['all' => lang('All')];
    foreach (module('membershipFees')->useController('MembershipFeesReportsController')->getYearOfBirthMembers() as $key => $value) {
      $YearOfBirthMembers[$key] = $value;
    }
    
    return LayoutReportHelper::selectTable(['yearOfBirthMembers'], [$YearOfBirthMembers], ['all']);
  }

  private function prepareView(): void
  {
    Service::lang()->addFile('membershipFees', paths()->modulesDir . 'membershipFees\\' . paths()->getLangDir());
    view()->addPathToView(paths()->modulesDir . 'reports\views\admin\default\\');
  }
}
