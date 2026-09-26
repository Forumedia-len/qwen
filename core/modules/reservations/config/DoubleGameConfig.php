<?php

namespace AC\core\modules\reservations\config;

use AC\core\modules\config\scoped\traits\ScopedConfigConstFallbackTrait;
use AC\core\modules\config\scoped\traits\ScopedConfigScopeTrait;
use AC\core\system\config\BaseConfig;

class DoubleGameConfig extends BaseConfig
{
  use ScopedConfigScopeTrait;
  use ScopedConfigConstFallbackTrait;

  /**
   * Формирует ключ типа конфигурации для настроек парной игры.
   *
   * @param int|null $typeId Идентификатор типа бронирования.
   * @param int|null $sportId Идентификатор вида спорта.
   * @param int|null $areaId Идентификатор зоны.
   * @param string $fallbackTypeAlias Алиас типа для резервного поиска значения.
   */
  public function buildConfigTypeKey(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default',
  ): string {
    return $this->buildScopedTypeKey('double', $typeId, $sportId, $areaId, $fallbackTypeAlias);
  }

  /**
   * Проверяет, нужно ли использовать максимальное значение количества периодов.
   *
   * @param int|null $typeId Идентификатор типа бронирования.
   * @param int|null $sportId Идентификатор вида спорта.
   * @param int|null $areaId Идентификатор зоны.
   * @param string $fallbackTypeAlias Алиас типа для резервного поиска значения.
   */
  public function useMaximumPeriodValue(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default'
  ): bool {
    return $this->resolveBool(
      $this->buildScopedConfigQuery('DOUBLE_PLAYERS_TIME_COUNT_MAX', $typeId, $sportId, $areaId, $fallbackTypeAlias, 'double'),
      'DOUBLE_PLAYERS_TIME_COUNT_MAX',
      false,
    );
  }

  /**
   * Возвращает настроенное количество периодов для парной игры.
   *
   * @param int|null $typeId Идентификатор типа бронирования.
   * @param int|null $sportId Идентификатор вида спорта.
   * @param int|null $areaId Идентификатор зоны.
   * @param string $fallbackTypeAlias Алиас типа для резервного поиска значения.
   */
  public function getNumberOfPeriods(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default'
  ): int {
    return $this->resolveInt(
      $this->buildScopedConfigQuery('DOUBLE_PLAYERS_TIME_COUNT', $typeId, $sportId, $areaId, $fallbackTypeAlias, 'double'),
      'DOUBLE_PLAYERS_TIME_COUNT',
      2,
    );
  }

  /**
   * Возвращает максимальное настроенное количество периодов для парной игры.
   *
   * @param int|null $typeId Идентификатор типа бронирования.
   * @param int|null $sportId Идентификатор вида спорта.
   * @param int|null $areaId Идентификатор зоны.
   * @param string $fallbackTypeAlias Алиас типа для резервного поиска значения.
   */
  public function getMaxNumberOfPeriods(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default'
  ): int {
    if (!$this->useMaximumPeriodValue($typeId, $sportId, $areaId, $fallbackTypeAlias)) {
      return $this->getNumberOfPeriods($typeId, $sportId, $areaId, $fallbackTypeAlias);
    }

    return $this->resolveInt(
      $this->buildScopedConfigQuery('DOUBLE_PLAYERS_TIME_COUNT_MAX', $typeId, $sportId, $areaId, $fallbackTypeAlias, 'double'),
      'DOUBLE_PLAYERS_TIME_COUNT_MAX',
      $this->getNumberOfPeriods($typeId, $sportId, $areaId, $fallbackTypeAlias),
    );
  }

  /**
   * Проверяет, можно ли использовать друзей для открытого корта в парной игре.
   *
   * @param int|null $typeId Идентификатор типа бронирования.
   * @param int|null $sportId Идентификатор вида спорта.
   * @param int|null $areaId Идентификатор зоны.
   * @param string $fallbackTypeAlias Алиас типа для резервного поиска значения.
   */
  public function doubleFriendsEnabled(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default'
  ): bool {
    return $this->resolveBool(
      $this->buildScopedConfigQuery('DOUBLE_FRIENDS_OPEN_COURT', $typeId, $sportId, $areaId, $fallbackTypeAlias, 'double'),
      'DOUBLE_FRIENDS_OPEN_COURT',
      true,
    );
  }

  /**
   * Проверяет, включены ли бронирования открытого корта для парной игры.
   *
   * @param int|null $typeId Идентификатор типа бронирования.
   * @param int|null $sportId Идентификатор вида спорта.
   * @param int|null $areaId Идентификатор зоны.
   * @param string $fallbackTypeAlias Алиас типа для резервного поиска значения.
   */
  public function doubleGameEnabled(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default'
  ): bool {
    return $this->resolveBool(
      $this->buildScopedConfigQuery('USE_DOUBLE_OPEN_COURT', $typeId, $sportId, $areaId, $fallbackTypeAlias, 'double'),
      'USE_DOUBLE_OPEN_COURT',
      false,
    );
  }

  /**
   * Проверяет, разрешены ли только бронирования парной игры.
   *
   * @param int|null $typeId Идентификатор типа бронирования.
   * @param int|null $sportId Идентификатор вида спорта.
   * @param int|null $areaId Идентификатор зоны.
   * @param string $fallbackTypeAlias Алиас типа для резервного поиска значения.
   */
  public function onlyDoubleGame(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default'
  ): bool {
    return $this->resolveBool(
      $this->buildScopedConfigQuery('USE_ONLY_DOUBLE_GAME', $typeId, $sportId, $areaId, $fallbackTypeAlias, 'double'),
      'USE_ONLY_DOUBLE_GAME',
      false,
    );
  }

  /**
   * Возвращает минимальное количество игроков для парной игры с друзьями.
   *
   * @param int|null $typeId Идентификатор типа бронирования.
   * @param int|null $sportId Идентификатор вида спорта.
   * @param int|null $areaId Идентификатор зоны.
   * @param string $fallbackTypeAlias Алиас типа для резервного поиска значения.
   */
  public function numberPlayers(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default'
  ): int {
    return max(2, $this->resolveInt(
      $this->buildScopedConfigQuery('DOUBLE_FRIENDS_OPEN_COURT_NUMBER_PLAYERS', $typeId, $sportId, $areaId, $fallbackTypeAlias, 'double'),
      'DOUBLE_FRIENDS_OPEN_COURT_NUMBER_PLAYERS',
      2,
    ));
  }

  /**
   * Возвращает количество игроков, используемое при расчете цены парной игры.
   *
   * @param int|null $typeId Идентификатор типа бронирования.
   * @param int|null $sportId Идентификатор вида спорта.
   * @param int|null $areaId Идентификатор зоны.
   * @param string $fallbackTypeAlias Алиас типа для резервного поиска значения.
   */
  public function priceCountPlayers(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default'
  ): int {
    return max(1, $this->resolveInt(
      $this->buildScopedConfigQuery('DOUBLE_PRICE_COUNT_PLAYERS', $typeId, $sportId, $areaId, $fallbackTypeAlias, 'double'),
      'DOUBLE_PRICE_COUNT_PLAYERS',
      2,
    ));
  }

}
