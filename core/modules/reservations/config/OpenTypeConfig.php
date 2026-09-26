<?php

namespace AC\core\modules\reservations\config;

use AC\core\modules\config\models\ConfigClubStateModel;
use AC\core\modules\config\models\ConfigOpenTypeModel;
use AC\core\modules\config\scoped\traits\ScopedConfigConstFallbackTrait;
use AC\core\modules\config\scoped\traits\ScopedConfigScopeTrait;
use AC\core\system\config\BaseConfig;
use AC\core\system\helpers\JsonHelper;

class OpenTypeConfig extends BaseConfig
{
  use ScopedConfigScopeTrait;
  use ScopedConfigConstFallbackTrait;

  /** @var array<string, array{players:array,use:array}> */
  private static array $combinationsCache = [];

  public function buildOpenConfigTypeKey(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default',
  ): string {
    return $this->buildScopedTypeKey('open', $typeId, $sportId, $areaId, $fallbackTypeAlias);
  }

  public function getWhoCanPlayWithWhom(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default',
  ): array {
    $raw = $this->resolveOption(
      $this->buildScopedConfigQuery('who_can_play_with_whom', $typeId, $sportId, $areaId, $fallbackTypeAlias, 'open'),
      '',
    );

    if (is_array($raw)) {
      return $raw;
    }

    if (!is_string($raw) || $raw === '') {
      return [];
    }

    $decoded = JsonHelper::decode($raw, true);

    return is_array($decoded) ? $decoded : [];
  }

  /**
   * @return array{players:array,use:array}
   */
  public function getCombinationsOfPlayers(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default',
  ): array {
    [$typeId, $sportId, $areaId] = $this->resolveScopeIds($typeId, $sportId, $areaId);
    $cacheKey                    = $this->combinationsCacheKey($typeId, $sportId, $areaId);

    if (!isset(self::$combinationsCache[$cacheKey])) {
      self::$combinationsCache[$cacheKey] = $this->buildCombinationsMatrix(
        $typeId,
        $sportId,
        $areaId,
        $fallbackTypeAlias,
      );
    }

    return self::$combinationsCache[$cacheKey];
  }

  public function resetCombinationsCache(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
  ): void {
    if ($typeId === null && $sportId === null && $areaId === null) {
      self::$combinationsCache = [];

      return;
    }

    [$typeId, $sportId, $areaId] = $this->resolveScopeIds($typeId, $sportId, $areaId);
    unset(self::$combinationsCache[$this->combinationsCacheKey($typeId, $sportId, $areaId)]);
  }

  public function checkCombination(
    int $clubStateMainPlayer,
    int $clubStateOtherPlayer,
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default',
  ): bool {
    $use = $this->getCombinationsOfPlayers($typeId, $sportId, $areaId, $fallbackTypeAlias)['use'] ?? [];

    return (bool)($use[$clubStateMainPlayer][$clubStateOtherPlayer] ?? false);
  }

  /**
   * @return array{players:array,use:array}
   */
  private function buildCombinationsMatrix(
    int $typeId,
    int $sportId,
    int $areaId,
    string $fallbackTypeAlias,
  ): array {
    $use        = $this->getWhoCanPlayWithWhom($typeId, $sportId, $areaId, $fallbackTypeAlias);
    $clubStates = [];
    foreach (array_keys(ConfigClubStateModel::getMarks(true, 'mark') ?? []) as $mark) {
      foreach (ConfigClubStateModel::getMarks(true) ?? [] as $stateId => $state) {
        if ($mark == $state->mark) {
          $clubStates[$stateId] = $state;
        }
      }
    }

    $result = ['players' => $clubStates, 'use' => []];

    foreach ($clubStates as $clubState) {
      if (!isset($result['players'][$clubState->id]->use)) {
        $result['players'][$clubState->id]->use = 0;
      }
      foreach ($clubStates as $clubState2) {
        $check = $use === [] ? 1 : (isset($use[$clubState->id][$clubState2->id]) ? $use[$clubState->id][$clubState2->id] : 0);
        $result['use'][$clubState->id][$clubState2->id] = (int)$check;
        $result['players'][$clubState->id]->use         += $check;
      }
    }

    return $result;
  }

  private function combinationsCacheKey(int $typeId, int $sportId, int $areaId): string
  {
    return implode(':', [$typeId, $sportId, $areaId]);
  }

  public function buildOptionsConfigTypeKey(
    string $optionsType,
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default',
  ): string {
    $prefix = trim($optionsType);
    if (!str_starts_with($prefix, 'options_')) {
      $prefix = 'options_' . $prefix;
    }

    return $this->buildScopedTypeKey($prefix, $typeId, $sportId, $areaId, $fallbackTypeAlias);
  }

  public function isGuestAsNoMember(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $pricingSystemId = 'PFP',
    bool $default = false
  ): bool {
    return $this->getOptionBool('guest_as_no_member', $default, $typeId, $sportId, $areaId, $pricingSystemId);
  }

  public function isMainNoMemberPriceForNoMember(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $pricingSystemId = 'PFP',
    bool $default = false
  ): bool {
    return $this->getOptionBool('main_no_member_price_for_no_member', $default, $typeId, $sportId, $areaId, $pricingSystemId);
  }

  public function isDoubleCountOtherPlayersOnly(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $pricingSystemId = 'PFP',
    bool $default = false
  ): bool {
    return $this->getOptionBool('double_count_other_players_only', $default, $typeId, $sportId, $areaId, $pricingSystemId);
  }

  public function getOption(
    string $optionAlias,
    mixed $default = null,
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    ?string $pricingSystemId = null
  ): mixed {
    $pricingSystemId = $pricingSystemId !== null ? trim($pricingSystemId) : '';
    if ($pricingSystemId === '') {
      $pricingSystemId = (string)($this->getPricingSystemId($typeId, $sportId, $areaId) ?? 'PFP');
    }

    return $this->resolveOption(
      $this->buildScopedConfigQuery(
        $optionAlias,
        $typeId,
        $sportId,
        $areaId,
        'open',
        'options_' . $pricingSystemId,
        true,
      ),
      $default,
    );
  }

  public function getOptionBool(
    string $optionAlias,
    bool $default = false,
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    ?string $pricingSystemId = null
  ): bool {
    return (bool)$this->getOption($optionAlias, $default, $typeId, $sportId, $areaId, $pricingSystemId);
  }

  public function getPricingSystemId(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default',
    ?string $default = null
  ): ?string {
    $value = $this->resolveOption(
      $this->buildScopedConfigQuery('type_pricing_system', $typeId, $sportId, $areaId, $fallbackTypeAlias),
      $default,
    );
    if ($value === null || $value === '') {
      return null;
    }

    return (string)$value;
  }

  public function isPricingSystem(
    string $pricingAlias,
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default'
  ): bool {
    $pricingAlias = trim($pricingAlias);
    if ($pricingAlias === '') {
      return false;
    }

    $byAlias = ConfigOpenTypeModel::getTypePricingSystem('alias');
    if (!isset($byAlias[$pricingAlias])) {
      return false;
    }

    return $this->getPricingSystemId($typeId, $sportId, $areaId, $fallbackTypeAlias, null) === $byAlias[$pricingAlias]->id;
  }

  public function checkOpenTypeAsClose(
    ?int $typeId = null,
    ?int $sportId = null,
    ?int $areaId = null,
    string $fallbackTypeAlias = 'default'
  ): bool {
    return $this->isPricingSystem('as_close', $typeId, $sportId, $areaId, $fallbackTypeAlias);
  }
}
