<?php

namespace AC\core\modules\config\contexts;

use AC\core\modules\config\scoped\ScopedConfigPolicy;
use AC\core\modules\config\scoped\ScopedConfigQuery;

/**
 * Контекст ограничения клиента для scoped-config (L4/L5).
 *
 * Содержит scope бронирования: корт (typeId/alias), клуб (clubMark), устройство.
 * Преобразуется в ScopedConfigQuery для чтения через ScopedConfigGateway.
 */
final class RestrictionContext
{
  public function __construct(
    public readonly ?int $typeId = null,
    public readonly ?int $sportId = null,
    public readonly ?int $areaId = null,
    public readonly ?int $clubStateId = null,
    public readonly ?string $clubMark = null,
    public readonly ?string $device = null,
    public readonly ?string $courtTypeAlias = null,
  ) {}

  /**
   * Собирает ScopedConfigQuery для gateway (prefix=restriction, policy RESTRICTION).
   */
  public function toQuery(string $dbAlias, bool $nullIfMissing = true): ScopedConfigQuery
  {
    $dimensions = array_filter([
      'clubMark'         => $this->clubMark,
      'courtTypeAlias'   => $this->courtTypeAlias,
      'fallbackTypeAlias'=> $this->courtTypeAlias ?? 'default',
    ], static fn(mixed $value) => $value !== null && $value !== '');

    return new ScopedConfigQuery(
      prefix: 'restriction',
      alias: $dbAlias,
      typeId: $this->typeId,
      sportId: $this->sportId,
      areaId: $this->areaId,
      policy: ScopedConfigPolicy::RESTRICTION,
      dimensions: $dimensions,
      default: null,
      nullIfMissing: $nullIfMissing,
    );
  }

  /**
   * Контекст из alias корта и clubMark (админка, unit-тесты, legacy API).
   */
  public static function fromCourtAliasAndClub(
    string $courtTypeAlias,
    string $clubMark,
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    ?int $clubStateId = null,
  ): self {
    return new self(
      typeId: $typeId,
      sportId: $sportId,
      areaId: $areaId,
      clubStateId: $clubStateId,
      clubMark: $clubMark,
      courtTypeAlias: $courtTypeAlias,
    );
  }
}
