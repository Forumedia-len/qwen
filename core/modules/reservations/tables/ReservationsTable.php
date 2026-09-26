<?php

namespace AC\core\modules\reservations\tables;

use AC\core\modules\payment\payone\entities\enums\MethodPayment;
use AC\core\modules\reservations\entities\dto\ReservationDto;
use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\system\model\BaseModel;

class ReservationsTable extends BaseModel
{
  public $reservation_id;
  public $area_id;
  public $client_id;
  public $light_state;
  public $heating_state;
  public $net_state;
  public $stock_id;
  public $sprice_id;
  public $encash;
  public $start;
  public $finish;
  public $ordered;
  public $price;
  public $light_price;
  public $heating_price;
  public $net_price;
  public $paypal_status;

  public $date;
  public $time;
  public $memo;

  public $customer = null;
  public $client_name = null;
  public $client_surname = null;
  public $reservation_available_check_error_code = null;
  public $reservation_insert_error_code = null;
  public $inserted_reservation_price = null;
  public $inserted_reservation_id = null;
  public $door_code = null;
  /**
   *  Статус бронирования 1 - полностью готово, 0 - для открытых кортов, если работает функционал присоединения
   *
   * @var int
   */
  public int $status = 1;
  public $main_reservation_id = null;
  public $main_client_id = null;
  /**
   * Статус оплаты сейчас или через paypal (временная таблица)
   *
   * @var bool
   */
  public $online_pay = false;
  /**
   * @var int Статус оплаты 1 - оплачено, 0 - не оплачено(наличка)
   */
  public int $pay_status = 1;
  /**
   * @var int Тип игры 1 - одиночна (2 игрока), 2 - двойная (4 игрока).
   *          По умолчанию одиночная игра
   */
  public $type_reservation = 1;
  /**
   * @var array|string Массив дополнительных игроков
   *    ['id'] - ид клиента или guest если играет гость
   *    ['name'] - имя игрока
   *    ['price'] - стоимость одного периода для этого игрока
   *    ['email'] - email этого игрока
   */
  public $street_friends = [];

  public $webIoStates = [];
  public ?string $payOnlineType = 'elv';


  /** Вставить запись о бронировании
   *
   * @param bool            $error_code
   *
   * @return bool
   * @var ReservationsTable $this
   *
   */
  public function insert(&$error_code = false)
  {
    parent::insert();
    if ($this->online_pay && ($this->paypal_status === null || trim((string)$this->paypal_status) === '')) {
      if (OnlineGatewayService::isSupportedGateway()) {
        $this->paypal_status = OnlineGatewayService::gatewayConfig()->typeKey;
      }
    }
    $currentReservation = [
      'reservation_id'      => $this->reservation_id,
      'area_id'             => $this->area_id,
      'client_id'           => $this->client_id,
      'type_reservation'    => $this->type_reservation,
      'payment_state'       => $this->pay_status,
      'status'              => $this->status,
      'main_reservation_id' => $this->main_reservation_id,
      'main_client_id'      => $this->main_client_id,
      'light_state'         => $this->light_state,
      'heating_state'       => $this->heating_state,
      'net_state'           => $this->net_state,
      'stock_id'            => $this->stock_id && count((array)$this->stock_id) === 1 ? ($this->stock_id[0] ?? $this->stock_id) : null,
      'sprice_id'           => $this->sprice_id ?: null,
      'encash'              => $this->encash,
      'start'               => $this->start,
      'finish'              => $this->finish,
      'price'               => $this->price,
      'light_price'         => $this->light_price,
      'heating_price'       => $this->heating_price,
      'net_price'           => $this->net_price,
      'customer'            => $this->customer,
      'customer_title'      => $this->customerTitle ?? '',
      'client_name'         => $this->client_name,
      'client_surname'      => $this->client_surname,
      'memo'                => $this->memo,
      'paypal_status'       => $this->paypal_status,
      'door_code'           => $this->door_code,
      'street_friends'      => $this->street_friends,
      'pay_online_type'     => $this->getPaymentOnlineType(),
    ];
    $reservationDto     = ReservationDto::fromArray($currentReservation);
    if (is_array($this->stock_id) && count($this->stock_id) > 1) {
      foreach ($this->stock_id as $stockId) {
        $reservationDto->setStock($stockId);
      }
    }
    if ($this->inserted_reservation_id = $this->engine->insertReservation($reservationDto, $this->online_pay, $error_code)) {
      return true;
    }

    return false;
  }

  public function remove()
  {
    if ($this->engine->removeReservationById($this->reservation_id)) {
      return true;
    }

    return false;
  }

  protected function getStocks()
  {
    return (array)$this->stock_id;
  }

  protected function getPaymentOnlineType(): string
  {
    return MethodPayment::tryFrom($this->payOnlineType)?->shortLabel() ?? '';
  }

  protected function setPaymentOnlineType($paymentType = 'PayPal'): void
  {
    $this->payOnlineType = match ($paymentType) {
      'Card'   => 'cc',
      'Wero'   => 'wero',
      'BNPL'   => 'bnpl',
      'Paypal' => 'pp',
      default  => 'wlt',
    };
  }

}