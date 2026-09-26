<?php

namespace AC\core\modules\reservations\entities\dto;

use AC\app\entities\enums\Encash;
use AC\app\entities\traits\fields\HasId;
use AC\core\modules\reservations\entities\enums\PaymentState;
use AC\core\modules\reservations\entities\enums\Status;
use AC\core\modules\reservations\entities\enums\ToggleState;
use AC\core\modules\reservations\entities\enums\TypeReservation;
use AC\core\modules\reservations\entities\traits\fields\HasReservationMetaData;
use AC\core\system\entities\dto\DtoInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Service;


class ReservationDto implements DtoInterface
{
  use HasId;
  
  public ?int               $reservationId;
  public int                $areaId;
  public ?int               $clientId          = null;
  public TypeReservation    $typeReservation;
  public PaymentState       $paymentState;
  public Status             $status;
  public ?int               $mainReservationId = null;
  public ?int               $mainClientId      = null;
  public ToggleState        $lightState;
  public ToggleState        $heatingState;
  public ToggleState        $netState;
  public ?int               $stockId;
  public ?int               $spriceId;
  public Encash             $encash;
  public DateTimeInterface  $start;
  public DateTimeInterface  $finish;
  public ?DateTimeInterface $ordered           = null;
  public float              $price;
  public ?float             $lightPrice        = 0.00;
  public ?float             $heatingPrice      = 0.00;
  public ?float             $netPrice          = 0.00;
  public ?int               $customer;
  public ?string            $customerTitle;
  public ?string            $clientName;
  public ?string            $clientSurname;
  public string             $memo              = '';
  public ?string            $paypalStatus;
  public ?string            $doorCode;
  public array              $streetFriends     = [];
  public ?string            $payOnlineType;
  use HasReservationMetaData;
  
  /**
   * @inheritDoc
   */
  public static function fromArray(array $data): self
  {
    $dto                    = new self();
    $dto->id                = $data['reservation_id'] ?? null;
    $dto->reservationId     = $data['reservation_id'] ?? null;
    $dto->areaId            = (int)($data['area_id'] ?? 0);
    $dto->clientId          = $data['client_id'] && is_numeric($data['client_id']) ? (int)$data['client_id'] : null;
    $dto->typeReservation   = TypeReservation::from($data['type_reservation'] ?? '1');
    $dto->paymentState      = PaymentState::from((string)($data['payment_state'] ?? '1'));
    $dto->status            = Status::from((string)($data['status'] ?? '1'));
    $dto->mainReservationId = $data['main_reservation_id'] ? (int)$data['main_reservation_id'] : null;
    $dto->mainClientId      = $data['main_client_id'] ? (int)$data['main_client_id'] : null;
    $dto->lightState        = ToggleState::from((string)($data['light_state'] ?? '0'));
    $dto->heatingState      = ToggleState::from((string)($data['heating_state'] ?? '0'));
    $dto->netState          = ToggleState::from((string)($data['net_state'] ?? '0'));
    $dto->stockId           = $data['stock_id'] ?? null;
    $dto->spriceId          = $data['sprice_id'] ?? null;
    $dto->encash            = Encash::from((string)($data['encash'] ?? '1'));
    $dto->start             = new DateTimeImmutable($data['start'] ?? '0000-00-00 00:00:00');
    $dto->finish            = new DateTimeImmutable($data['finish'] ?? '0000-00-00 00:00:00');
    $dto->ordered           = $data['ordered'] ? new DateTimeImmutable($data['ordered']) : null;
    $dto->price             = (float)($data['price'] ?? 0.0);
    $dto->lightPrice        = isset($data['light_price']) ? (float)$data['light_price'] : null;
    $dto->heatingPrice      = isset($data['heating_price']) ? (float)$data['heating_price'] : null;
    $dto->netPrice          = isset($data['net_price']) ? (float)$data['net_price'] : null;
    $dto->customer          = $data['customer'] ?? null;
    $dto->customerTitle     = $data['customer_title'] ?? null;
    $dto->clientName        = $data['client_name'] ?? null;
    $dto->clientSurname     = $data['client_surname'] ?? null;
    $dto->memo              = (string)($data['memo'] ?? '');
    $dto->paypalStatus      = $data['paypal_status'] ?? null;
    $dto->doorCode          = $data['door_code'] ?? null;
    $dto->streetFriends     = Service::cast('array')->get($data['street_friends'] ?? null);
    $dto->payOnlineType     = $data['pay_online_type'] ?? null;
    
    return $dto;
  }
  
  /**
   * @inheritDoc
   */
  public function toArray(): array
  {
    return [
      'id'                  => $this->getId(),
      'reservation_id'      => $this->reservationId,
      'area_id'             => $this->areaId,
      'client_id'           => $this->clientId,
      'type_reservation'    => $this->typeReservation->value,
      'payment_state'       => $this->paymentState->value,
      'status'              => $this->status->value,
      'main_reservation_id' => $this->mainReservationId,
      'main_client_id'      => $this->mainClientId,
      'light_state'         => $this->lightState->value,
      'heating_state'       => $this->heatingState->value,
      'net_state'           => $this->netState->value,
      'stock_id'            => $this->stockId,
      'sprice_id'           => $this->spriceId,
      'encash'              => $this->encash->value,
      'start'               => $this->start->format('Y-m-d H:i:s'),
      'finish'              => $this->finish->format('Y-m-d H:i:s'),
      'ordered'             => $this->ordered?->format('Y-m-d H:i:s'),
      'price'               => $this->price,
      'light_price'         => $this->lightPrice,
      'heating_price'       => $this->heatingPrice,
      'net_price'           => $this->netPrice,
      'customer'            => $this->customer,
      'customer_title'      => $this->customerTitle,
      'client_name'         => $this->clientName,
      'client_surname'      => $this->clientSurname,
      'memo'                => $this->memo,
      'paypal_status'       => $this->paypalStatus,
      'door_code'           => $this->doorCode,
      'street_friends'      => !empty($this->streetFriends) ? Service::cast('array')->set($this->streetFriends) : null,
      'pay_online_type'     => $this->payOnlineType,
    ];
  }
}
