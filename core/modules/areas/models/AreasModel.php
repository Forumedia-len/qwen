<?php

namespace AC\core\modules\areas\models;

use AC\core\system\helpers\CalendarHelper;
use AC\core\engines\AreasEngine;
use AC\core\modules\areas\tables\AreasTable;
use AC\core\modules\webIo\models\WebIoModel;
use Service;

/**
 * Class AreasModel
 * todo getAreaPrice сделать модели для всего связанного с площадками и сделать выборку сразу
 *  в модель areas поменять код везде где спрашивается об этом
 */
class AreasModel extends AreasTable
{
  protected      $baseGetFunction        = 'getAreaData';
  protected      $primary_key            = 'area_id';
  protected      $baseEngine             = 'AreasEngine';
  protected      $baseGetFunctionAllData = 'getAllAreasData';
  private static $relevant_sport_by_type;

  /**
   * @var AreasEngine
   */
  protected $engine;

  public static function relevantSportsByType(): array
  {
    if (self::$relevant_sport_by_type === null) {
      self::$relevant_sport_by_type = getEngine('areas', false)?->getSportsByType();
    }

    return self::$relevant_sport_by_type;
  }

  /** Получить вариации площадок по типу спорту и сезону (комбинации)
   *
   * @param $useSeason
   *
   * @return array
   */
  public function getSportByTypeBySeason($useSeason = false)
  {
    static $seasons = [];
    if (empty($seasons)) {
      $seasons = $this->getEngine()->getSeasons();
    }
    $useSeason = $useSeason ? (bool)Service::configDB('registration', 'use_season_in_choosing_type_sport') : $useSeason;
    $out       = [];
    foreach (self::relevantSportsByType() as $key => $value) {
      $seasonValue        = (array)$value;
      $seasonValue['key'] = $key;
      for ($i = 1; $i <= ($useSeason ? count($seasons) : 1); $i++) {
        $seasonKey = $key . ($useSeason ? '_' . $seasons[$i]['period_id'] : '');
        if ($useSeason) {
          $seasonValue['season_id']      = $seasons[$i]['period_id'];
          $seasonValue['season_title']   = $seasons[$i]['title'];
          $seasonValue['title']          = $value->title . ' - ' . $seasons[$i]['title'];
          $seasonValue['title_full']     = $value->title_full . ' - ' . $seasons[$i]['title'];
          $seasonValue['title_site_url'] = $value->title_site_url . ' - ' . $seasons[$i]['title'];
          $seasonValue['season_key']     = $seasonKey;
        }
        $out[$seasonKey] = (object)$seasonValue;
      }
    }

    return $out;
  }


  public function getAreaPricesByDateTime($date, $times)
  {
    $period_id = $this->engine->getPeriodByDate($date);
    $weekday   = CalendarHelper::getWeekdayByUnixtime(strtotime($date));
    $prices    = [];
    foreach ($this->engine->getAreaPricesByTimes($this->area_id, $times, $period_id, $weekday) as $value) {
      $prices[substr($value['start'], 0, -3)] = $value['price'];
    }

    return $prices;
  }

  public function getWebIoPriceAndState($date, $time)
  {
    $state = [];
    foreach (WebIoModel::getWebIoTypes() as $alias => $webIoState) {
      $state[$alias] = [
        'price'   => (float)$this->{$alias . '_price'},
        'on'      => (bool)$this->{$alias . '_on'},
        'default' => WebIoModel::issetWebIoStateByAreaIdWeekdayTime($this->area_id, $date, $webIoState['id'], $time),
      ];
    }

    return $state;
  }

  public static function selectSportUseType($type_id)
  {
    return getEngine('areas', false)?->selectSportsByType($type_id);
  }

  public static function selectPageUseTypeSport($type_id, $sport_id)
  {
    return getEngine('areas', false)->selectPageByTypeAndSports($type_id, $sport_id);
  }

  public static function selectActiveType($key = 'current_alias'): array
  {
    $result = [];
    foreach (getEngine('areas', false)?->selectActiveType() as $item) {
      $result[$item->{$key}] = $item;
    }

    return $result;
  }

  public function checkTypeIsActiveByAlias($alias): bool
  {
    $typesKeys = array_keys(self::selectActiveType());
    if (in_array($alias, $typesKeys)) {
      return true;
    }
    foreach ($typesKeys as $key) {
      if (str_starts_with( $key, $alias)) {
        return true;
      }
    }

    return false;
  }

  public static function getFirstActiveType($property = 'type_id')
  {
    return getEngine('areas', false)?->getFirstActiveType($property);
  }

  public static function generateWorkTime($type, $sport, $byTime = false, $second = true)
  {
    return getEngine('areas', false)?->getWorkTimeByTypeSport($type, $sport, $byTime, $second);
  }

}