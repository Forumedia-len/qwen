<?php

namespace AC\core\modules\reservations\models;

use AC\core\modules\text\engines\TextEngine;
use AC\core\system\helpers\TimeHelper;

class OrdersModelMcArenaReservation extends OrdersModelReservation
{
  /** Максимальное количество бронирований
   * @var int|false
   */
  public $max_count_reservation = MC_ARENA_MANY_HOURS;

  public function proceed(&$error_code)
  {
    $content = parent::proceed($error_code);
    /** @var TextEngine $txt */
    $txt     = getEngine('text', false);
    $pay     = '';
    if ($txt?->getContent($row, 'pay') && !empty($row)) {
      $pay = $row['content'];
    }

    return (is_array($content) ? array_merge($content, array('pay' => $pay, 'date' => $this->date, 'times' => TimeHelper::getTimeByStartFinish($this->times, $this->areas->period))): array());
  }
}