<?php

namespace AC\core\modules\reservations\views;

use AC\core\system\view\Page;

/** Параметры страницы расписания и оформления бронирования. */
class ReservationView extends Page
{
  /** Календарь навигации нужен только при выводе расписания. */
  public bool $showCalendar = false;
}
