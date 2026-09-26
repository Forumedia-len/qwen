<?php

namespace AC\core\modules\clients\entities\dto;

use Service;

class ClientDto
{
  public int     $client_id;
  public string  $mode;
  public string  $system_mode;
  public string  $super;
  public string  $area_type;
  public ?int    $club_state;
  public string  $encash;
  public ?string $active;
  public ?int    $nds;
  public ?int    $discount;
  public ?string $login;
  public ?string $password_md5;
  public ?string $number;
  public string  $name;
  public string  $surname;
  public ?string $birthday;
  public string  $phone;
  public string  $phone_mobile;
  public string  $fax;
  public string  $city;
  public string  $post_code;
  public string  $address;
  public string  $email;
  public ?string $start_order_period;
  public int     $order_period;
  public int     $reservations_removed;
  public string  $registered;
  public ?string $account_owner;
  public ?string $account_number;
  public ?string $bank_index;
  public ?string $bank_name;
  public ?string $codecard;
  public int     $limit_day;
  public float   $prepayment_sum;
  public array   $stock_id;
  public array   $sprice_id;
  public string  $sepa_type;
  public string  $sepa_standart;
  public ?string $bank_iban;
  public ?string $bank_bic;
  public ?string $bank_sepa_mandat;
  public ?string $bank_sepa_referenz;
  public ?string $unavailable_sports;
  public int     $abo_delete;
  public int     $refund_for_ticket;
  public int     $refund_for_paypal;
  public string  $student;
  public ?string $student_number;
  public ?string $firm;
  public ?string $preferences;

  public static function fromArray(array $data): self
  {
    $dto = new self();

    $dto->client_id            = (int)($data['client_id'] ?? 0);
    $dto->mode                 = (string)($data['mode'] ?? '1');
    $dto->system_mode          = (string)($data['system_mode'] ?? '0');
    $dto->super                = (string)($data['super'] ?? '0');
    $dto->area_type            = (string)($data['area_type'] ?? '0');
    $dto->club_state           = isset($data['club_state']) ? (int)$data['club_state'] : null;
    $dto->encash               = (string)($data['encash'] ?? '0');
    $dto->active               = isset($data['active']) ? (string)$data['active'] : null;
    $dto->nds                  = isset($data['nds']) ? (int)$data['nds'] : null;
    $dto->discount             = isset($data['discount']) ? (int)$data['discount'] : null;
    $dto->login                = $data['login'] ?? null;
    $dto->password_md5         = $data['password_md5'] ?? null;
    $dto->number               = $data['number'] ?? null;
    $dto->name                 = (string)($data['name'] ?? '');
    $dto->surname              = (string)($data['surname'] ?? '');
    $dto->birthday             = isset($data['birthday']) ? (string)$data['birthday'] : null;
    $dto->phone                = (string)($data['phone'] ?? '');
    $dto->phone_mobile         = (string)($data['phone_mobile'] ?? '');
    $dto->fax                  = (string)($data['fax'] ?? '');
    $dto->city                 = (string)($data['city'] ?? '');
    $dto->post_code            = (string)($data['post_code'] ?? '');
    $dto->address              = (string)($data['address'] ?? '');
    $dto->email                = (string)($data['email'] ?? '');
    $dto->start_order_period   = isset($data['start_order_period']) ? (string)$data['start_order_period'] : null;
    $dto->order_period         = (int)($data['order_period'] ?? 0);
    $dto->reservations_removed = (int)($data['reservations_removed'] ?? 0);
    $dto->registered           = (string)($data['registered'] ?? '0000-00-00 00:00:00');
    $dto->account_owner        = $data['account_owner'] ?? null;
    $dto->account_number       = $data['account_number'] ?? null;
    $dto->bank_index           = $data['bank_index'] ?? null;
    $dto->bank_name            = $data['bank_name'] ?? null;
    $dto->codecard             = $data['codecard'] ?? null;
    $dto->limit_day            = (int)($data['limit_day'] ?? 0);
    $dto->prepayment_sum       = (float)($data['prepayment_sum'] ?? 0.0);
    $dto->stock_id             = !empty($data['stock_id']) ? $dto->setUnserialize($data['stock_id']) : [];
    $dto->sprice_id            = !empty($data['sprice_id']) ? $dto->setUnserialize($data['sprice_id']) : [];
    $dto->sepa_type            = (string)($data['sepa_type'] ?? '0');
    $dto->sepa_standart        = (string)($data['sepa_standart'] ?? '0');
    $dto->bank_iban            = $data['bank_iban'] ?? null;
    $dto->bank_bic             = $data['bank_bic'] ?? null;
    $dto->bank_sepa_mandat     = $data['bank_sepa_mandat'] ?? null;
    $dto->bank_sepa_referenz   = $data['bank_sepa_referenz'] ?? null;
    $dto->unavailable_sports   = $data['unavailable_sports'] ?? null;
    $dto->abo_delete           = (int)($data['abo_delete'] ?? 0);
    $dto->refund_for_ticket    = (int)($data['refund_for_ticket'] ?? 0);
    $dto->refund_for_paypal    = (int)($data['refund_for_paypal'] ?? 0);
    $dto->student              = (string)($data['student'] ?? '0');
    $dto->student_number       = $data['student_number'] ?? null;
    $dto->firm                 = $data['firm'] ?? null;
    $dto->preferences          = $data['preferences'] ?? null;

    return $dto;
  }

