<?php

namespace AC\core\modules\config\models;


use AC\core\modules\areas\entities\dto\AreaTypeDto;
use AC\core\system\helpers\JsonHelper;
use AC\core\system\helpers\ObjectHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use Service;


class ConfigOpenTypeModel extends ConfigModel
{
  private ?ConfigOpenTypeDoubleGameModel $doubleGameModel = null;

  /**
   * @return array<int, AreaTypeDto>
   */
  public function getActiveOpenAreaTypes(): array
  {
    $types = ModCommHelper::get('areas', 'areasType/selectActiveTypes', [], 'data');
    if (!is_array($types)) {
      return [];
    }

    $open = [];
    foreach ($types as $type) {
      if (!$type instanceof AreaTypeDto || $type->getAlias() !== 'open') {
        continue;
      }

      $typeId = (int)($type->type_id ?? $type->getId() ?? 0);
      if ($typeId > 0) {
        $open[$typeId] = $type;
      }
    }
    ksort($open);

    return $open;
  }

  public function resolveAdminTypeId(): int
  {
    $types = $this->getActiveOpenAreaTypes();
    if ($types === []) {
      return 0;
    }

    $requested = (int)Service::request()->_('type_id', 0);
    if ($requested > 0 && isset($types[$requested])) {
      return $requested;
    }

    return (int)array_key_first($types);
  }

  /**
   * @return array<string, array{href:string,title:string,active:bool}>
   */
  public function buildAdminContentMenuItems(): array
  {
    $types = $this->getActiveOpenAreaTypes();
    if (count($types) <= 1) {
      return [];
    }

    $current = $this->resolveAdminTypeId();
    $items   = [];
    foreach ($types as $typeId => $type) {
      $title = trim((string)($type->title ?? ''));
      if ($title === '') {
        $title = trim($type->getCurrentAlias());
      }
      if ($title === '') {
        $title = 'open ' . $typeId;
      }

      $items['type_' . $typeId] = [
        'href'   => 'config.php?mode=open_type&type_id=' . $typeId,
        'title'  => $title,
        'active' => $typeId === $current,
      ];
    }

    return $items;
  }

  public function getOpenConfigTypeKey(int $typeId, ?int $sportId = null, ?int $areaId = null): string
  {
    return config('OpenType')->buildOpenConfigTypeKey(
      $typeId > 0 ? $typeId : null,
      $sportId ?? 0,
      $areaId ?? 0,
    );
  }

  public function getOptionsConfigTypeKey(string $optionsType, int $typeId, ?int $sportId = null, ?int $areaId = null): string
  {
    return config('OpenType')->buildOptionsConfigTypeKey(
      $optionsType,
      $typeId > 0 ? $typeId : null,
      $sportId ?? 0,
      $areaId ?? 0,
    );
  }

  public function getAdminOpenModel(?int $typeId = null): object
  {
    $typeId                     = $typeId ?? $this->resolveAdminTypeId();
    $openType                   = config('OpenType');
    $model                      = ObjectHelper::createObject();
    $model->type_pricing_system = $openType->getPricingSystemId(
      $typeId > 0 ? $typeId : null,
      null,
      null,
      'default',
      'ST',
    ) ?? 'ST';

    return $model;
  }

  public function getAdminOptionsModel(int $typeId, string $pricingSystemId): object
  {
    $openType                                  = config('OpenType');
    $scopeId                                   = $typeId > 0 ? $typeId : null;
    $model                                     = ObjectHelper::createObject();
    $model->guest_as_no_member                 = $openType->isGuestAsNoMember($scopeId, null, null, $pricingSystemId) ? 1 : 0;
    $model->main_no_member_price_for_no_member = $openType->isMainNoMemberPriceForNoMember(
      $scopeId,
      null,
      null,
      $pricingSystemId,
    ) ? 1 : 0;
    $model->double_count_other_players_only    = $openType->isDoubleCountOtherPlayersOnly(
      $scopeId,
      null,
      null,
      $pricingSystemId,
    ) ? 1 : 0;

    return $model;
  }

  public function saveTypePricingSystem(int $typeId, string $pricingSystemId): bool
  {
    $pricingSystemId = trim($pricingSystemId);
    if ($pricingSystemId === '') {
      return false;
    }
    return $this->engine->saveItem('type_pricing_system', $pricingSystemId, $this->getOpenConfigTypeKey($typeId));
  }

