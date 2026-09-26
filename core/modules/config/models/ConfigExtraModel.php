<?php

namespace AC\core\modules\config\models;

use AC\core\engines\ExtraEngine;
use AC\core\modules\areas\models\AreasModel;
use AC\core\modules\config\tables\ConfigExtraTable;


/**
 * Class ConfigExtraModel
 * @property $weekPrices
 */
class ConfigExtraModel extends ConfigExtraTable
{
//  protected $baseGetFunction        = 'getNdsById';
  protected $baseGetFunctionAllData = 'getExtra';
  protected $primary_key            = 'extra_id';
  protected $baseEngine             = 'ExtraEngine';
  /**
   * @var ExtraEngine
   */
  protected $engine;

  public static $club_states;
  public        $club_rate;
  public        $club_rate2;
  public        $no_club_rate;

  public function __construct($id = null)
  {
    parent::__construct($id);
    if (self::$club_states === null) {
      $club_states = ConfigClubStateModel::findAll(array('active' => 1));
      /** @var $state ConfigClubStateModel */
      foreach ($club_states as $state) {
        $state->title = lang($state->mark, 'club_state', array(), $state->title);
        self::$club_states[$state->{$state->getPrimaryKey()}] = $state;
      }
    }
    if ($this->use_time) {
      $this->weekPrices = $this->generateWeekPricesByWorkTime();
    }
  }

  public function getExtraByTypeAndSport($type, $sport)
  {
    if ($type && $sport) {
      return $this->engine->getExtraByTypeAndSport($type, $sport);
    }

    return false;
  }

  public function rules()
  {
    return array(
      array(array('rate', 'club_state', 'area_type_id', 'area_sport_id', 'use_time'), 'required'),
      array(array('club_state', 'area_type_id', 'area_sport_id'), 'integer'),
      array('rate', 'float'),
      array('use_time', 'bool')
    );
  }

  public function attributes($reflection = false): array
  {
    $club_state = ConfigClubStateModel::getMarks();
    $attributes = parent::attributes($reflection);
    foreach ($club_state as $state) {
      unset($attributes[array_search($state->mark, $attributes)]);
    }

    return $attributes;
  }

  public function attributeLabel(?string $locale = null)
  {
    $label         = parent::attributeLabel($locale);
    $label['rate'] = isset(self::$club_states[$this->club_state]) ? self::$club_states[$this->club_state]->title : $label['rate'];

    return $label;
  }

  public static function getClubStates()
  {
    if (self::$club_states === null) {
      $club_states = ConfigClubStateModel::findAll(array('active' => 1));
      /** @var $state ConfigClubStateModel */
      foreach ($club_states as $state) {
        $state->title = lang($state->mark, 'club_state', [], $state->title);
        self::$club_states[$state->{$state->getPrimaryKey()}] = $state;
      }
    }

    return self::$club_states;
  }

  static public function getExtraPriceWeek($extra_id)
  {
    $extra = new ConfigExtraModel($extra_id);

    return $extra->generateWeekPricesByWorkTime();
  }

  public function generateWeekPricesByWorkTime()
  {
    $result    = array();
    $extraWeek = $this->engine->getExtraWeek($this->extra_id);
    foreach (AreasModel::generateWorkTime($this->area_type_id, $this->area_sport_id) as $weekday => $times) {
      foreach ($times as $time => $title_time) {
        $result[$weekday][$time] = isset($extraWeek[$weekday][$time]) ? $extraWeek[$weekday][$time] : 0;
      }
    }


    return $result;
  }

  public function updateRowWeekPrice($weekday, $time, $price)
  {
    $this->engine->updateRowExtraWeek($this->extra_id, $weekday, $time, $price);
  }
}