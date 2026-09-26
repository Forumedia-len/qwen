<?php

namespace AC\core\modules\membershipFees\models;

use AC\core\modules\membershipFees\engines\MembershipFeesGroupsEngine;
use AC\core\modules\membershipFees\tables\MembershipFeesGroupsTable;


class MembershipFeesGroupsModel extends MembershipFeesGroupsTable
{
  protected $baseEngine             = 'MembershipFeesGroupsEngine';
  /**
   * @var MembershipFeesGroupsEngine
   */
  protected $engine;

  /**
   * {@inheritDoc}
   */
  public function rules()
  {
    $rule = array(
      array(array('rate', 'title'), 'required'),
      array('rate', 'float'),
      array('inclusion_age', 'default', ['value' => 0]),
      array('inclusion_age', 'integer'),
      array('title', 'string', array('max' => 254)),
    );

    foreach ($this->parameterUseLanguageOptions() as $parameter) {
      $itemRule = [];
      foreach (config('lang')->getActiveLanguages() as $lang) {
        $itemRule[] = $parameter . '_' . $lang;
      }
      $rule[] = [$itemRule, 'string', array('max' => 254)];
    }

    return $rule;
  }

  public function remove()
  {
    $this->load(['active' => 0]);
    return $this->save();
  }

}