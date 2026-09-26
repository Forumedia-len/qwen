<?php

namespace AC\mapi;

use AC\core\system\modules\modComm\helpers\ModCommHelper;
use Service;
use AC\core\system\helpers\PasswordHelper;
use AC\core\system\helpers\JsonHelper;
use AC\core\system\db\Query;

class api
{

  function start()
  {
    return match (Service::request()->_('action')) {
      'lang'                => $this->lang(),
      'bookingLinks'        => $this->bookingLinks(),
      'check_login'         => $this->checkLogin(),
      'generatePassword'    => $this->generatePassword(),
      'getListNamesClients' => $this->getListNamesClients(),
      'statusPayment'       => $this->statusPayment(),
      default               => '',
    };
  }

  private function lang(): string
  {
    $post = JsonHelper::decode(Service::request()->_post('request'), true);

    return lang($post['message'], $post['group'], $post['params'], $post['defaultMessage'], $post['locale']);
  }

  private function bookingLinks(): array
  {
    $out    = [];
    $engine = Service::engines();
    $device = Service::request()->_get('device');
    switch ($device) {
      case 'touch':
        $prefix_key = 'login_';
        $prefix_url = 'touchscreen/';
        break;
      default:
        $prefix_key = 'reservations_';
        $prefix_url = '';
    }
    foreach (array_keys($engine->areas->selectActiveType('type_id')) as $type) {
      $item         = Service::structure($device)->getPageDataByKey($prefix_key . $type);
      $item['href'] = BASE_HREF . $prefix_url . $item['href'];
      $out[]        = $item;
    }

    return $out;
  }

  private function checkLogin(): bool
  {
    if ($login = Service::request()->_('login')) {
      if (($result = Query::sqlQuery(
          'select count(*) as cnt from ' . Query::tableName('clients') . ' where login = ?',
          [$login],
          true,
          ['onlyOne' => true]
        )) && isset($result['cnt']) && $result['cnt'] > 0) {
        return false;
      }
    }

    return true;
  }

  private function generatePassword()
  {
    $password = PasswordHelper::generatePassword(16);
    if (!PasswordHelper::checkPasswordValidation($password)) {
      $password = $this->generatePassword();
    }

    return $password;
  }

  private function getListNamesClients(): array
  {
    $client_id_selected = Service::request()->_('client_id_selected');
    $alfa               = Service::request()->_('alfa');

    $out = [];
    if ($client_id_selected) {
      $out[] = ['id' => '-1', 'text' => lang('New client'), 'selected' => false];
    }
    if ($clients_data = ModCommHelper::get('clients', 'getListNamesClients', ['alfa' => $alfa], 'data')) {
      foreach ($clients_data as $clientId => $clientName) {
        $out[] = [
          'id'       => (string)$clientId,
          'text'     => $clientName,
          'selected' => $client_id_selected == $clientId,
          'type'     => ($client_id_selected == $clientId ? 'select' : ''),
        ];
      }
    }

    return ['items' => $out];
  }

  private function statusPayment(): array
  {
    $reference = Service::request()->_('reference');
    $type      = Service::request()->_('type');

    if (!$reference) {
      return ['error' => 'Reference is required'];
    }
    $status = module('payment', ['subName' => 'payone'])->execContent('statusPayment');

    return [
      'is_confirmed' => $status == 'OK',
      'status'       => $status,
      'reference'    => $reference,
      'type'         => $type,
    ];
  }

}

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$aca = new api();
echo json_encode($aca->start());
