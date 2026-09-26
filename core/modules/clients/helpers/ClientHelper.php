<?php

namespace AC\core\modules\clients\helpers;

class ClientHelper
{
  /**
   *  Сгенерировать имя игрока, по фамилии и имени
   *
   * @param string|null $name
   * @param string|null $surname
   * @param string|null $prefix - добавочный префикс
   *
   * @return string
   */
  public static function generatePlayerName(?string $name = null, ?string $surname = null, ?string $prefix = null): string
  {
    $player_name = [];
    if (!empty($surname)) {
      $player_name[] = $surname;
    }
    if (!empty($name)) {
      $player_name[] = $name;
    }
    $player_name = implode(' ', $player_name);
    if (empty($player_name)) {
      $player_name = lang('Guest_player');
    }
    
    return addslashes($player_name . ($prefix !== null ? $prefix : ''));
  }
}