<?php

namespace AC\core\modules\membershipFees\controllers\admin;

use AC\app\locators\Service;
use AC\core\modules\reports\controllers\admin\ReportsController;

class MembershipFeesReportsController extends ReportsController
{
  public $default_template = 'reports';

  public function getYearOfBirthMembers()
  {
    return $this->getModel('MembershipFeesClient')->getEngine()->getTheBirthYearOfTheClubMembers();
  }

  public function setViewParams()
  {
    parent::setViewParams();
    $this->view->addPathToView(paths()->modulesDir . 'reports\views\admin\default\\');
  }

  public function membersByYearOfBirth()
  {
    $yearOfBirthMembers = Service::request()->_post('yearOfBirthMembers', 'all');
    $genderBase         = Service::request()->_post('gender', 'all');
    $titles             = [
      'title_row' => 1,
      'fields'    => [
        'title' => ['row' => 1, 'col' => 1, 'title' => lang('Year of birth', 'membership_fees_reports'), 'parent' => null, 'defaultValue' => '']
      ]
    ];


    foreach (config('gender')->getGender() as $gender => $genderTitle) {
      if ($genderBase == 'all' || $gender == $genderBase) {
        $titles['fields'][$gender] = ['row' => 1, 'col' => 1, 'title' => ucfirst($genderTitle), 'parent' => null, 'defaultValue' => 0];
      }
    }
    $titles['fields']['sum'] = ['row' => 1, 'col' => 1, 'title' => lang('In total'), 'parent' => null, 'defaultValue' => 0];

    $clients = $this->getModel('MembershipFeesClient')->getEngine()->getClientsClubMembers(['clients' => ['client_id', 'birthday']],
      [
        'clients'     => ['birthday' => ($yearOfBirthMembers != 'all' ? [['=', $yearOfBirthMembers, 'year']] : 'is not null')],
        'client_data' => ['gender' => ($genderBase != 'all' ? '="' . $genderBase . '"' : '!= "not"')]
      ],
      ['clients' => ['birthday desc']]);
    $rows    = [];
    if ($clients) {
      $totalCount = [];
      $rowKeys    = [];
      foreach ($titles['fields'] as $fieldName => $field) {
        $rowKeys[(string)$fieldName]    = $field['defaultValue'];
        $totalCount[(string)$fieldName] = $fieldName != 'title' ? $field['defaultValue'] : lang('Sum');
      }
      foreach ($clients as $client) {
        $year = date('Y', strtotime($client['birthday']));
        if (!isset($rows[$year])) {
          $rowKeys['title'] = $year;
          $rows[$year]      = $rowKeys;
        }
        $rows[$year][$client['gender']] += 1;
        $rows[$year]['sum']             += 1;
        $totalCount[$client['gender']]  += 1;
        $totalCount['sum']              += 1;
      }
      $rows[] = $totalCount;
    }

    if (Service::request()->_('action') == 'CSV') {
      return $this->giveCsvFile(['titles' => $titles, 'rows' => $rows]);
    }

    return $this->view->render('membersByYearOfBirth', [
      'title'    => config('app')->getProjectTitle(),
      'dateTime' => date('d.m.Y H:i'),
      'data'     => [
        'titles' => $this->renderTitles($titles),
        'rows'   => $rows
      ]
    ]);
  }

  public function membership()
  {
    $ageMembers = Service::request()->_post('ageMembers', 'all');
    if ($ageMembers != 'all') {
      $ageRange = [];
      foreach (explode('_', $ageMembers) as $key => $value) {
        if (empty($value)) {
          unset($ageRange[$key]);
          continue;
        }

        $ageRange[$key] = [($key == 0 ? ' <= ' : ' >= '), ((int)date('Y') - $value), 'year'];
      }
    }
    $genderBase = Service::request()->_post('gender', 'all');
    $titles     = [
      'title_row' => 1,
      'fields'    => [
        'year' => ['row' => 1, 'col' => 1, 'title' => lang('Year of birth', 'membership_fees_reports'), 'parent' => null, 'defaultValue' => lang('Sum')],
        'age'  => ['row'          => 1,
                   'col'          => 1,
                   'title'        => lang('Age of the club members', 'membership_fees_reports'),
                   'parent'       => null,
                   'defaultValue' => ''
        ]
      ]
    ];


    foreach (config('gender')->getGender() as $gender => $genderTitle) {
      if ($genderBase == 'all' || $gender == $genderBase) {
        $titles['fields'][$gender] = ['row' => 1, 'col' => 1, 'title' => ucfirst($genderTitle), 'parent' => null, 'defaultValue' => 0];
      }
    }
    $titles['fields']['sum'] = ['row' => 1, 'col' => 1, 'title' => lang('Sum'), 'parent' => null, 'defaultValue' => 0];

    $clients = $this->getModel('MembershipFeesClient')->getEngine()->getClientsClubMembers(['clients' => ['client_id', 'birthday']],
      [
        'clients'     => ['birthday' => ($ageMembers != 'all' ? $ageRange : 'is not null')],
        'client_data' => ['gender' => ($genderBase != 'all' ? '="' . $genderBase . '"' : '!= "not"')]
      ],
      ['clients' => ['birthday desc']]);
    $rows    = [];

    if ($clients) {
      $totalCount = [];
      foreach (config('report')->getAgeMembers() as $key => $titleAge) {
        $rowKeys = [];
        if ($ageMembers == 'all' || $ageMembers == $key) {
          foreach ($titles['fields'] as $fieldName => $field) {
            $rowKeys[(string)$fieldName]    = $titleAge[(string)$fieldName] ?? $field['defaultValue'];
              $totalCount[(string)$fieldName] ??= $field['defaultValue'];
          }
        }
        if (!isset($rows[$key])) {
          $rows[$key] = $rowKeys;
        }
        foreach ($clients as $client) {
          $year = date('Y', strtotime($client['birthday']));
          if(config('report')->checkTheYearOfBirthByAgeRange($year, explode('_', $key))) {
            $rows[$key][$client['gender']] += 1;
            $rows[$key]['sum']             += 1;
            $totalCount[$client['gender']]  += 1;
            $totalCount['sum']              += 1;
          }
        }
      }
      $rows['sum'] = $totalCount;
    }
    if (Service::request()->_('action') == 'CSV') {
      return $this->giveCsvFile(['titles' => $titles, 'rows' => $rows]);
    }

    return $this->view->render('membersByYearOfBirth', [
      'title'    => config('app')->getProjectTitle(),
      'dateTime' => date('d.m.Y H:i'),
      'data'     => [
        'titles' => $this->renderTitles($titles),
        'rows'   => $rows
      ]
    ]);
  }


  public function setViewKey($key = 'index')
  {
    parent::setViewKey(Service::structure()->isPageKey($key . '_' . $this->action) ? $key . '_' . $this->action : $key);
  }

}