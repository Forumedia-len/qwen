<?php

namespace AC\core\modules\membershipFees\models;

use AC\core\modules\membershipFees\engines\MembershipFeesClientDataEngine;
use AC\core\modules\membershipFees\tables\ClientDataTable;

/**
 * Модель дополнительных данных клиента, принадлежащих модулю членских взносов.
 */
class ClientDataModel extends ClientDataTable
{
  protected $baseEngine             = 'MembershipFeesClientDataEngine';
  /**
   * @var MembershipFeesClientDataEngine
   */
  protected $engine;

  /**
   * Создать или обновить только привязку клиента к группе членского взноса.
   */
  public function assignMembershipFeesGroup(int $clientId, int $groupId): bool
  {
    $dataId = null;
    foreach ($this->getClientData($clientId) as $clientData) {
      if ($clientData->name === 'membership_fees_group') {
        $dataId = $clientData->id;
        break;
      }
    }

    $model = $dataId ? new self($dataId) : new self();
    if (!$model->load([
      'id'        => $dataId,
      'client_id' => $clientId,
      'typeBlock' => 'membershipFees',
      'name'      => 'membership_fees_group',
      'value'     => $groupId,
    ])) {
      return false;
    }

    return (bool)$model->save();
  }
}
