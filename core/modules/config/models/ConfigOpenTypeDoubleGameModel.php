<?php

namespace AC\core\modules\config\models;

use AC\core\engines\ConfigEngine;

class ConfigOpenTypeDoubleGameModel
{
  private const BOOL_ALIASES = [
    'DOUBLE_FRIENDS_OPEN_COURT',
    'USE_DOUBLE_OPEN_COURT',
    'USE_ONLY_DOUBLE_GAME',
  ];

  private const INT_ALIASES = [
    'DOUBLE_PLAYERS_TIME_COUNT',
    'DOUBLE_PLAYERS_TIME_COUNT_MAX',
    'DOUBLE_FRIENDS_OPEN_COURT_NUMBER_PLAYERS',
    'DOUBLE_PRICE_COUNT_PLAYERS',
  ];

  public function __construct(
    private readonly ConfigEngine $engine,
  ) {
  }

  /**
   * @return list<array{alias:string,label:string,input:string,value:mixed}>
   */
  public function buildAdminFields(?int $scopeId): array
  {
    $double = config('DoubleGame');

    return [
      [
        'alias' => 'double_friends_open_court',
        'label' => lang('double_friends_open_court', 'config_open_type'),
        'input' => 'bool',
        'value' => $double->doubleFriendsEnabled($scopeId) ? 1 : 0,
      ],
      [
        'alias' => 'use_double_open_court',
        'label' => lang('use_double_open_court', 'config_open_type'),
        'input' => 'bool',
        'value' => $double->doubleGameEnabled($scopeId) ? 1 : 0,
      ],
      [
        'alias' => 'use_only_double_game',
        'label' => lang('use_only_double_game', 'config_open_type'),
        'input' => 'bool',
        'value' => $double->onlyDoubleGame($scopeId) ? 1 : 0,
      ],
      [
        'alias' => 'double_players_time_count',
        'label' => lang('double_players_time_count', 'config_open_type'),
        'input' => 'int',
        'value' => $double->getNumberOfPeriods($scopeId),
      ],
      [
        'alias' => 'double_players_time_count_max',
        'label' => lang('double_players_time_count_max', 'config_open_type'),
        'input' => 'int',
        'value' => $double->useMaximumPeriodValue($scopeId)
          ? $double->getMaxNumberOfPeriods($scopeId)
          : 0,
      ],
      [
        'alias' => 'double_friends_open_court_number_players',
        'label' => lang('double_friends_open_court_number_players', 'config_open_type'),
        'input' => 'int',
        'value' => $double->numberPlayers($scopeId),
      ],
      [
        'alias' => 'double_price_count_players',
        'label' => lang('double_price_count_players', 'config_open_type'),
        'input' => 'int',
        'value' => $double->priceCountPlayers($scopeId),
      ],
    ];
  }

  public function getConfigTypeKey(int $typeId, ?int $sportId = null, ?int $areaId = null): string
  {
    return config('DoubleGame')->buildConfigTypeKey(
      $typeId > 0 ? $typeId : null,
      $sportId,
      $areaId,
    );
  }

  public function save(int $typeId, array $data, ?int $sportId = null, ?int $areaId = null): bool
  {
    $typeKey = $this->getConfigTypeKey($typeId, $sportId, $areaId);

    foreach ($this->constAliases() as $constAlias) {
      $dbAlias = $this->dbAlias($constAlias);
      $raw     = $this->readPostValue($data, $constAlias);
      $value   = $this->normalizeSavedValue($constAlias, $raw);

      if (!$this->saveConfigItem($typeKey, $dbAlias, $value)) {
        return false;
      }
    }

    return true;
  }

  /**
   * @return list<string>
   */
  private function constAliases(): array
  {
    return array_merge(self::BOOL_ALIASES, self::INT_ALIASES);
  }

  private function dbAlias(string $constAlias): string
  {
    return strtolower($constAlias);
  }

  private function readPostValue(array $data, string $constAlias): mixed
  {
    $dbAlias = $this->dbAlias($constAlias);
    if (array_key_exists($dbAlias, $data)) {
      return $data[$dbAlias];
    }
    if (array_key_exists($constAlias, $data)) {
      return $data[$constAlias];
    }

    return null;
  }

  /**
   * Дефолты и ограничения как в DoubleGameConfig + app/uses/defined.php.
   */
  private function normalizeSavedValue(string $constAlias, mixed $raw): string
  {
    if (in_array($constAlias, self::BOOL_ALIASES, true)) {
      if ($raw === null) {
        return defined($constAlias) && constant($constAlias) ? '1' : '0';
      }

      return $raw ? '1' : '0';
    }

    $default = defined($constAlias) ? (int)constant($constAlias) : 0;
    $int     = ($raw !== null && $raw !== '') ? (int)$raw : $default;

    return match ($constAlias) {
      'DOUBLE_PLAYERS_TIME_COUNT_MAX' => (string)max(0, $int),
      'DOUBLE_FRIENDS_OPEN_COURT_NUMBER_PLAYERS' => (string)max(2, $int),
      'DOUBLE_PRICE_COUNT_PLAYERS' => (string)max(1, $int),
      default => (string)$int,
    };
  }

  private function resolveDbAlias(string $typeKey, string $dbAlias): string
  {
    $byType = ConfigModel::getByType($typeKey);
    if (!is_object($byType)) {
      return $dbAlias;
    }

    foreach (array_keys((array)$byType) as $key) {
      if (strcasecmp((string)$key, $dbAlias) === 0) {
        return (string)$key;
      }
    }

    return $dbAlias;
  }

  private function saveConfigItem(string $typeKey, string $dbAlias, string $value): bool
  {
    $dbAlias = $this->resolveDbAlias($typeKey, $dbAlias);

    return $this->engine->saveItem($dbAlias, $value, $typeKey);
  }
}
