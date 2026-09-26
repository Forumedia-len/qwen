<?php
/**
 *  Переменные для календаря
 * @var object $date     - дата
 * @var int    $type_id  - тип корта
 * @var int    $sport_id - тип спорта
 * @var int    $page     - номер страницы
 * @var array  $months   - месяцы
 * @var array  $weeks    - недели
 * @var bool   $is_bar   - showBar
 */

use AC\core\system\helpers\TranslateHelper;

?>
<table width="100%" cellspacing="0" cellpadding="8">
  <tr>
    <td>
      <form name="navigation" action="-">
        <table width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td>
              <select name="item"
                <?php if (!$is_bar): ?>
                  onchange="window.location = 'reservations.php?action=showReservations&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>&date=' + document.forms['navigation'].item.value+'&page=<?= $page ?>';return false;"
                <?php else: ?>

                  onchange="window.location = 'reservations.php?action=showBar&date=' + document.forms['navigation'].item.value;return false;"

                <?php endif ?>

                      style="width: 100%">

                <?php
                foreach ($months as $month) {
                  $style = '';
                  if ($month->year == $date->current_year && $month->month == date('m')) {
                    $style = 'background:#5588D4;color:#FFFFFF;';
                  } elseif ($month->year == $date->selected_year && $month->month == $date->selected_month) {
                    $style = 'background:#DDDDDD;';
                  }
                  $day = '01';
                  if ($month->year == $date->current_year && $month->month == date('m')) {
                    $day = date('d');
                  }
                  ?>
                  <option value="<?= $month->year . '-' . $month->month . '-' . $day ?>" style="<?= $style ?>" <?= $month->active ? 'selected'
                    : '' ?>>
                    <?= $month->title . ' ' . $month->year ?>
                  </option>
                <?php } ?>
              </select>
            </td>
            <!--td align="right">
              <input type="submit" value="OK" class="button"
                     onclick="window.location = 'reservations.php?action=showReservations&type_id=<?= $type_id ?>//&date=' + document.forms['navigation'].item.value+'&page=<?= $page ?>//';return false;"/>
//            </td-->
          </tr>
        </table>
      </form>
    </td>
  </tr>
  <tr>
    <td>
      <table border="0" cellspacing="1" cellpadding="0" class="calendar">
        <tr>
          <th bgcolor="#D5F0F0"><?= TranslateHelper::translateWeekday(0, true) ?></th>
          <th bgcolor="#F5F8F8"><?= TranslateHelper::translateWeekday(1, true) ?></th>
          <th bgcolor="#D5F0F0"><?= TranslateHelper::translateWeekday(2, true) ?></th>
          <th bgcolor="#F5F8F8"><?= TranslateHelper::translateWeekday(3, true) ?></th>
          <th bgcolor="#D5F0F0"><?= TranslateHelper::translateWeekday(4, true) ?></th>
          <th bgcolor="#97E1E1"><?= TranslateHelper::translateWeekday(5, true) ?></th>
          <th bgcolor="#71D5D5"><?= TranslateHelper::translateWeekday(6, true) ?></th>
        </tr>
        <?php foreach ($weeks as $week) { ?>
          <tr>
            <?php foreach ($week as $day) {
              $class = '';
              if (isset($day->day)) {
                ?>
                <td <?= $day->class !== '' ? 'class="' . $day->class . '"' : '' ?>>
                  <?php if ($day->class == 'selected') { ?>
                    <?= $day->day ?>
                  <?php } else { ?>
                  <?php $_date = $date->selected_year . '-' . $date->selected_month . '-' . $day->day; ?>
                  <?php if (!$is_bar): ?>
                  <a
                    href="reservations.php?action=showReservations&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>&date=<?= $_date ?>&page=<?= $page ?>">
                    <?php else: ?>
                    <a href="reservations.php?action=showBar&date=<?= $_date ?>">
                      <?php endif ?>
                      <?= $day->day ?>
                    </a>
                    <?php } ?>
                </td>
              <?php } else { ?>
                <td class="otherMonth">&nbsp;</td>
              <?php }
            } ?>
          </tr>
        <?php } ?>
      </table>
    </td>
  </tr>
</table>
