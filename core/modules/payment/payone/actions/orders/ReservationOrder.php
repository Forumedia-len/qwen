<?php

namespace AC\core\modules\payment\payone\actions\orders;

use AC\core\engines\AccountsEngine;
use AC\core\modules\payment\payone\entities\dto\PortalDataDto;
use AC\core\modules\payment\payone\entities\dto\OrderDataDto;
use AC\core\modules\payment\payone\entities\dto\PersonalDataDto;
use AC\core\modules\payment\payone\services\PayoneDataService;
use AC\core\modules\payment\payone\services\PayoneService;
use AC\core\modules\payment\services\OnlinePaymentPostProcessingService;
use AC\core\system\helpers\RedisHelper;
use Service;

class ReservationOrder extends PaymentOrder
{
  protected string $sessionParameter = 'PAY_DATA';
  protected string $param = 'AT';

  public function setDataRequest(PersonalDataDto $personalData, OrderDataDto $orderData, PortalDataDto $portalData, ?string &$errorCode = null): bool
  {
    $session = PayoneService::session()->setSessionKey($this->getSessionParameter());
    $ids     = explode('|', Service::request()->_post('r'));
    $session->set('reference', $portalData->reference);
    $session->set('ids', $ids);
    RedisHelper::setKey($portalData->reference, 'lang', config('lang')->getCurrentLang(), PayoneService::REDIS_PARAMS_TTL);

    $r = Service::engines();
    if ($this->setPersonalData($personalData)) {
      RedisHelper::setKey(
        $portalData->reference,
        'onlineInvoiceClient',
        [
          'name'      => (string)$personalData->firstname,
          'surname'   => (string)$personalData->lastname,
          'address'   => (string)$personalData->street,
          'post_code' => (string)$personalData->zip,
          'city'      => (string)$personalData->city,
          'email'     => (string)$personalData->email,
          'number'    => (string)$personalData->telephonenumber,
        ],
        PayoneService::REDIS_PARAMS_TTL
      );
      $portalData->param = $portalData->param . '|' . implode(':', $ids) . '|' . $personalData->customerid;
      if (!empty($ids) && $r->getReservationDataByIds($ids, $rows, true)) {
        $sum = 0;
        foreach ($rows as $row) {
          $r->setPayOneReference($row['reservation_id'], $portalData->reference);
          $price = $row['price'];
          $sum   += $price;
          $orderData->addOrder(
            [
              'it' => 'goods',
              'id' => $this->getParam() . $row['reservation_id'],
              'de' => Service::lang(config('lang')->getDefault())->_('my_booking') . ': '
                . date('d.m.Y/H:i', strtotime($row['start'])) . ' - '
                . date('H:i', strtotime($row['finish'])),
              'pr' => $price * 100,
              'no' => 1,
            ]
          );
        }
        $orderData->setAmount($sum);
        $session->set('amount', $sum);
        // Значение приходит из OrdersModelReservation (prepayment=3_{code}) и из layouts Payone.

        if (!$this->applyPayTypeToOrderData($orderData, $personalData, $portalData, $errorCode)) {
          return false;
        }

        return true;
      } else {
        $errorCode = '3';
      }
    } else {
      $errorCode = '2';
    }

    return false;
  }

