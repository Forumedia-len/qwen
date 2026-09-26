<?php

namespace AC\core\modules\payment\payone\actions\orders;

use AC\core\modules\payment\payone\entities\dto\PortalDataDto;
use AC\core\modules\payment\payone\entities\dto\OrderDataDto;
use AC\core\modules\payment\payone\entities\dto\PersonalDataDto;
use AC\core\modules\payment\payone\entities\enums\MethodPayment;
use AC\core\modules\payment\payone\services\PayoneDataService;
use AC\core\modules\payment\payone\services\PayoneService;
use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\system\helpers\RedisHelper;
use Service;

abstract class PaymentOrder
{
  protected string $sessionParameter;
  protected string $param = '';

  abstract public function setDataRequest(
    PersonalDataDto $personalData,
    OrderDataDto $orderData,
    PortalDataDto $portalData,
    ?string &$errorCode = null
  ): bool;

  abstract public function payment(string $reference, string $ids, string $client_id): bool;

  abstract public function delete(string $reference): void;

  abstract public function setStatus(string $reference, string $status): void;

  abstract public function checkStatusPayment(string $reference): ?string;

  protected function setPersonalData(PersonalDataDto $personalData): bool
  {
    $client = Service::engines()->clients->current_client_data;
    if (empty($client) && Service::auth()->isBarClient()) {
      $client = [
        'client_id'     => null,
        'name'          => $_SESSION['name'] ?? '',
        'surname'       => $_SESSION['surname'] ?? '',
        'address'       => '',
        'post_code'     => '',
        'city'          => '',
        'email'         => $_SESSION['email'] ?? '',
        'country'       => config('country')->getDefaultCode(),
        'phone_mobile'  => '',
        'phone'         => '',
        'account_owner' => '',
        'bank_iban'     => '',
      ];
    }
    if (!empty($client)) {
      // вносим основные персональные данные
      $personalData->customerid = $client['client_id'];
      $personalData->firstname  = $client['name'];
      $personalData->lastname   = $client['surname'];
      $personalData->street     = $client['address'];
      $personalData->zip        = $client['post_code'];
      $personalData->city       = $client['city'];
      $personalData->email      = $client['email'];
      $personalData->country    = !empty($client['country']) ? $client['country'] : config('country')->getDefaultCode();
      // Доп. данные (нужны для отдельных методов, например BNPL/PDD).
      $mobile                        = trim((string)($client['phone_mobile'] ?? ''));
      $phone                         = trim((string)($client['phone'] ?? ''));
      $personalData->telephonenumber = $mobile !== '' ? $mobile : $phone;
      $rawBirthday                   = isset($client['birthday']) ? trim((string)$client['birthday']) : '';
      // Payone ждёт YYYYMMDD (пример из документации: 19820324).
      $personalData->birthday            = $rawBirthday !== '' ? preg_replace('/[^0-9]/', '', $rawBirthday) : '';
      $personalData->ip                  = (string)Service::request()->getIPAddress();
      $personalData->customer_is_present = 'yes';
      $personalData->businessrelation    = 'b2c';
      $personalData->bankaccountholder   = trim((string)($client['account_owner'] ?? ($client['name'] ?? '') . ' ' . ($client['surname'] ?? '')));
      $personalData->iban                = preg_replace('/\s+/', '', (string)($client['bank_iban'] ?? ''));

      return true;
    }

    return false;
  }

  protected function applyPayTypeToOrderData(
    OrderDataDto $orderData,
    PersonalDataDto $personalData,
    PortalDataDto $portalData,
    ?string &$errorCode = null
  ): bool {
    // Строго валидируем по включённым методам профиля: если метод не включён — онлайн-оплата недоступна.
    $payType = strtolower(trim((string)Service::request()->_post('pay_type', '')));
    $enabled = OnlineGatewayService::payoneConfig()->enabledMethods();
    if ($payType === '' || $enabled === [] || !in_array($payType, $enabled, true)) {
      $errorCode = '1';
      return false;
    }
    switch ($payType) {
      case 'pp':
        $orderData->clearingtype = 'wlt';
        $orderData->wallettype   = 'PPE';
        break;
      case 'wero':
        $orderData->clearingtype = 'wlt';
        $orderData->wallettype   = 'WRO';
        break;
      case 'bnpl':
        $orderData->clearingtype  = 'fnc';
        $orderData->financingtype = 'PDD';
        if (trim((string)($personalData->telephonenumber ?? '')) === ''
          || !preg_match('/^\d{8}$/', trim((string)($personalData->birthday ?? '')))
          || trim((string)($personalData->email ?? '')) === ''
          || trim((string)($personalData->ip ?? '')) === ''
          || trim((string)($personalData->bankaccountholder ?? '')) === ''
          || trim((string)($personalData->iban ?? '')) === ''
          || trim((string)($personalData->customer_is_present ?? '')) === ''
          || trim((string)($personalData->businessrelation ?? '')) === ''
        ) {
          $errorCode = '1';
          return false;
        }
        $deviceToken = trim((string)Service::request()->_post('device_token', ''));
        if ($deviceToken === '') {
          $errorCode = '1';
          return false;
        }
        $orderData->add_paydata['device_token'] = $deviceToken;
        break;
      default:
        $orderData->clearingtype = $payType;
    }
    RedisHelper::setKey(
      $portalData->reference,
      'typePayment',
      MethodPayment::tryFrom($payType)?->shortLabel(),
      PayoneService::REDIS_PARAMS_TTL
    );
//    PayoneService::session()->set('typePayment', MethodPayment::tryFrom($payType)?->shortLabel());
    $credentials          = OnlineGatewayService::payoneConfig()->portalCredentialsForMethod($payType);
    $portalData->portalid = $credentials['portalid'];
    $portalData->key      = $credentials['key'];

    return true;
  }

  public function getSessionParameter(): string
  {
    return $this->sessionParameter;
  }

  public function getParam()
  {
    return $this->param;
  }

  public function getTypePayment(): string
  {
    return RedisHelper::get(PayoneDataService::portal()->reference, 'typePayment') ?? PayoneDataService::order()->typePayment();
  }

}
