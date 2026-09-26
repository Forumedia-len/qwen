<?php

namespace AC\app\entities\traits\sets;

use AC\app\entities\enums\State;
use AC\app\entities\enums\UserRights;
use AC\app\entities\traits\fields\HasId;
use Service;

trait HasUser
{
  use HasId;
  
  public UserRights $rights;
  public ?string    $login;
  public ?string    $password;
  public string     $email;
  public ?string    $name;
  public ?string    $code;
  public State      $default;
  public ?string    $preferences;
  public string     $language;
  
  public static function fromArray(array $data): self
  {
    $dto = new self();
    
    $dto->id          = (int)($data['user_id'] ?? 0);
    $dto->rights      = UserRights::from((string)($data['rights'] ?? '1'));
    $dto->login       = $data['login'] ?? null;
    $dto->password    = $data['password'] ?? null;
    $dto->email       = (string)($data['email'] ?? '');
    $dto->name        = $data['name'] ?? null;
    $dto->code        = $data['code'] ?? null;
    $dto->default     = State::from($data['default'] ?? '0');
    $dto->preferences = $data['preferences'] ?? null;
    $dto->language    = (string)($data['language'] ?? 'de');
    
    return $dto;
  }
  
  public function toArray(): array
  {
    return [
      'user_id'     => (int)$this->id,
      'rights'      => $this->rights->value,
      'login'       => $this->login !== null ? (string)$this->login : null,
      'password'    => $this->password !== null ? (string)$this->password : null,
      'email'       => (string)$this->email,
      'name'        => $this->name !== null ? (string)$this->name : null,
      'code'        => $this->code !== null ? (string)$this->code : null,
      'default'     => $this->default->value,
      'preferences' => $this->preferences !== null ? (string)$this->preferences : null,
      'language'    => (string)$this->language,
    ];
  }
}
