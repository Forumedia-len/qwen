<?php

namespace AC\core\modules\clients\entities\dto;

use AC\core\system\entities\dto\Dto;
use AC\core\system\helpers\JsonHelper;

/**
 * DTO (Объект передачи данных), представляющий транзакцию личного счёта.
 */
class PrivateAccountTransactionDto extends Dto
{
  public ?int    $id;
  public int     $client_id;
  public string  $type_direction;
  public ?string $type_code;
  public float   $amount;
  public ?string $created_at;
  public ?string $created_user;
  public ?array  $related_data;
  public ?int    $related_id;
  public ?string $status;
  public ?string $external_id;
  
  /**
   * Конструктор DTO
   *
   * @param ?int        $id
   * @param int         $client_id
   * @param string      $type_direction
   * @param ?string     $type_code
   * @param float       $amount
   * @param ?string     $created_at
   * @param ?string     $created_user
   * @param             $related_data
   * @param int|null    $related_id
   * @param ?string     $status
   * @param ?string     $external_id
   */
  public function __construct(
    ?int $id,
    int $client_id,
    string $type_direction,
    ?string $type_code,
    float $amount,
    ?string $created_at,
    ?string $created_user,
    $related_data,
    ?int $related_id,
    ?string $status,
    ?string $external_id
  ) {
    $this->id             = $id;
    $this->client_id      = $client_id;
    $this->type_direction = $type_direction;
    $this->type_code      = $type_code;
    $this->created_at     = $created_at;
    $this->created_user   = $created_user;
    $this->related_id     = $related_id;
    $this->amount($amount);
    $this->relatedData($related_data);
    $this->status      = $status;
    $this->external_id = $external_id;
  }
  
  /**
   * Преобразование DTO в ассоциативный массив
   *
   * @return array
   */
  public function toArray(): array
  {
    return [
      'id'             => $this->id,
      'client_id'      => $this->client_id,
      'type_direction' => $this->type_direction,
      'type_code'      => $this->type_code,
      'amount'         => $this->amount,
      'created_at'     => $this->created_at,
      'created_user'   => $this->created_user,
      'related_data'   => $this->related_data,
      'related_id'     => $this->related_id,
      'status'         => $this->status,
      'external_id'    => $this->external_id,
    ];
  }
  
  private function amount(float $amount): void
  {
    $this->amount = abs(round($amount, 2));
  }
  
  protected function relatedData($related_data): void
  {
    if (is_string($related_data)) {
      $related_data = JsonHelper::decode($related_data, true) ?? [];
    }
    
    $this->related_data = $related_data;
  }
  
  /**
   * Создание DTO из массива (например, из формы или API)
   *
   * @param array $data
   * @return self
   */
  public static function fromArray(array $data): self
  {
    return new self(
      id            : (int)($data['id'] ?? null),
      client_id     : (int)$data['client_id'],
      type_direction: $data['type_direction'],
      type_code     : $data['type_code'] ?? null,
      amount        : (float)($data['amount'] ?? 0.00),
      created_at    : $data['created_at'] ?? null,
      created_user  : $data['created_user'] ?? null,
      related_data  : $data['related_data'] ?? [],
      related_id    : isset($data['related_id']) ? (int)$data['related_id'] : null,
      status        : $data['status'] ?? 'pending',
      external_id   : $data['external_id'] ?? null,
    );
  }
  
  /**
   * Создание DTO для новой транзакции (id и created_at могут быть позже)
   *
   * @param array $data
   * @return self
   */
  public static function forCreate(array $data): self
  {
    return new self(
      id            : null,
      client_id     : (int)$data['client_id'],
      type_direction: $data['type_direction'],
      type_code     : $data['type_code'] ?? null,
      amount        : (float)($data['amount'] ?? 0.00),
      created_at    : null,
      created_user  : $data['created_user'] ?? null,
      related_data  : $data['related_data'] ?? [],
      related_id    : isset($data['related_id']) ? (int)$data['related_id'] : null,
      status        : $data['status'] ?? 'pending',
      external_id   : $data['external_id'] ?? null,
    );
  }
}