<?php

namespace AC\core\modules\areas\controllers\modComm;

use AC\core\engines\AreasEngine;
use AC\core\engines\SportsEngine;
use AC\core\modules\areas\entities\dto\AreaDto;
use AC\core\system\modules\modComm\controllers\ModCommController;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;
use Service;

class AreasController extends ModCommController
{
  /**
   *
   * @param ModCommRequest $request
   *
   * @return ModCommResponse
   */
  public function relevantSportsByType(ModCommRequest $request): ModCommResponse
  {
    if ($useSeason = $request->getDataValue('useSeason', false)) {
      $useSeason = (bool)Service::configDB('registration', 'use_season_in_choosing_type_sport');
    }
    $sportsByType        = $this->sportsByType();
    $sportsByTypeSeasons = [];
    if ($useSeason) {
      $seasons = $this->areasEngine()->getSeasons();
      foreach ($sportsByType as $key => $value) {
        $seasonValue = clone $value;
        for ($i = 1; $i <= count($seasons); $i++) {
          $seasonKey                       = $key . '_' . $seasons[$i]['period_id'];
          $seasonValue->season_id          = $seasons[$i]['period_id'];
          $seasonValue->season_title       = $seasons[$i]['title'];
          $seasonValue->title              = $value->title . ' - ' . trim($seasons[$i]['title']);
          $seasonValue->title_full         = $value->title_full . ' - ' . trim($seasons[$i]['title']);
          $seasonValue->title_site_url     = $value->title_site_url . ' - ' . trim($seasons[$i]['title']);
          $seasonValue->season_key         = $seasonKey;
          $sportsByTypeSeasons[$seasonKey] = $seasonValue;
        }
      }
    }
    return ModCommHelper::success(['sports_by_type' => $useSeason ? $sportsByTypeSeasons : $sportsByType]);
  }

  public function relevantSportsByTypeAsSelect(ModCommRequest $request): ModCommResponse
  {
    $multiple = (bool)$request->getDataValue('multiple', false);
    $values   = array_map(static fn($item) => $item->title, $this->sportsByType());
    if ($request->getDataValue('all_sports', false)) {
      $values = array_merge(['' => lang('all_choice', 'areas')], $values);
    }

    return ModCommHelper::success([
      'sports_by_type' => useLayout()::render('select', [
        'name'     => $request->getDataValue('name', 'type_sport') . ($multiple ? '[]' : ''),
        'values'   => $values,
        'class'    => $request->getDataValue('class', 'wide'),
        'multiple' => $multiple,
        'size'     => $multiple ? 5 : null,
        'current'  => $request->getDataValue('current', null),
      ], 'common'),
    ]);
  }

  public function getAreas(ModCommRequest $request): ModCommResponse
  {
    $areas_data = $this->areasEngine()?->getAreasFullData(
      array_merge([
        'key'      => 'area_id',
        'as'       => 'dto',
        'dtoClass' => AreaDto::class,
        'prices'   => true,
      ], $request->getData()));

    return ModCommHelper::success(['areas' => $areas_data ?? []]);
  }

  public function getAreaDataById(ModCommRequest $request): ModCommResponse
  {
    $area_data = [];
    if (($areaId = $request->getDataValue('area_id'))
      && $this->areasEngine()?->getAreaData($areaId, $area_data)) {
      $typeSport = $this->sportsByType()[$area_data['type_id'] . '_' . $area_data['sport_id']];

      $area_data['type']  = [
        'type_id'       => $typeSport->type_id,
        'title'         => $typeSport->type_title,
        'alias'         => $typeSport->type_alias,
        'active'        => $typeSport->type_active,
        'color'         => $typeSport->type_color,
        'sort'          => $typeSport->type_sort,
        'comments'      => $typeSport->type_comments,
        'alias_type_id' => $typeSport->type_alias_type_id,
        'count'         => $typeSport->type_count,
        'count_alias'   => $typeSport->type_count_alias,
      ];
      $area_data['sport'] = [
        'sport_id'       => $typeSport->sport_id,
        'title'          => $typeSport->sport_title,
        'alias'          => $typeSport->sport_alias,
        'sort'           => $typeSport->sport_sort,
        'comment'        => $typeSport->sport_comment,
        'count'          => $typeSport->sport_count,
        'alias_sport_id' => $typeSport->sport_alias . '_' . $typeSport->sport_id,
      ];

      return ModCommHelper::success(['area' => $area_data]);
    }

    return ModCommHelper::error();
  }

  public function getSeasonByDate(ModCommRequest $request): ModCommResponse
  {
    $date    = $request->getDataValue('date', date('Y-m-d'));
    $seasons = $request->getDataValue('seasons', []);

    return ModCommHelper::success(['season' => $this->areasEngine()?->getPeriodByDate($date, $seasons)]);
  }

  private function sportsByType(): array
  {
    static $relevant_sport_by_type;

    if (empty($relevant_sport_by_type)) {
      $relevant_sport_by_type = $this->areasEngine()?->getSportsByType();
    }

    return $relevant_sport_by_type;
  }

  private function areasEngine(): AreasEngine
  {
    return getEngine('areas', false);
  }

  private function areasSportsEngine(): SportsEngine
  {
    return getEngine('sports', false);
  }
}