  public function toArray(): array
  {
    return [
      'client_id'            => (int)$this->client_id,
      'mode'                 => (string)$this->mode,
      'system_mode'          => (string)$this->system_mode,
      'super'                => (string)$this->super,
      'area_type'            => (string)$this->area_type,
      'club_state'           => isset($this->club_state) ? (int)$this->club_state : null,
      'encash'               => (string)$this->encash,
      'active'               => $this->active !== null ? (string)$this->active : null,
      'nds'                  => $this->nds !== null ? (int)$this->nds : null,
      'discount'             => $this->discount !== null ? (int)$this->discount : null,
      'login'                => $this->login !== null ? (string)$this->login : null,
      'password_md5'         => $this->password_md5 !== null ? (string)$this->password_md5 : null,
      'number'               => $this->number !== null ? (string)$this->number : null,
      'name'                 => (string)$this->name,
      'surname'              => (string)$this->surname,
      'birthday'             => $this->birthday !== null ? (string)$this->birthday : null,
      'phone'                => (string)$this->phone,
      'phone_mobile'         => (string)$this->phone_mobile,
      'fax'                  => (string)$this->fax,
      'city'                 => (string)$this->city,
      'post_code'            => (string)$this->post_code,
      'address'              => (string)$this->address,
      'email'                => (string)$this->email,
      'start_order_period'   => $this->start_order_period !== null ? (string)$this->start_order_period : null,
      'order_period'         => (int)$this->order_period,
      'reservations_removed' => (int)$this->reservations_removed,
      'registered'           => (string)$this->registered,
      'account_owner'        => $this->account_owner !== null ? (string)$this->account_owner : null,
      'account_number'       => $this->account_number !== null ? (string)$this->account_number : null,
      'bank_index'           => $this->bank_index !== null ? (string)$this->bank_index : null,
      'bank_name'            => $this->bank_name !== null ? (string)$this->bank_name : null,
      'codecard'             => $this->codecard !== null ? (string)$this->codecard : null,
      'limit_day'            => (int)$this->limit_day,
      'prepayment_sum'       => (float)$this->prepayment_sum,
      'stock_id'             => (string)Service::cast('array')->set($this->stock_id),
      'sprice_id'            => (string)Service::cast('array')->set($this->sprice_id),
      'sepa_type'            => (string)$this->sepa_type,
      'sepa_standart'        => (string)$this->sepa_standart,
      'bank_iban'            => $this->bank_iban !== null ? (string)$this->bank_iban : null,
      'bank_bic'             => $this->bank_bic !== null ? (string)$this->bank_bic : null,
      'bank_sepa_mandat'     => $this->bank_sepa_mandat !== null ? (string)$this->bank_sepa_mandat : null,
      'bank_sepa_referenz'   => $this->bank_sepa_referenz !== null ? (string)$this->bank_sepa_referenz : null,
      'unavailable_sports'   => $this->unavailable_sports !== null ? (string)$this->unavailable_sports : null,
      'abo_delete'           => (int)$this->abo_delete,
      'refund_for_ticket'    => (int)$this->refund_for_ticket,
      'refund_for_paypal'    => (int)$this->refund_for_paypal,
      'student'              => (string)$this->student,
      'student_number'       => $this->student_number !== null ? (string)$this->student_number : null,
      'firm'                 => $this->firm !== null ? (string)$this->firm : null,
      'preferences'          => $this->preferences !== null ? (string)$this->preferences : null,
    ];
  }

  private function setUnserialize($data): array
  {
    return is_array($data) ? $data : Service::cast('array')->get($data);
  }

  public static function getProperties(): array
  {
    static $properties;
    if ($properties === null) {
      $properties = array_keys(self::fromArray([])->toArray());
    }
    return $properties ?? [];
  }
}