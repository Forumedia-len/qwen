<?php

namespace AC\core\modules\config\models;

use AC\core\modules\config\tables\ConfigEmailsTable;
use AC\core\system\helpers\StringHelper;
use AC\core\system\model\BaseModel;

class ConfigEmailModel extends BaseModel
{
  use ConfigEmailsTable;

  public function getEmail()
  {
    return ['alias' => $this->getAlias(), 'email' => $this->email, 'name_court' => $this->name_court, 'courts' => $this->courts];
  }

  public function getAlias()
  {
    if (empty($this->alias)) {
      $this->alias = StringHelper::translit($this->name_court);
    }

    return $this->alias;
  }
}