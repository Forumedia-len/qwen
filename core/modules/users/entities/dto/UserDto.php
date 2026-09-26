<?php

namespace AC\core\modules\users\entities\dto;

use AC\app\entities\traits\sets\HasUser;
use AC\core\system\helpers\JsonHelper;
use DateTimeImmutable;
use DateTimeInterface;

class UserDto
{
  public int $user_id;
  use HasUser {
    fromArray as traitFromArray;
    toArray as traitToArray;
  }

  public ?DateTimeInterface $created;
  public ?DateTimeInterface $last_login;

  private string $_preferences = '';

  public static function fromArray(array $data): self
  {
    $dto = self::traitFromArray($data);
    $dto->user_id = $data['user_id'];
    $dto->created = !empty($data['created']) ? new DateTimeImmutable($data['created']) : null;
    $dto->last_login = !empty($data['last_login']) ? new DateTimeImmutable($data['last_login']) : null;
    $dto->setPreferences($data['preferences']);
    
    return $dto;
  }

  public function toArray(): array
  {
    $array = $this->traitToArray();
    $array['user_id'] = $this->user_id;
    $array['created'] = $this->created->format('Y-m-d H:i:s');
    $array['last_login'] = $this->last_login ? $this->last_login->format('Y-m-d H:i:s') : null;
    $array['preferences'] = $this->getPreferences();
    
    return $array;
  }

  private function setPreferences($preferences): void
  {
    $this->_preferences = !empty($preferences)
      ? (is_array($preferences) || is_object($preferences) ? JsonHelper::encode($preferences) : (string)$preferences)
      : JsonHelper::encode([]);
  }

  public function getPreferences(): array
  {
    return JsonHelper::decode($this->_preferences, true) ?? [];
  }
}
