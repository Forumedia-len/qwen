<?php

namespace AC\app\config;

use AC\core\system\config\BaseConfig;

class AuthConfig extends BaseConfig
{
  /**
   * @var string параметр в сессии для авторизации админа
   */
  public string $sessionAdminParams = SESSION_COURT . '_session_admin';
  
  /**
   * @var string параметр в для авторизации клиента сайта
   */
//  public $sessionClientParams = SESSION_COURT . '_session_client';// будущее значение
  public string $sessionClientParams = 'malsch_session_client_id_' . SESSION_COURT; // текущее значение
  
  /**
   * @var string название модуля для админов
   */
  public string $nameModuleAdmins = 'users';
  
  /**
   * @var string название модуля для пользователей
   */
  public string $nameModuleClients = 'clients';
  
  /**
   *  Массив с адресами(включая устройство)
   *  для которых не требуется проверка авторизации
   *  Cron, обращение webIo или другие схожие
   * @var array
   */
  public array $urlNotCheckAuth = [
    'at/ical_webio_generate.php',
    'at/users/auth/logOut',
    'at/cron.php',
    'at/cron_deploy_clients.php',
  ];
  
  
  public function numberOfSecondsOfCodeValidity2FA(): int
  {
    return NUMBER_SECOND_LIFE_CODE_2FA;
  }
  
  public function numberOfSecondsToReload2FA(): int
  {
    return NUMBER_SECOND_LIFE_PAGE_SEND_CODE_2FA;
  }
  
  public function numberOfAttempts(): int
  {
    return NUMBER_OF_ATTEMPTS;
  }
  
  public function lockTimeAuth(): int
  {
    return LOCK_TIME_AUTH;
  }
  
  public function numberOfAttempts2FA(): int
  {
    return NUMBER_OF_ATTEMPTS_2FA;
  }
  
  public function useEmailUniquenessCheck(): bool
  {
    return !defined('CHECK_EMAIL_ADMIN_UNIQUE') || CHECK_EMAIL_ADMIN_UNIQUE;
  }
}