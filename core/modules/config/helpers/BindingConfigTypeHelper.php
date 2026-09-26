<?php

namespace AC\core\modules\config\helpers;

use AC\app\services\DataService;
use AC\core\modules\areas\entities\dto\AreaTypeDto;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use Service;

/**
 * Единая точка разбора config type вида `prefix|alias_typeId_sportId_areaId`
 * и получения человекочитаемых подписей по типам/видам спорта.
 */
final class BindingConfigTypeHelper
{
  public static function isValidConfigType(string $type, string $prefix): bool
  {
    $type = trim($type);
    $prefix = trim($prefix);
    if ($type === '' || $prefix === '') {
      return false;
    }
    if ($type === $prefix) {
      return true;
    }
    if (!str_starts_with($type, $prefix . '|')) {
      return false;
    }

    $rest = substr($type, strlen($prefix) + 1);

    return $rest !== '' && preg_match('/^[A-Za-z0-9_]+$/', $rest) === 1;
  }

  /**
   * Опции привязки для форм: ключом сразу является итоговый `config.type`.
   *
   * @return array<string, string> `config.type` => title
   */
  public static function bindingOptionMap(string $prefix): array
  {
    static $all;
    if (isset($all[$prefix]) && is_array($all[$prefix])) {
      return $all[$prefix];
    }

    $all[$prefix] = [];
    if (($titles = self::typeSportMap()) !== []) {
      foreach (array_keys($titles) as $typeKey) {
        $all[$prefix][$prefix . (!empty($typeKey) ? '|' . $typeKey : '')] = $titles[$typeKey];
      }
    }

    return $all[$prefix];
  }

  /**
   * Человекочитаемая подпись scope для таблиц и форм.
   */
  public static function bindingLabelForConfigType(string $configType): string
  {
    [$prefix, $alias] = array_pad(explode('|', $configType), 2, null);
    $typeSportTitles = self::typeSportMap();

    return empty($alias) ? $typeSportTitles[''] : $typeSportTitles[$alias];
  }

  /**
   * @return array<int, AreaTypeDto>
   */
  private static function activeTypes(): array
  {
    static $types;
    if (is_array($types)) {
      return $types;
    }
    $types = ModCommHelper::get('areas', 'areasType/selectActiveTypes', [], 'data');

    return is_array($types) ? $types : [];
  }

  /**
   *
   * @return array
   */
  private static function typeSportMap(): array
  {
    static $all;
    if (!is_array($all) && empty($all)) {
      $all = [];
      $activeTypes = self::activeTypes();
      foreach ($activeTypes as $type) {
        if (count($activeTypes) > 1) {
          $all[$type->current_alias] = $type->getTitle();
        }
      }
      foreach (DataService::sportsByType() as $row) {
        if ($row->sport_count > 1) {
          $all[$row->type_alias_type_id . '_' . $row->sport_id] = $row->title_full;
        }
      }
      if (count($all) == 1) {
        $all = [];
      }
      $all[''] = lang('All');
    }

    return $all;
  }

  /**
   * @param string     $name
   * @param array|null $allowed
   *
   * @return string
   */
  public static function listTypesSports(
    string $name,
    ?array $allowed = null,
  ): string {

    return useLayout()->render(
      'select',
      [
        'name'   => $name,
        'values' => $allowed,
      ],
      'common'
    );
  }

  /**
   * Собирает ключи доступные:
   * - "{prefix}"                                  (применяется ко всем)
   * - "{prefix}|{alias}"                          (применяется ко всем type_id с таким alias)
   * - "{prefix}|{alias}_{type_id}"                (применяется ко всем sport_id этого type_id)
   * - "{prefix}|{alias}_{type_id}_{sport_id}"     (применяется к конкретному sport_id этого type_id)
   *
   * @param list<string> $usedConfigTypes type из таблицы config
   * @param string       $prefix          например "paypal"
   *
   * @return list<string> список ключей
   */
  public static function availableBindingConfigTypes(array $usedConfigTypes, string $prefix): array
  {
    $available = [];
    if (($allOptions = BindingConfigTypeHelper::bindingOptionMap($prefix)) !== []) {
      if (!in_array($prefix, $usedConfigTypes, true) && ($global = $allOptions[$prefix]) !== null) {
        unset($allOptions[$prefix]);
      }
      foreach (array_keys($allOptions) as $configType) {
        if (!in_array($configType, $usedConfigTypes, true)) {
          $available[$configType] = $allOptions[$configType];
        }
      }
      if ((empty($usedConfigTypes) || count($available) > 0) && !empty($global)) {
        $available = array_merge([$prefix => $global], $available);
      }
    }

    return $available;
  }

  public static function prioritiesByTypeKeyInConfig(?array $params = []): array
  {
    $priorities = [];
    if (!empty($params['typeKey'])) {
      $priorities[] = rawurldecode($params['typeKey']);
    }
    if (($areaId = (int)($params['area_id'] ?? Service::request()->_('area_id'))) && ($area = DataService::areas()[$areaId])) {
      $typeId = $area->typeId;
      $sportId = $area->sportId;
    } else {
      $typeId = (int)($params['type_id'] ?? Service::request()->_('type_id'));
      $sportId = (int)($params['sport_id'] ?? Service::request()->_('sport_id'));
    }
    if ($typeId && ($type = ModCommHelper::get('areas', 'areasType/getAreaType', ['type_id' => $typeId, 'asDto' => true], 'data'))) {
      if($areaId) {
        $priorities[] = $type->type_alias_type_id . '_' . $sportId . '_' . $areaId;
      }
      if($sportId) {
        $priorities[] = $type->type_alias_type_id . '_' . $sportId;
      }
      $priorities[] = $type->type_alias_type_id;
      $priorities[] = $type->alias;
    }

    return array_unique($priorities);
  }
}
