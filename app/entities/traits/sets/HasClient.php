<?php

namespace AC\app\entities\traits\sets;

use AC\app\entities\enums\Encash;
use AC\app\entities\traits\fields\HasId;
use Service;

trait HasClient
{
  use HasId;
  
  public string  $mode;
  public string  $systemMode;
  public string  $super;
  public string  $areaType;
  public ?int    $clubState;
  public Encash  $encash;
  public ?string $active;
  public ?int    $nds;
  public ?int    $discount;
  public ?string $login;
  public ?string $passwordMd5;
  public ?string $number;
  public string  $name;
  public string  $surname;
  public ?string $birthday;
  public string  $phone;
  public string  $phoneMobile;
  public string  $fax;
  public string  $city;
  public string  $postCode;
  public string  $address;
  public string  $email;
  public ?string $startOrderPeriod;
  public int     $orderPeriod;
  public int     $reservationsRemoved;
  public string  $registered;
  public ?string $accountOwner;
  public ?string $accountNumber;
  public ?string $bankIndex;
  public ?string $bankName;
  public ?string $codecard;
  public int     $limitDay;
  public float   $prepaymentSum;
  public array   $stockId;
  public array   $spriceId;
  public string  $sepaType;
  public string  $sepaStandard;
  public ?string $bankIban;
  public ?string $bankBic;
  public ?string $bankSepaMandat;
  public ?string $bankSepaReferenz;
  public ?string $unavailableSports;
  public int     $aboDelete;
  public int     $refundForTicket;
  public int     $refundForPaypal;
  public string  $student;
  public ?string $studentNumber;
  public ?string $firm;
  public ?string $preferences;
  
  public static function fromArray(array $data): self
  {
    $dto = new self();
    
    $dto->id                  = (int)($data['client_id'] ?? 0);
    $dto->mode                = (string)($data['mode'] ?? '1');
    $dto->systemMode          = (string)($data['system_mode'] ?? '0');
    $dto->super               = (string)($data['super'] ?? '0');
    $dto->areaType            = (string)($data['area_type'] ?? '0');
    $dto->clubState           = isset($data['club_state']) ? (int)$data['club_state'] : null;
    $dto->encash              = Encash::from((string)($data['encash'] ?? '1'));
    $dto->active              = isset($data['active']) ? (string)$data['active'] : null;
    $dto->nds                 = isset($data['nds']) ? (int)$data['nds'] : null;
    $dto->discount            = isset($data['discount']) ? (int)$data['discount'] : null;
    $dto->login               = $data['login'] ?? null;
    $dto->passwordMd5         = $data['password_md5'] ?? null;
    $dto->number              = $data['number'] ?? null;
    $dto->name                = (string)($data['name'] ?? '');
    $dto->surname             = (string)($data['surname'] ?? '');
    $dto->birthday            = isset($data['birthday']) ? (string)$data['birthday'] : null;
    $dto->phone               = (string)($data['phone'] ?? '');
    $dto->phoneMobile         = (string)($data['phone_mobile'] ?? '');
    $dto->fax                 = (string)($data['fax'] ?? '');
    $dto->city                = (string)($data['city'] ?? '');
    $dto->postCode            = (string)($data['post_code'] ?? '');
    $dto->address             = (string)($data['address'] ?? '');
    $dto->email               = (string)($data['email'] ?? '');
    $dto->startOrderPeriod    = isset($data['start_order_period']) ? (string)$data['start_order_period'] : null;
    $dto->orderPeriod         = (int)($data['order_period'] ?? 0);
    $dto->reservationsRemoved = (int)($data['reservations_removed'] ?? 0);
    $dto->registered          = (string)($data['registered'] ?? '0000-00-00 00:00:00');
    $dto->accountOwner        = $data['account_owner'] ?? null;
    $dto->accountNumber       = $data['account_number'] ?? null;
    $dto->bankIndex           = $data['bank_index'] ?? null;
    $dto->bankName            = $data['bank_name'] ?? null;
    $dto->codecard            = $data['codecard'] ?? null;
    $dto->limitDay            = (int)($data['limit_day'] ?? 0);
    $dto->prepaymentSum       = (float)($data['prepayment_sum'] ?? 0.0);
    $dto->stockId             = !empty($data['stock_id']) ? $dto->setUnserialize($data['stock_id']) : [];
    $dto->spriceId            = !empty($data['sprice_id']) ? $dto->setUnserialize($data['sprice_id']) : [];
    $dto->sepaType            = (string)($data['sepa_type'] ?? '0');
    $dto->sepaStandard        = (string)($data['sepa_standart'] ?? '0');
    $dto->bankIban            = $data['bank_iban'] ?? null;
    $dto->bankBic             = $data['bank_bic'] ?? null;
    $dto->bankSepaMandat      = $data['bank_sepa_mandat'] ?? null;
    $dto->bankSepaReferenz    = $data['bank_sepa_referenz'] ?? null;
    $dto->unavailableSports   = $data['unavailable_sports'] ?? null;
    $dto->aboDelete           = (int)($data['abo_delete'] ?? 0);
    $dto->refundForTicket     = (int)($data['refund_for_ticket'] ?? 0);
    $dto->refundForPaypal     = (int)($data['refund_for_paypal'] ?? 0);
    $dto->student             = (string)($data['student'] ?? '0');
    $dto->studentNumber       = $data['student_number'] ?? null;
    $dto->firm                = $data['firm'] ?? null;
    $dto->preferences         = $data['preferences'] ?? null;
    
    return $dto;
  }
  
