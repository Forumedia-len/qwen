<?php
/**
 * @var int    $year
 * @var array  $yearLinksHtml
 * @var array  $holidaysInSelectedYear
 * @var string $calendarUrl
 * @var bool   $useUpdate
 */

use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\TranslateHelper;

$weekday = date('w', strtotime($year . '-1-1'));
if ($weekday == 0) {
  $weekday = 7;
}

$monthCells = [];
for ($month = 1; $month <= 12; $month++) {
  $tbl = '<table border="0" cellspacing="2" cellpadding="1" class="month">' . "\n"
    . '<tr><th colspan="7">' . TranslateHelper::translateMonth($month) . ' - ' . $year . '</th></tr>' . "\n";

  if ($weekday != 1) {
    $tbl .= '<tr>' . "\n";
    for ($i = 1; $i < $weekday; $i++) {
      $tbl .= '<td class="w' . $i . '">&nbsp;</td>' . "\n";
    }
  }

  for ($day = 1; $day <= CalendarHelper::getDaysInMonth($year, $month); $day++) {
    if ($weekday == 1) {
      $tbl .= '<tr>' . "\n";
    }

    $value = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $tbl   .= '<td onclick="s (this)" class="w' . $weekday . '" id="' . $value . '">' . $day . '</td>' . "\n";

    if ($weekday == 7) {
      $tbl     .= '</tr>' . "\n";
      $weekday = 1;
    } else {
      $weekday++;
    }
  }

  if ($weekday != 1) {
    for ($i = $weekday; $i <= 7; $i++) {
      $tbl .= '<td class="w' . $i . '">&nbsp;</td>' . "\n";
    }
    $tbl .= '</tr>' . "\n";
  }

  $tbl          .= '</table>' . "\n";
  $monthCells[] = '<td valign="top" width="25%">' . "\n" . $tbl . '</td>' . "\n";
}

$monthsRow = '';
foreach ($monthCells as $index => $item) {
  $month = $index + 1;
  if ($month == 1) {
    $monthsRow .= '<tr>' . "\n" . $item;
  } elseif (($month - 1) % 4 == 0) {
    $monthsRow .= '</tr>' . "\n" . '<tr>' . $item . "\n";
  } else {
    $monthsRow .= $item;
  }
}
$monthsRow .= '</tr>' . "\n";

$holidaysJson = count($holidaysInSelectedYear) > 0
  ? 'selected_days_original = new Array ("' . join('", "', $holidaysInSelectedYear) . '");'
  : 'selected_days_original = new Array ();';
?>
<form action="<?= $calendarUrl . '/updateCalendar' ?>" onsubmit="<?php if ($useUpdate){?>holidaysCalendarSubmit(<?= $year ?>, this)<?php } else { echo 'return false;';} ?>" onreset="holidaysCalendarReset()"
      method="post">
  <input type="hidden" name="holidays" value="">
  <input type="hidden" name="year" value="">
  <div align="center">
    <table border="0" class="holidaysCalendar" cellspacing="12" cellpadding="0">
      <tr>
        <th colspan="4"><?= $yearLinksHtml ?></th>
      </tr>
      <?= $monthsRow ?>
      <tr>
        <th colspan="4"><?= $yearLinksHtml ?></th>
      </tr>
    </table>
    <?php if ($useUpdate): ?>
          <input type='submit' value="<?= lang('button_update') ?>" class='button'/>
          <input type='reset' value="<?= lang('button_reset') ?>" class='button'/>
    <?php endif; ?>
  </div>
</form>
<script>
  <?= $holidaysJson ?>

  selected_days = new Array()
  restoreOriginalSelectedDays()
  drawSelectedDays()
</script>
