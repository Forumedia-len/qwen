<?php

namespace AC\core\modules\stocks\entities\entity\groupTypes;

use AC\app\locators\Service;
use AC\core\modules\stocks\entities\entity\StocksGroup;

class MessageWhenBooking extends GroupType
{
  public function getConditionAsHtml(StocksGroup $group): string
  {
    $preferences = Service::formatData('json');
    if (!$preferences->getItem($group->getType()->getName())) {
      $preferences->set($group->preferences);
    }
    Service::mainPage()->addJsFile('visualeditor/ckeditor');

    return view()->render('groups/types/message_when_booking', ['message' => $preferences->{$this->getName()} ?? '', 'group' => $group]);
  }

  public function updateCondition(StocksGroup $group, &$error = null): bool
  {
    $preferences = Service::tablePreferences(getEngine('StocksGroups')?->tableName());
    if (!$preferences->setPreference($group->id, $this->getName(), Service::request()->_post('message'))) {
      $error = lang('This text variant has not been changed!', 'message_error');
      return false;
    }

    return true;
  }

  public function getAsString(): string
  {
    return '';
  }

}