  public function toArray(): array
  {
    return [
      'client_id'            => (int)$this->id,
      'mode'                 => (string)$this->mode,
      'system_mode'          => (string)$this->systemMode,
      'super'                => (string)$this->super,
      'area_type'            => (string)$this->areaType,
      'club_state'           => $this->clubState !== null ? (int)$this->clubState : null,
      'encash'               => $this->encash->value,
      'active'               => $this->active !== null ? (string)$this->active : null,
      'nds'                  => $this->nds !== null ? (int)$this->nds : null,
      'discount'             => $this->discount !== null ? (int)$this->discount : null,
      'login'                => $this->login !== null ? (string)$this->login : null,
      'password_md5'         => $this->passwordMd5 !== null ? (string)$this->passwordMd5 : null,
      'number'               => $this->number !== null ? (string)$this->number : null,
      'name'                 => (string)$this->name,
      'surname'              => (string)$this->surname,
      'birthday'             => $this->birthday !== null ? (string)$this->birthday : null,
      'phone'                => (string)$this->phone,
      'phone_mobile'         => (string)$this->phoneMobile,
      'fax'                  => (string)$this->fax,
      'city'                 => (string)$this->city,
      'post_code'            => (string)$this->postCode,
      'address'              => (string)$this->address,
      'email'                => (string)$this->email,
      'start_order_period'   => $this->startOrderPeriod !== null ? (string)$this->startOrderPeriod : null,
      'order_period'         => (int)$this->orderPeriod,
      'reservations_removed' => (int)$this->reservationsRemoved,
      'registered'           => (string)$this->registered,
      'account_owner'        => $this->accountOwner !== null ? (string)$this->accountOwner : null,
      'account_number'       => $this->accountNumber !== null ? (string)$this->accountNumber : null,
      'bank_index'           => $this->bankIndex !== null ? (string)$this->bankIndex : null,
      'bank_name'            => $this->bankName !== null ? (string)$this->bankName : null,
      'codecard'             => $this->codecard !== null ? (string)$this->codecard : null,
      'limit_day'            => (int)$this->limitDay,
      'prepayment_sum'       => (float)$this->prepaymentSum,
      'stock_id'             => (string)Service::cast('array')->set($this->stockId),
      'sprice_id'            => (string)Service::cast('array')->set($this->spriceId),
      'sepa_type'            => (string)$this->sepaType,
      'sepa_standart'        => (string)$this->sepaStandard,
      'bank_iban'            => $this->bankIban !== null ? (string)$this->bankIban : null,
      'bank_bic'             => $this->bankBic !== null ? (string)$this->bankBic : null,
      'bank_sepa_mandat'     => $this->bankSepaMandat !== null ? (string)$this->bankSepaMandat : null,
      'bank_sepa_referenz'   => $this->bankSepaReferenz !== null ? (string)$this->bankSepaReferenz : null,
      'unavailable_sports'   => $this->unavailableSports !== null ? (string)$this->unavailableSports : null,
      'abo_delete'           => (int)$this->aboDelete,
      'refund_for_ticket'    => (int)$this->refundForTicket,
      'refund_for_paypal'    => (int)$this->refundForPaypal,
      'student'              => (string)$this->student,
      'student_number'       => $this->studentNumber !== null ? (string)$this->studentNumber : null,
      'firm'                 => $this->firm !== null ? (string)$this->firm : null,
      'preferences'          => $this->preferences !== null ? (string)$this->preferences : null,
    ];
  }
  
  private function setUnserialize($data): array
  {
    return is_array($data) ? $data : Service::cast('array')->get($data);
  }
}