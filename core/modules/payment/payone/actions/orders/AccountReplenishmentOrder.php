<?php

namespace AC\core\modules\payment\payone\actions\orders;

use AC\core\engines\AccountsEngine;
use AC\core\modules\payment\payone\entities\dto\PortalDataDto;
use AC\core\modules\payment\payone\entities\dto\OrderDataDto;
use AC\core\modules\payment\payone\entities\dto\PersonalDataDto;
use AC\core\modules\payment\payone\entities\enums\OnlineGateway;
use AC\core\modules\payment\payone\services\PayoneDataService;
use AC\core\modules\payment\payone\services\PayoneService;
use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\modules\payment\services\OnlinePaymentPostProcessingService;
use AC\core\system\helpers\JsonHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use Service;

class AccountReplenishmentOrder extends PaymentOrder
{

  protected string $sessionParameter = 'PREP_PAY_DATA';
  protected string $param            = 'ATPRPM';

  public function setDataRequest(PersonalDataDto $personalData, OrderDataDto $orderData, PortalDataDto $portalData, ?string &$errorCode = null): bool
  {
    $session = PayoneService::session()->setSessionKey($this->getSessionParameter());

    if (Service::request()->checkPost('payment_data') && $this->setPersonalData($personalData)) {
      [$pp_id, $sum] = explode(';', Service::request()->_post('payment_data'));
      if ($pp_id && $sum) {
        $session->set('reference', $portalData->reference);
        $session->set('ids', $pp_id);
        $session->set('amount', $sum);
        $orderData->setAmount($sum);

        $portalData->param = $portalData->param . '|' . $pp_id . '|' . $personalData->customerid;

        if (!$this->applyPayTypeToOrderData($orderData, $personalData, $portalData, $errorCode)) {
          return false;
        }
        $orderData->addOrder(
          [
            'it' => 'goods',
            'id' => $this->getParam() . '0' . $personalData->customerid,
            'de' => Service::lang(config('lang')->getDefault())->_('prepayment', 'payone') . ': ' .
              date('d.m.Y/H:i') . ' ' . $sum . ' ' . $orderData->currency,
            'pr' => $orderData->amount,
            'no' => 1,
          ]
        );

        return true;
      }
    }
    $errorCode = '2';

    return false;
  }

  public function payment(string $reference, string $ids, string $client_id): bool
  {
    /** @var AccountsEngine $a */
    $a = getEngine('accounts', false);
    if ($a->checkPrepaymentAccountByReference($reference)
      || $this->insert($reference, $ids, $client_id)) {
      if ($a->closedPrepaymentAccountByReference($reference)) {
        return true;
      }
    }

    return false;
  }


  protected function insert(string $reference, string $ids, string $client_id): bool
  {
    Service::engines()->clients->getClientData($client_id, $client);
    $transactionId = PayoneDataService::portal()->txid;
    $paymentType    = $this->getTypePayment();

    return OnlinePaymentPostProcessingService::replenishPrivateAccount(
      $ids,
      $client,
      'paypal|' . $reference . '|' . $transactionId . '|' . $paymentType
        . OnlineGatewayService::encodePrepaymentPriceInfoTypeKeyTail(),
      [
        'reference'       => $reference,
        'tx_id'           => $transactionId,
        'pay_type'        => $paymentType,
        'payment_profile' => OnlineGateway::PAYONE->value,
      ],
      $prepaymentSum,
      $accountId,
      "client|{$client['client_id']}|system"
    );
  }

  public function delete(string $reference): void
  {
    /** @var AccountsEngine $a */
    $a        = getEngine('accounts', false);
    $clientId = Service::auth()->getUserId() ?? PayoneDataService::personal()->customerid;
    if ((['account_id' => $account_id, 'price' => $prepayment_sum] = $a->checkPrepaymentAccountByReference($reference))
      && $account_id && $prepayment_sum && $clientId) {
      $related_data = JsonHelper::decode(ModCommHelper::get('clients', 'privateAccount/transaction',
        ['clientId' => (int)$clientId, 'typeDirection' => 'in', 'status' => 'succeeded', 'typeCode' => 'paypal_deposit', 'relatedId' => $account_id],
        'related_data'), true);
      OnlinePaymentPostProcessingService::rollbackPrivateAccount(
        (int)$clientId,
        (int)$account_id,
        $prepayment_sum,
        $related_data ?? [
            'pp_id'                  => PayoneService::session($this->getSessionParameter())->get('ids'),
            'amount_private_account' => $prepayment_sum,
            'amount_real'            => null,
            'account_id'             => $account_id,
            'reference'              => $reference,
            'tx_id'                  => PayoneDataService::portal()->txid,
            'pay_type'               => $this->getTypePayment(),
        ],
        "client|$clientId|system",
        false
      );
    }
  }

  public function checkStatusPayment(string $reference): ?string
  {
    /** @var AccountsEngine $a */
    $a = getEngine('accounts', false);

    return $a->checkStatusPaymentPrepaymentAccountByReference($reference);
  }

  public function setStatus(string $reference, string $status): void
  {
  }
}
