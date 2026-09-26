<?php

namespace AC\core\modules\payment\services;

use AC\core\engines\AccountsEngine;
use AC\core\modules\config\helpers\ScopedConstantHelper;
use AC\core\modules\reservations\helpers\TmpBlockingHelper;
use AC\core\modules\reservations\models\ReservationsModel;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use Service;

/**
 * Общие операции, выполняемые после подтверждения онлайн-платежа.
 */
class OnlinePaymentPostProcessingService
{
  /**
   * Создать реальные бронирования на основе временных.
   *
   * @param list<int|string> $reservationIds
   *
   * @return array{success: bool, reservationIds: list<int>, message: string, doorCode: string, price: float|int}
   */
  public static function createReservations(array $reservationIds, string $failedStatus): array
  {
    $engine                 = Service::engines();
    $check                  = 0;
    $sum                    = 0;
    $createdReservationIds  = [];
    $message                = '';
    $doorCode               = '';
    /** @var ReservationsModel $main */
    $main                   = module('reservations')->useModel(null, $reservationIds[0], true);
    $main->times            = [];
    $lastMainId             = null;

    foreach ($reservationIds as $reservationId) {
      /** @var ReservationsModel $model */
      $model         = module('reservations')->useModel(null, $reservationId, true);
      $main->times[] = $model->time;
      if ($model->reservation_id === null) {
        continue;
      }
      if (!empty($model->main_reservation_id) && !empty($lastMainId)) {
        $model->main_reservation_id = $lastMainId;
      }
      $errors = [];
      if ($model->insert($errors)) {
        if (empty($model->main_reservation_id)) {
          $lastMainId = $model->inserted_reservation_id;
        }
        getEngine('ReservationData')?->setReservationId($model->inserted_reservation_id, $reservationId);
        $engine->setPayPalStatus($reservationId, 'OK', $model->inserted_reservation_id);
        $createdReservationIds[] = (int)$model->inserted_reservation_id;
        $errorCode = null;
        $model->switchStateLHN($errorCode);
        if ($model->door_code) {
          $main->door_codes[strtotime($model->time)] = (object)[
            'time' => $model->time,
            'code' => $model->door_code,
          ];
        }
        $main->sum_price_array[$model->time]['totalCost'] = $model->getFullPrice();
        $sum += $model->getFullPrice();
        $check++;
      } else {
        $engine->setPayPalStatus($reservationId, $failedStatus);
      }
    }

    if (count($reservationIds) === $check) {
      $engine->webIo->sendFTPCurrentIcal($engine, $main->area_id, $main->getDate());
      $message = $main->getMessageAfterBooking(
        $sum,
        $engine->config['min_max'][$main->type_id][$main->sport_id]['min_rejection_days_count']
      );
      $main->setTimeTitles();
      if (empty($main->stock_id)) {
        $main->stock_id = array_keys(getEngine('ReservationData')?->getReservationData($reservationIds[0], true)['stocks'] ?? []);
      }
      TmpBlockingHelper::deleteOrderBlock($main->getAreaId(), $main->getDate(), $main->getTimes(), $main->getClientId());
      $main->mailAfterInsertReservation();
      if (ScopedConstantHelper::boolValue(
        'DOOR_CODES',
        ScopedConstantHelper::contextKey(
          (int)$main->type_id,
          (int)$main->sport_id,
          (int)$main->getAreaId()
        )
      )) {
        $doorCode = view()->render('door_codes', ['door_codes' => $main->door_codes]);
      }
    }

    return [
      'success'        => count($reservationIds) === $check,
      'reservationIds' => $createdReservationIds,
      'message'        => $message,
      'doorCode'       => $doorCode,
      'price'          => $sum,
    ];
  }

  /**
   * Создать предоплатный счёт и зачислить пакет на личный счёт клиента.
   *
   * @param array<string, mixed> $client
   * @param array<string, mixed> $gatewayRelatedData
   * @param float|int            $prepaymentSum
   * @param int|null             $accountId
   */
  public static function replenishPrivateAccount(
    string $prepaymentId,
    array $client,
    string $priceInfo,
    array $gatewayRelatedData,
    &$prepaymentSum = 0,
    &$accountId = null,
    ?string $createdUser = null,
    bool $closeAccount = false
  ): bool {
    $engine = Service::engines();
    if (!$engine->pp->getPP($prepaymentId, $prepaymentItem)) {
      return false;
    }

    $price          = $prepaymentItem['price_real'];
    $prepaymentSum  = $prepaymentItem['price_account'];
    $engine->nds->getNds($client['nds'], $nds);
    /** @var AccountsEngine|false $accounts */
    $accounts = getEngine('accounts', false);
    if (!$accounts) {
      return false;
    }

    $accountId = $accounts->insertPrepaymentAccount(
      $client['client_id'],
      $client['name'],
      $client['surname'],
      $client['address'],
      $client['post_code'],
      $client['city'],
      $client['email'],
      $client['number'],
      date('Y-m-d H:i:s'),
      0,
      ($nds['rate'] ?? 0),
      [
        $client['bank_iban'],
        $client['bank_bic'],
        $client['bank_sepa_referenz'],
        $client['bank_sepa_mandat'],
        $client['sepa_type'],
        $client['sepa_standart'],
      ],
      [
        $client['account_owner'],
        $client['account_number'],
        $client['bank_index'],
        $client['bank_name'],
      ],
      $price,
      $priceInfo
    );
    if (!$accountId) {
      return false;
    }

    $relatedData = array_merge([
      'pp_id'                  => $prepaymentId,
      'amount_private_account' => $prepaymentSum,
      'amount_real'            => $price,
      'account_id'             => $accountId,
    ], $gatewayRelatedData);
    $depositData = [
      'client_id'    => (int)$client['client_id'],
      'type_code'    => 'paypal_deposit',
      'amount'       => $prepaymentSum,
      'related_data' => $relatedData,
      'related_id'   => (int)$accountId,
    ];
    if ($createdUser !== null) {
      $depositData['created_user'] = $createdUser;
    }

    $result = ModCommHelper::callSafe('clients', 'PrivateAccount/deposit', $depositData);

    return $result->isSuccess() && (!$closeAccount || $accounts->closedPrepaymentAccount($accountId));
  }

  /**
   * Компенсировать зачисление на личный счёт и удалить предоплатный счёт.
   *
   * @param array<string, mixed>|null $relatedData
   */
  public static function rollbackPrivateAccount(
    int $clientId,
    int $accountId,
    float|int $amount,
    ?array $relatedData = null,
    ?string $createdUser = null,
    bool $updatePayments = true
  ): void {
    $withdrawData = [
      'client_id'  => $clientId,
      'type_code'  => 'paypal_deposit_removed',
      'amount'     => $amount,
      'related_id' => $accountId,
    ];
    if ($relatedData !== null) {
      $withdrawData['related_data'] = $relatedData;
    }
    if ($createdUser !== null) {
      $withdrawData['created_user'] = $createdUser;
    }
    ModCommHelper::callSafe('clients', 'PrivateAccount/withdraw', $withdrawData);

    /** @var AccountsEngine|false $accounts */
    $accounts = getEngine('accounts', false);
    $accounts?->deleteAccount($accountId, $updatePayments);
  }
}
