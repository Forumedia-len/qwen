<?php

namespace AC\core\modules\config\models;

use AC\core\engines\ClubStateEngine;
use AC\core\modules\config\entities\dto\ClubStateDto;
use AC\core\modules\config\tables\ConfigClubStateTable;

class ConfigClubStateModel extends ConfigClubStateTable
{
  protected $baseGetFunction        = 'getNdsById';
  protected $baseGetFunctionAllData = 'getExtra';
  protected $baseEngine             = 'ClubStateEngine';
  /**
   * @var ClubStateEngine
   */
  protected $engine;
  /**
   * @var array
   */
  private static array $clubStates = [];

  /**
   * @param bool   $addGuest
   * @param string $alias
   *
   * @return array<ClubStateDto>|null
   */
  public static function getMarks(bool $addGuest = false, string $alias = 'id'): array | null
  {
    $clubStates = self::getClubState();
    if (isset($clubStates[$alias])) {
      if (!$addGuest) {
        unset($clubStates[$alias][($alias == 'id' ? 0 : 'guest')]);
      }

      return $clubStates[$alias];
    }

    return null;
  }

  public static function getMark($mark): ?ClubStateDto
  {
    $clubStates = self::getClubState();
    if (isset($clubStates['mark'][$mark])) {
      return $clubStates['mark'][$mark];
    }

    return null;
  }

  private static function getClubState(): array
  {
    if (!self::$clubStates) {
      self::$clubStates['mark']['guest'] = self::$clubStates['id'][0] = ClubStateDto::fromArray([
        'title' => lang('guest', 'club_state'),
        'short' => lang('short_guest', 'club_state'),
        'mark'  => 'guest',
        'id'    => 0,
      ]);
      $club_state                        = ConfigClubStateModel::findAll(['active' => 1]);
      foreach ($club_state as $state) {
        self::$clubStates['id'][$state->id] = self::$clubStates['mark'][$state->mark] = ClubStateDto::fromArray([
          'title' => lang($state->mark, 'club_state', [], $state->title),
          'short' => lang('short_' . $state->mark, 'club_state', [], $state->title),
          'mark'  => $state->mark,
          'id'    => $state->id,
        ]);
      }
    }
    return self::$clubStates;
  }

  public static function getItemById($id, $alias = null)
  {
    $id   = $id == 'guest' ? 0 : $id;
    $item = self::getMarks(true)[$id];

    return $alias ? $item->$alias : $item;
  }
}