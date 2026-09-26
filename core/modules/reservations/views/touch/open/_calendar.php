<?php
/**
 *  Переменные для календаря
 * @var object $date     - дата
 * @var int        $type_id  - тип корта
 * @var int        $sport_id - тип спорта
 * @var int        $area_id  - тип спорта
 * @var int        $page     - номер страницы
 * @var array      $months   - месяцы
 * @var array      $weeks    - недели
 * @var View       $this
 */

use AC\core\system\helpers\TranslateHelper;
use AC\core\system\view\View;


?>
<script>
  if(<?=Service::request()->_('click', 0)?>) {
    viewHideCalendar('view')
  }
</script>
<div class="separate"></div>
<table border="0" class="reservationsNavigationCalendar">
  <tr>
    <td>
      <div onclick="changeMonthBlank('back')"><img src="<?= base_url(paths()->getAssetsDir('images/left_arrow.png'))?>" alt=""/></div>
    </td>
    <td class="month-title">
      <div class="month-title-content" id="monthTitleСontent">
        <?php foreach ($months as $month) { ?>
          <div class="<?= $month->active ? 'sel' : '' ?>">
            <?php if (!$month->active) { ?>
            <a href="#"
               onclick="location.href='reservations.php?action=showReservations&type_id=<?= $type_id ?>&area_id=<?= $area_id ?>&sport_id=<?= $sport_id ?>&date=<?= $month->date ?>&page=<?= $page ?>&click=1'">
              <?php } ?>
            <?= $month->title . ', ' . $date->selected_year ?>
          <?php if (!$month->active) { ?>
            </a>
          <?php } ?>
          </div>
        <? } ?>
      </div>
    </td>
    <td style="text-align:right;">
      <div onclick="changeMonthBlank('forward')"><img src="<?= base_url(paths()->getAssetsDir('images/right_arrow.png'))?>" alt=""/></div>
    </td>
  </tr>
</table>
<table border="0" cellspacing="0" cellpadding="0" class="reservationsCalendar">
  <tr>
    <th><?= TranslateHelper::translateWeekday(0,true)?></th>
    <th><?= TranslateHelper::translateWeekday(1,true)?></th>
    <th><?= TranslateHelper::translateWeekday(2,true)?></th>
    <th><?= TranslateHelper::translateWeekday(3,true)?></th>
    <th><?= TranslateHelper::translateWeekday(4,true)?></th>
    <th><?= TranslateHelper::translateWeekday(5,true)?></th>
    <th><?= TranslateHelper::translateWeekday(6,true)?></th>
  </tr>
  <?php foreach ($weeks as $week) { ?>
    <tr>
      <?php foreach ($week as $day) {
        $class = ''; ?>
        <td>
          <?php if (isset($day->day)) {
            ?>
            <div <?= $day->class !== '' ? 'class="' . $day->class . '"' : '' ?>>
              <?php if ($day->class !== '' && $day->class != 'today') {
                echo $day->day;
              } else {
                $_date = $date->selected_year . '-' . $date->selected_month . '-' . $day->day;
                ?>
                <a href="#"
                   onclick="location.href='reservations.php?action=showReservations&type_id=<?= $type_id ?>&area_id=<?= $area_id ?>&sport_id=<?= $sport_id ?>&date=<?= $_date ?>&page=<?= $page ?>'">
                  <?= $day->day ?>
                </a>
              <?php } ?>
            </div>
          <?php } else { ?>
            <div class="otherMonth">&nbsp;</div>
          <?php } ?>
        </td>
      <?php } ?>
    </tr>
  <?php } ?>
</table>

