<?php

namespace AC\core\modules\payment\payone\entities\dto;

class   PersonalDataDto
{
  public ?string $customerid;
  public ?string $userid;
  /**
   *  Title of the customer
   *  Dr
   *  Prof.
   *  Dr.-Ing.
   *
   * @var string|null
   */
  public ?string $title;
  public ?string $firstname;
  public ?string $lastname;
  public ?string $street;
  public ?string $addressaddition;
  public ?string $zip;
  public ?string $city;
  public ?string $country;
  public ?string $state = "";
  public ?string $email;
  public ?string $telephonenumber;
  public ?string $birthday;
  public ?string $bankaccountholder;
  public ?string $iban;
  public ?string $customer_is_present;
  public ?string $businessrelation;
  public ?string $language;
  public ?string $gender;
  public ?string $ip;

  public static function fromArray(array $data): self
  {
    $dto                      = new self();
    $dto->customerid          = $data['customerid'] ?? null;
    $dto->title               = $data['title'] ?? '';
    $dto->firstname           = $data['firstname'] ?? null;
    $dto->lastname            = $data['lastname'] ?? null;
    $dto->street              = $data['street'] ?? null;
    $dto->addressaddition     = $data['addressaddition'] ?? '';
    $dto->zip                 = $data['zip'] ?? null;
    $dto->city                = $data['city'] ?? null;
    $dto->country             = $data['country'] ?? config('country')->getDefaultCode();
    $dto->state               = $data['state'] ?? "";
    $dto->email               = $data['email'] ?? null;
    $dto->telephonenumber     = $data['telephonenumber'] ?? '';
    $dto->birthday            = $data['birthday'] ?? '';
    $dto->bankaccountholder   = $data['bankaccountholder'] ?? '';
    $dto->iban                = $data['iban'] ?? '';
    $dto->customer_is_present = $data['customer_is_present'] ?? '';
    $dto->businessrelation    = $data['businessrelation'] ?? '';
    $dto->language            = config('lang')->getCurrentLang();
    $dto->gender              = $data['gender'] ?? '';
    $dto->ip                  = $data['ip'] ?? '';
    $dto->userid              = $data["user_id"] ?? null;

    return $dto;
  }

  public function toArray(): array
  {
    return [
      'customerid'          => $this->customerid,
      'title'               => $this->title,
      'firstname'           => $this->firstname,
      'lastname'            => $this->lastname,
      'street'              => $this->street,
      'addressaddition'     => $this->addressaddition,
      'zip'                 => $this->zip,
      'city'                => $this->city,
      'country'             => $this->country,
      'state'               => $this->state,
      'email'               => $this->email,
      'telephonenumber'     => $this->telephonenumber,
      'birthday'            => $this->birthday,
      'bankaccountholder'   => $this->bankaccountholder,
      'iban'                => $this->iban,
      'customer_is_present' => $this->customer_is_present,
      'businessrelation'    => $this->businessrelation,
      'language'            => $this->language,
      'gender'              => $this->gender,
      'ip'                  => $this->ip,
      'userid'              => $this->userid,
    ];
  }
}