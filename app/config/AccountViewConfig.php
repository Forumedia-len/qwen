<?php

namespace AC\app\config;

use AC\core\system\config\BaseConfig;

class AccountViewConfig extends BaseConfig
{
  public $showClientNumberInAddress = false;
  public $useSprintf = true;

  public $viewNullPrice = false;

  public function footer()
  {

  }
  public function getNumberAccount($numberAccount, $prefix, $formatSprintf = "%06d"): string
  {
    return $prefix . ($this->useSprintf ? sprintf($formatSprintf,$numberAccount) : $numberAccount);
  }
  public function getNumberAccountById($accountId, $prefix, $formatSprintf = "%06d") : string
  {
    return $this->getNumberAccount(getEngine('accounts', false)?->getNumberByAccountId($accountId), $prefix, $formatSprintf);
  }

  public function getSpaceBeforeMainText(): int
  {
    return 25;
  }

  public function getLeftMargin(): int
  {
    return 20;
  }

  public function verticalPositionClientData(): int
  {
    return 55;
  }

  public function additionalTextFooter(): string
  {
    return '';
  }

  public function marginTop(): string
  {
    return 77;
  }

  /**
   * Выводить блок клиента справа, а блок организации слева.
   *
   * @return bool
   */
  public function useShowDataClientRight()
  {
    return defined('USE_SWISS_ENVELOPE_LAYOUT') && USE_SWISS_ENVELOPE_LAYOUT;
  }

}
