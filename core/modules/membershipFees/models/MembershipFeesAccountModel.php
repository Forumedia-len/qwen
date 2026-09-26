<?php

namespace AC\core\modules\membershipFees\models;

use AC\core\modules\accounts\models\AccountsModel;
use Service;
use AC\core\modules\membershipFees\engines\MembershipFeesAccountEngine;

class MembershipFeesAccountModel extends AccountsModel
{

  public $baseEngine = 'MembershipFeesAccountEngine';

  /**
   * @var MembershipFeesAccountEngine
   */
  public $engine;
  public $account_type = 4;


  public function setDataLoad($data)
  {
    if($this->setData($data)) {
      return true;
    }

    return false;
  }

  public function checkInsert(): bool
  {
    if(parent::checkInsert()) {
      if($this->engine->verifyAccountExists($this)) {
        return true;
      }
    }

    return false;
  }

  public function insert(&$error_code = false): bool
  {
    if(parent::insert($error_code)) {
      if($this->engine->insertAccountMembershipFees($this)) {

        return true;
      }
    }
    return false;
  }

  public function getAccountTableData($reservations, $nds)
  {
    $out = '<table border="1" bordercolor="black" cellpadding="2">' . "\n";
    $out .= '<tr>' . "\n";
    $out .= '<th width="50%">' . lang('Position') . '</th>' . "\n";
    $out .= '<th width="50%">' . lang('Invoice amount', 'accounts_view') . '</th>' . "\n";
    $out .= '</tr>' . "\n";

    $sum = 0;
    foreach ($reservations as $a) {
      $out .= '<tr>' . "\n";
      $out .= '<td width="50%" align="left">' . $a['membership_fees_group_title'] . '</td>' . "\n";
      $out .= '<td width="50%" align="right">' . number_format($a['price'], 2, ',', ' ') . ' ' . CURR_VALUTE . '</td>' . "\n";
      $out .= '</tr>' . "\n";
      $sum += $a['price'];
    }
    $out .= '</table>' . "\n";
    $out .= '<p>&nbsp;</p>' . "\n";
    $out .= $this->getSumInfo($sum, $nds);


    return $out;
  }

  function getSumInfo($sum, $nds)
  {
    $out = '<table border="0"><tr><td width="56%">&nbsp;</td><td width="44%">';
    $out .= '<table border="0" width="280">';
    if ((int)Service::configDB('account', 'account_view_nds_view')) {
      $nds_sum = $sum - ($sum / (1 + $nds / 100));
      $out     .= '<tr><td>' . lang('Invoice amount net', 'accounts_view') . '</td><td align="right">' . number_format(
          ($sum - $nds_sum),
          '2',
          ',',
          ' '
        ) . ' ' . CURR_VALUTE . '</td></tr>';
      $out     .= '<tr><td>' . lang('VAT', 'accounts_view') . ' ' . $nds . '%</td><td align="right">' . (number_format(
          $nds_sum,
          '2',
          ',',
          ' '
        )) . ' ' . CURR_VALUTE . '</td></tr>';
    }
    $out .= '<tr><td><strong>' . lang('Invoice amount', 'accounts_view') . '</strong></td><td align="right"><strong>' . number_format(
        $sum,
        '2',
        ',',
        ' '
      ) . ' ' . CURR_VALUTE . '</strong></td></tr>';
    $out .= '</table>';
    $out .= '</td></tr></table>';

    return $out;
  }
}