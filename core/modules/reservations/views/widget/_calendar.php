<?php
/** Заголовок и календарь widget используют выбранные тип и спорт из общего движка сайта. */

use AC\core\system\helpers\ConfigHelper;
use AC\core\system\helpers\TranslateHelper;
use AC\core\system\helpers\StringHelper;

$escape = static fn($value): string => StringHelper::shield((string)$value);
$bookingTitle = Service::engines()->areas->getTitleByTypeAndSport($type_id, $sport_id, 'title_full');
$calendarUrl = static function (string $selectedDate, bool $keepOpen = false) use ($type_id, $sport_id, $area_id, $page, $week_id): string {
  return site_url('reservations.php') . '?' . http_build_query([
    'action' => 'showReservations', 'type_id' => $type_id, 'sport_id' => $sport_id,
    'area_id' => $area_id, 'date' => $selectedDate, 'page' => $page, 'week' => $week_id,
    'calendar' => $keepOpen ? 1 : 0,
  ]);
};
$selected = strtotime($date->selected_date);
$previous = date('Y-m-d', strtotime('-1 day', $selected));
$next = date('Y-m-d', strtotime('+1 day', $selected));
$extraDays = PERIOD_SHOW_CALENDAR ? ConfigHelper::parseStringToVariables(PERIOD_SHOW_CALENDAR, 'PERIOD_SHOW_CALENDAR') : [];
$canNext = Service::engines()->checkDateAvaliableByUnixtime(strtotime($next))
  || ConfigHelper::checkDateInInterval(date('Y-m-d'), (int)($extraDays["{$type_id}_{$sport_id}"] ?? 0), $next);
$expanded = in_array(Service::request()->_('calendar'), ['1', 1], true);
$iconPath = static fn(string $direction): string => Service::autoloader()->getPathFile(
  paths()->getTplDir('images/date-' . $direction . '.svg', 'widget'), 'svg'
);
?>
<h2 class="fm-booking-title"><?= StringHelper::shield((string)$bookingTitle, doubleEncode: false) ?></h2>
<div class="fm-date-picker">
  <div class="fm-date-control">
    <a class="fm-date-arrow" <?= $previous >= date('Y-m-d') ? 'href="' . $escape($calendarUrl($previous)) . '"' : 'aria-disabled="true"' ?>
       aria-label="<?= lang('Previous day', 'widget') ?>"><?php include $iconPath('prev'); ?></a>
    <a href="#fm-calendar" class="fm-date-toggle" id="tablet-calendar-button-id"
       aria-expanded="<?= $expanded ? 'true' : 'false' ?>" aria-controls="fm-calendar">
      <?= (int)$date->selected_day ?>. <?= TranslateHelper::translateMonth($date->selected_month, true) ?> <?= $date->selected_year ?>
      <?php include $iconPath('down'); ?>
    </a>
    <a class="fm-date-arrow" <?= $canNext ? 'href="' . $escape($calendarUrl($next)) . '"' : 'aria-disabled="true"' ?>
       aria-label="<?= lang('Next day', 'widget') ?>"><?php include $iconPath('next'); ?></a>
  </div>
  <section class="fm-calendar-panel<?= $expanded ? ' fm-open' : '' ?>" id="fm-calendar"
           aria-label="<?= lang('calendar') ?>" aria-hidden="<?= $expanded ? 'false' : 'true' ?>" <?= $expanded ? '' : 'inert' ?>>
    <div class="fm-calendar-body">
      <nav class="fm-calendar-month">
        <?php foreach ($months as $month) {
          if (!$month->active) { continue; }
          $monthActions = array_column($month->actions, 'date', 'action');
          ?>
          <a <?= isset($monthActions['prev']) ? 'href="' . $escape($calendarUrl($monthActions['prev'], true)) . '"' : 'aria-disabled="true"' ?>
             aria-label="<?= lang('Previous month', 'widget') ?>"><?php include $iconPath('prev'); ?></a>
          <span><?= $month->title ?>, <?= $date->selected_year ?></span>
          <a <?= isset($monthActions['next']) ? 'href="' . $escape($calendarUrl($monthActions['next'], true)) . '"' : 'aria-disabled="true"' ?>
             aria-label="<?= lang('Next month', 'widget') ?>"><?php include $iconPath('next'); ?></a>
        <?php } ?>
      </nav>
      <div class="fm-calendar-week">
        <?php for ($weekday = 0; $weekday < 7; $weekday++) { ?>
          <span><?= TranslateHelper::translateWeekday($weekday, true) ?></span>
        <?php } ?>
      </div>
      <div class="fm-calendar-days">
        <?php foreach ($weeks as $calendarWeek) { foreach ($calendarWeek as $day) {
          $dayClass = isset($day->day) ? $day->class : 'other-month';
          ?>
          <div class="fm-calendar-cell fm-<?= $dayClass ?: 'available' ?>">
            <?php if (isset($day->day)) {
              if ($day->class === '' || $day->class === 'today') { ?>
                <a href="<?= $escape($calendarUrl($date->selected_year . '-' . $date->selected_month . '-' . $day->day)) ?>"><?= $day->day ?></a>
              <?php } else { ?>
                <span <?= $day->class === 'select_day' ? 'aria-current="date"' : '' ?>><?= $day->day ?></span>
              <?php }
            } ?>
          </div>
        <?php } } ?>
      </div>
      <section class="fm-calendar-legend">
        <h2><?= lang('Explanation of the schedule', 'show_order') ?>:</h2>
        <?php foreach (['today' => 2, 'select_day' => 3, 'locked' => 4, 'holiday' => 5] as $state => $legend) { ?>
          <div><span class="fm-calendar-sample fm-<?= $state ?>">00</span><?= lang('legend_calen' . $legend) ?></div>
        <?php } ?>
      </section>
    </div>
  </section>
</div>
