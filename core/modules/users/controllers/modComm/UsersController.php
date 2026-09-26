<?php

namespace AC\core\modules\users\controllers\modComm;

use AC\core\engines\UsersEngine;
use AC\core\modules\users\entities\dto\UserDto;
use AC\core\system\helpers\DataHelper;
use AC\core\system\modules\modComm\controllers\ModCommController;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;

class UsersController extends ModCommController
{
  private function usersEngine(): UsersEngine
  {
    return getEngine('users', false);
  }
  
  public function getAllUsers(ModCommRequest $request): ModCommResponse
  {
    $users_data = [];
    if ($this->usersEngine()?->getUsers($users_data)) {
      $users_data = DataHelper::getDataAs($users_data, array_merge([
        'key'      => 'user_id',
        'dtoClass' => UserDto::class,
        'as'       => 'dto',
      ], $request->getData()));
    }
    
    return ModCommHelper::success(['users' => $users_data]);
  }
}
