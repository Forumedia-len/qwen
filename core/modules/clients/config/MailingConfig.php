<?php

namespace AC\core\modules\clients\config;

use AC\core\system\config\BaseConfig;

class MailingConfig extends BaseConfig
{
  /** Отправлять письмо админу после того как клиент изменил свои данные
   * @return bool
   */
  public function useSendMailAdminAfterChangeDataClient(): bool
  {
    return USE_MAIL_ADMIN_AFTER_CHANGE_DATA_CLIENT;
  }
}