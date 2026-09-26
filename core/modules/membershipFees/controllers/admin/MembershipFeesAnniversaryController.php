<?php

namespace AC\core\modules\membershipFees\controllers\admin;

use AC\core\modules\membershipFees\MembershipFeesModule;
use AC\core\system\controller\BaseController;
use AC\core\system\helpers\ObjectHelper;


class MembershipFeesAnniversaryController extends BaseController
{
  public $default_template = 'anniversary';

  public function show()
  {
    return
      $this->render(
        'index',
        [
          'tableList' => [
            'titles' => $this->getTitles(),
            'rows'   => $this->getRows(),
          ]
        ]
      );
  }

  protected function getTitles()
  {
    return [
      'name'           => ['title' => lang('Name')],
      'clubEntry'      => ['title' => lang('clubEntry', 'membership_fees_anniversary')],
      'yearsInTheClub' => ['title' => lang('yearsInTheClub', 'membership_fees_anniversary')],
    ];
  }

  protected function getRows()
  {
    $clientData = $rows = [];
    foreach ($this->getModel('MembershipFeesClient')->getEngine()->getClubMemberData() as $data) {
      if((bool)$data['membership_fees_group']) {
        $clientData[strtotime($data['entry_date'])][] = $data;
      }
    }
    ksort($clientData);
    foreach ($clientData as $data) {
      foreach ($data as $row) {
        if($row['membership_fees_group'] && $row['entry_date'] && date('d.m.Y', strtotime($row['entry_date'])) == $row['entry_date']) {
          $rows[] = [
            'name'           => ObjectHelper::createObject(['class' => 'dark', 'value' => $row['surname'] . ' ' . $row['name']], true),
            'clubEntry'      => ObjectHelper::createObject(['class' => 'light', 'value' => $row['entry_date']], true),
            'yearsInTheClub' => ObjectHelper::createObject(['class' => 'dark', 'value' => date('Y') - date('Y' , strtotime($row['entry_date']. ' + 0 year'))], true),
          ];
        }
      }
    }

    return $rows;
  }

  /**
   * Сохранить исходный или новый контекст навигации для страницы юбиляров.
   */
  protected function setViewKey($key = 'membership_fees_anniversary')
  {
    if (MembershipFeesModule::isManagementRequest()) {
      $key = MembershipFeesModule::getManagementPageKey('anniversary');
    }

    parent::setViewKey($key);
  }
}
