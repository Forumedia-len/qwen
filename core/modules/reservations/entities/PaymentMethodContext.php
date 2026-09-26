<?php

namespace AC\core\modules\reservations\entities;

use AC\app\entities\enums\Encash;

final class PaymentMethodContext
{
  public function __construct(
    public int $typeId,
    public int $sportId,
    public Encash $clientMethod,
    public bool $barClient,
    public float $price,
    public float $privateAccountBalance,
    public string $channel
  ) {
  }
}
