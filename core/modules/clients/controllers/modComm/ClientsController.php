<?php

namespace AC\core\modules\clients\controllers\modComm;

use AC\core\engines\ClientsEngine;
use AC\core\modules\clients\entities\dto\ClientDto;
use AC\core\system\modules\modComm\controllers\ModCommController;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;

class ClientsController extends ModCommController
{
  public function getClientDataById(ModCommRequest $request): ModCommResponse
  {
    $client_id = $request->getDataValue('client_id');
    if ($client_id === 'current' && $this->clientsEngine()->checkAuthorization()) {
      $client = $this->clientsEngine()->current_client_data;
    } else {
      $this->clientsEngine()->getClientData($request->getDataValue('client_id'), $client);
    }
    return ModCommHelper::success(['client' => ClientDto::fromArray($client)]);
  }
  
  public function getListNamesClients(ModCommRequest $request): ModCommResponse
  {
    $alfa       = $request->getDataValue('alfa');
    $clientId   = $request->getDataValue('client_id') ?? $request->getDataValue('currentClientId');
    $addOptions = $request->getDataValue('addOptions', true);
    $clients    = $this->clientsEngine()->getListNamesClients(
      $alfa,
      $addOptions ? null : $clientId,
      $request->getDataValue('substring'),
      $request->getDataValue('addClients', []));
    
    $output = match ($request->getDataValue('output')) {
      'select' => useLayout()->render('select', [
        'name'           => 'client_id',
        'values'         => $addOptions || $clientId ? $clients : [],
        'class'          => $request->getDataValue('class'),
        'current'        => $clientId,
        'size'           => $request->getDataValue('size'),
        'otherAttribute' => $request->getDataValue('otherAttribute'),
      ], 'common'),
      default  => $clients,
    };
    return ModCommHelper::success(['data' => $output]);
  }
  
  private function clientsEngine(): ClientsEngine
  {
    return getEngine('clients', false);
  }
}