  public function payment(string $reference, string $ids, string $client_id): bool
  {
    config('lang')->setLang(RedisHelper::get($reference, 'lang'));
    $check = 0;
    $r     = Service::engines();
    $ids   = explode(':', $ids);
    $insertedReservationIds = [];
    if ($r->getReservationsOnBasisOfTmp($ids, $reservations, $real_ids) && empty($real_ids)) {
      RedisHelper::setKeys($reference, [
        'messageCode' => 'time_booked',
      ], PayoneService::REDIS_PARAMS_TTL);

      return false;
    }
    if ($reservations || ($reservations = $r->getReservationsTmpByReference($reference))) {
      foreach ($reservations as $reservation) {
        $r->setPayOneReference($reservation['reservation_id'], $reference . '|' . PayoneDataService::portal()->txid);
        if (!empty($reservation['real_reservation_id'])) {
          $check++;
        }
      }
      if (count($reservations) == $check || $this->insert($ids, $reference, $insertedReservationIds)) {
        if ($r->setPaymentStateReservations($reference)) {
          /** @var AccountsEngine|false $accounts */
          $accounts = getEngine('accounts', false);
          if ($accounts) {
            $portalData     = PayoneDataService::portal();
            $portalParam    = explode('|', $portalData->param);
            $profileTypeKey = rawurldecode($portalParam[1] ?? '');
            $accounts->createOnlinePaymentInvoiceForReservations(
              $real_ids ?: $insertedReservationIds,
              $reference,
              (string)$portalData->txid,
              $this->getTypePayment(),
              $profileTypeKey,
              RedisHelper::get($reference, 'onlineInvoiceClient', [])
            );
          }

          return true;
        }
      }
    }

    return false;
  }

  protected function insert(array $ids, string $reference, array &$insertedReservationIds = []): bool
  {
    $result                 = OnlinePaymentPostProcessingService::createReservations($ids, 'ERROR');
    $insertedReservationIds = $result['reservationIds'];
    if ($result['success']) {
      if ($result['doorCode'] !== '') {
        RedisHelper::setKey($reference, 'door_code_out', $result['doorCode'], PayoneService::REDIS_PARAMS_TTL);
      }
      RedisHelper::setKeys($reference, [
        'message'   => $result['message'],
        'price'     => $result['price'],
        'typeOrder' => 'reservation',
      ], PayoneService::REDIS_PARAMS_TTL);

      return true;
    }
    $this->delete($reference);
    RedisHelper::setKey($reference, 'messageCode', '3', PayoneService::REDIS_PARAMS_TTL);

    return false;
  }

  public function delete(string $reference, string $status = 'ERROR'): void
  {
    $r = Service::engines();
    if ($reservations = $r->getReservationsTmpByReference($reference)) {
      foreach ($reservations as $reservation) {
        if (!empty($reservation['real_reservation_id'])
          && $r->getReservationData($reservation['area_id'], $reservation['start'], $reservation_data)) {
          if ($reservation_data['client_id'] == $reservation['client_id'] && $reservation['real_reservation_id'] == $reservation_data['reservation_id']) {
            module('reservations')->useModel(null, $reservation_data['reservation_id'])->removeReservation($error_message, true,
              $reservation['pay_state'])
            || RedisHelper::setKey($reference, 'message', $error_message, PayoneService::REDIS_PARAMS_TTL);
          }
        }
//        $unix = strtotime($reservation['start']);
//        TmpBlockingHelper::deleteOrderBlock($reservation['area_id'], date('Y-m-d', $unix), $times, $reservation['client_id']);
        $r->setPayPalStatus($reservation['reservation_id'], $status);
      }
    }

    RedisHelper::setKey($reference, 'messageCode', 'time_booked', PayoneService::REDIS_PARAMS_TTL);
  }


  public function setStatus(string $reference, string $status): void
  {
    Service::engines()->setPayPalStatusByReference($reference, $status);
  }

  public function checkStatusPayment(string $reference): string
  {
//    'OK','FALSE','CANCEL' - назад ,'ERROR' ,'FAILED' - pay_status failed
    $status = [
      'OK'     => 0,
      'FALSE'  => 0,
      'FAILED' => 0,
      'ERROR'  => 0,
      'CANCEL' => 0,
      'WAIT'   => 0,
    ];
    if ($reservations = Service::engines()->getReservationsTmpByReference($reference)) {
      foreach ($reservations as $reservation) {
        $status[$reservation['pay_state'] ?? 'WAIT']++;
      }
    }

    return match (true) {
      $status['FALSE'] == count($reservations)  => 'FALSE',
      $status['FAILED'] == count($reservations) => 'FAILED',
      $status['ERROR'] == count($reservations)  => 'ERROR',
      $status['CANCEL'] == count($reservations) => 'CANCEL',
      $status['OK'] == count($reservations)     => 'OK',
      default                                         => 'WAIT'
    };
  }
}