  public function saveOptionsSystemPrice($type, $data, ?int $typeId = null)
  {
    $typeId = $typeId ?? $this->resolveAdminTypeId();
    if (!is_array($data)) {
      $data = [];
    }

    $typeKey = $this->getOptionsConfigTypeKey((string)$type, $typeId);
    $fields  = [
      'guest_as_no_member'                 => isset($data['guest_as_no_member']) && $data['guest_as_no_member'] ? '1' : '0',
      'main_no_member_price_for_no_member' => isset($data['main_no_member_price_for_no_member']) && $data['main_no_member_price_for_no_member'] ? '1' : '0',
      'double_count_other_players_only'    => isset($data['double_count_other_players_only']) && $data['double_count_other_players_only'] ? '1' : '0',
    ];

    foreach ($fields as $alias => $value) {
      if (!$this->engine->saveItem($alias, $value, $typeKey)) {
        return false;
      }
    }

    return true;
  }

  private function doubleGameModel(): ConfigOpenTypeDoubleGameModel
  {
    return $this->doubleGameModel ??= new ConfigOpenTypeDoubleGameModel($this->engine);
  }

  /**
   * @return list<array{alias:string,label:string,input:string,value:mixed}>
   */
  public function buildAdminDoubleFields(?int $typeId = null): array
  {
    $typeId  = $typeId ?? $this->resolveAdminTypeId();
    $scopeId = $typeId > 0 ? $typeId : null;

    return $this->doubleGameModel()->buildAdminFields($scopeId);
  }

  public function getDoubleConfigTypeKey(int $typeId): string
  {
    return $this->doubleGameModel()->getConfigTypeKey($typeId, 0, 0);
  }

  public function saveDoubleGame(int $typeId, array $data): bool
  {
    return $this->doubleGameModel()->save($typeId, $data, 0, 0);
  }

  public function getCombinationsOfPlayers($reset = false, ?int $typeId = null, ?int $sportId = null, ?int $areaId = null)
  {
    $typeId = $typeId ?? $this->resolveAdminTypeId();
    $openType = config('OpenType');
    if ($reset) {
      $openType->resetCombinationsCache($typeId > 0 ? $typeId : null, $sportId ?? 0, $areaId ?? 0);
    }

    return $openType->getCombinationsOfPlayers($typeId > 0 ? $typeId : null, $sportId ?? 0, $areaId ?? 0);
  }

  public function setCombinationsOfPlayers($combinations, ?int $typeId = null, ?int $sportId = null, ?int $areaId = null)
  {
    $typeId = $typeId ?? $this->resolveAdminTypeId();
    if (!is_array($combinations)) {
      $combinations = [];
    }

    $combinations = JsonHelper::encode($combinations);
    if ($this->engine->saveItem(
      'who_can_play_with_whom',
      $combinations,
      $this->getOpenConfigTypeKey($typeId, $sportId ?? 0, $areaId ?? 0),
    )) {
      config('OpenType')->resetCombinationsCache($typeId > 0 ? $typeId : null, $sportId ?? 0, $areaId ?? 0);

      return true;
    }

    return false;
  }

  public static function getTypePricingSystem($byAlias = 'id')
  {
    $return = [];

    foreach (
      [
        [
          'alias'       => 'standard',
          'id'          => 'ST',
          'title'       => lang('title_type_pricing_system_st', 'config_open_type'),
          'description' => lang('description_type_pricing_system_st', 'config_open_type'),
        ],
        [
          'alias'       => 'standard_for_players',
          'id'          => 'STP',
          'title'       => lang('title_type_pricing_system_stp', 'config_open_type'),
          'description' => lang('description_type_pricing_system_stp', 'config_open_type'),
        ],
        [
          'alias'       => 'guest',
          'id'          => 'GT',
          'title'       => lang('title_type_pricing_system_gt', 'config_open_type'),
          'description' => lang('description_type_pricing_system_gt', 'config_open_type'),
        ],
        [
          'alias'       => 'price_for_player',
          'id'          => 'PFP',
          'title'       => lang('title_type_pricing_system_pfp', 'config_open_type'),
          'description' => lang('description_type_pricing_system_pfp', 'config_open_type'),
        ],
        [
          'alias'       => 'as_close',
          'id'          => 'PV',
          'title'       => lang('title_type_pricing_system_ac', 'config_open_type'),
          'description' => lang('description_type_pricing_system_ac', 'config_open_type'),
        ],
      ] as $typePrice
    ) {
      $return[$typePrice[$byAlias]] = (object)$typePrice;
    }

    return $return;
  }

  public function rules()
  {
    return [
      [
        [
          'guest_as_no_member',
          'main_no_member_price_for_no_member',
          'double_count_other_players_only',
        ],
        'bool',
      ],
    ];
  }


}
