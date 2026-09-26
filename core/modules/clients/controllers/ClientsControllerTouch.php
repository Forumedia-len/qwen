<?php

namespace AC\core\modules\clients\controllers;

use AC\core\system\App;
use AC\core\system\helpers\UrlHelper;
use Service;

class ClientsControllerTouch extends ClientsController
{

  /**
   *  Основной метод работы с регистрацией клиентов
   *
   * @param bool $new
   *
   * @return mixed
   */
  public function showFormRegistration($new = true)
  {
    $type = Service::engines()->getTypeAlias();
    if ($type == null) {
      $this->redirect(UrlHelper::to(''));
    }

    return parent::showFormRegistration($new);
  }

}