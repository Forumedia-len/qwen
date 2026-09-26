<?php

namespace AC\core\modules\membershipFees\actions;

use AC\core\modules\accounts\actions\ShowListArchivedAccount;
use AC\core\modules\membershipFees\MembershipFeesModule;
use AC\core\system\helpers\ObjectHelper;

class ShowListArchivedAccountMembershipFees extends ShowListArchivedAccount
{
  protected string $accountTypeAlias = 'membershipFees';

  /**
   * Сохранить контекст Vereinsverwaltung в ссылках навигации по годам.
   */
  protected function getNavigationYearsLine($archive = false)
  {
    if (!MembershipFeesModule::isManagementRequest()) {
      return parent::getNavigationYearsLine($archive);
    }

    $out = ObjectHelper::createObject();
    if (($date = $this->accountsModel->getMinMaxDateAndCurrentYear($archive)) && $date->minDate && $date->maxDate) {
      $menu = [];
      for ($year = date('Y', strtotime($date->maxDate)); $year >= date('Y', strtotime($date->minDate)); $year--) {
        $href = 'membershipFees/accounts/archives/year/' . $year . '/type/' . $this->areaType;
        $menu[] = ObjectHelper::createObject([
          'key'   => $year,
          'title' => $year,
          'href'  => site_url(MembershipFeesModule::asManagementHref($href)),
        ], true);
      }
      $out->text = useLayout()->render('menu/link_menu', [
        'menu'        => $menu,
        'currentItem' => $date->currentYear,
      ], 'admin');
    }

    return $out;
  }
}
