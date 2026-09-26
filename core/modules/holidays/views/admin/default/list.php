<?php
/**
 * @var array  $holidays
 * @var bool   $hasHolidays
 * @var bool   $showSundayColumn
 * @var string $baseUrl
 * @var string $prefixUrl
 * @var array  $pagination
 * @var string $yearLinksHtml
 * @var string $year
 */
$check = '<i class="fas fa-check" style="color: green"></i>';
$ban   = '<i class="fa fa-ban fa-rotate-90" aria-hidden="true"  style="color: red"></i>';

?>
<?= $yearLinksHtml ?>
<?= $pagination ?>

<?php if ($hasHolidays): ?>
  <?php
  $tables_columns = [];
  $i              = 0;
  foreach ($holidays as $holiday) {
    $colIndex = (int)($i / 15);
    if (!isset($tables_columns[$colIndex])) {
      $tables_columns[$colIndex] = '';
    }
    $tables_columns[$colIndex] .= '<tr><td class="dark">' . date('d.m.Y', strtotime($holiday['date'])) . '</td>';
    if ($showSundayColumn) {
      $sundayPrices              = $holiday['sunday_prices'] == 1;
      $sundayTimes               = $holiday['sunday_times'] == 1;
      $tables_columns[$colIndex] .= '<td class="light" style="text-align:center">
        <a href="' . $baseUrl . '/activeSunday/sundayType/prices/state/' . ((int)!$sundayPrices) . '/holiday_id/' . $holiday['holiday_id'] . $prefixUrl . '" title="' . lang('Click to toggle') . '">' .
        ($sundayPrices ? $check : $ban) .
        '</a></td>';
      if (config('holidays')->useSundayTimes()) {
        $tables_columns[$colIndex] .= '<td class="light" style="text-align:center">
        <a href="' . $baseUrl . '/activeSunday/sundayType/times/state/' . ((int)!$sundayTimes) . '/holiday_id/' . $holiday['holiday_id'] . $prefixUrl . '" title="' . lang('Click to toggle') . '">' .
          ($sundayTimes ? $check : $ban) .
          '</a></td>';
      }
    }
    $tables_columns[$colIndex] .= '<td class="light"><a href="' . $baseUrl . '/removeHoliday/holiday_id/' . $holiday['holiday_id'] . $prefixUrl . '" onclick="return ifConfirm()" class="btnRemove">' . lang('button_remove') . '</a></td></tr>' . "\n";
    $i++;
  }
  $columnCount = count($tables_columns);
  ?>
  <table align="center" cellspacing="10">
    <tr>
      <?php foreach ($tables_columns as $column): ?>
        <td width="<?= $columnCount ? (100 / $columnCount) : 100 ?>%" valign="top">
          <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main">
            <tr>
              <th><?= lang('Date') ?></th>
              <?php if ($showSundayColumn): ?>
                <th><?= lang('Use Sunday Prices', 'holiday') ?></th>
                <?php if (config('holidays')->useSundayTimes()): ?>
                  <th><?= lang('Use opening hours on Sundays', 'holiday') ?></th>
                <?php endif; ?>
              <?php endif; ?>
              <th><?= lang('Action') ?></th>
            </tr>
            <?= $column ?>
          </table>
        </td>
      <?php endforeach; ?>
    </tr>
  </table>
<?php else: ?>
  <div style="margin: 10px auto 10px;width: 500px; text-align: center;">
    <?= lang('No holidays found for', 'holiday', ['year' => $year ?? date('Y')]) ?>
  </div>
<?php endif; ?>