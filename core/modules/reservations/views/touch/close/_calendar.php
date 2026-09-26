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
<div id="shadow" style="display:none;"></div>
<div id="calendarLarge" class="reservationsCalendarLarge" style="display:none;">
  <table class="ob_calen">
    <tr>
      <td>
        <div id="today_head">
          <table style="width: 248px !important;margin: 5px 0;">
            <tr>
              <td rowspan="2">
          <span class="t_day">
            <?= $date->current_day ?>
          </span>
              </td>
              <td>
          <span class="t_week_d">
            <?= $date->current_week_day ?>
          </span>
              </td>
            </tr>
            <tr>
              <td>
          <span class="t_my">
            <?= $date->current_month . ', ' . $date->current_year ?>
          </span>
              </td>
            </tr>
          </table>
        </div>
        <header class="calendar">
          <?php foreach ($months as $month) { ?>
            <?php if ($month->active === true) { ?>
              <?php foreach ($month->actions as $action) { ?>
                <a href="#"
                   onclick="location.href='reservations.php?action=showReservations&type_id=<?= $type_id ?>&area_id=<?= $area_id ?>&sport_id=<?= $sport_id ?>&date=<?= $action->date ?>&page=<?= $page ?>&click=1'"
                   class="<?= $action->action ?>_month"></a>
              <?php } ?>
              <ul class="month">
                <li class="active">
                  <?= $month->title . ', ' . $date->selected_year ?>
                </li>
              </ul>
            <?php } ?>
          <? } ?>
          <ul class="week">
            <li><?= TranslateHelper::translateWeekday(0,true)?></li>
            <li><?= TranslateHelper::translateWeekday(1,true)?></li>
            <li><?= TranslateHelper::translateWeekday(2,true)?></li>
            <li><?= TranslateHelper::translateWeekday(3,true)?></li>
            <li><?= TranslateHelper::translateWeekday(4,true)?></li>
            <li><?= TranslateHelper::translateWeekday(5,true)?></li>
            <li><?= TranslateHelper::translateWeekday(6,true)?></li>
          </ul>
        </header>
        <div id="calendars_months">
          <ul class="calendar_days active">
            <?php foreach ($weeks as $week) {
              foreach ($week as $day) {
                $class = '';
                if (isset($day->day)) {
                  ?>
                  <li <?= $day->class !== '' ? 'class="' . $day->class . '"' : '' ?>>
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
                  </li>
                <?php } else { ?>
                  <li class="otherMonth">&nbsp;</li>
                <?php }
              }
            } ?>
          </ul>
        </div>
      </td>
      <td>
        <div class="legend">
          <h3><?= lang('Explanation of the schedule', 'show_order')?>:</h3>
          <ul>
            <li style="background-image:url(<?= base_url(paths()->getAssetsDir('images/l1.png'))?>);"><?= lang('legend_calen2')?></li>
            <li style="background-image:url(<?= base_url(paths()->getAssetsDir('images/l2.png'))?>);"><?= lang('legend_calen3')?></li>
            <li style="background-image:url(<?= base_url(paths()->getAssetsDir('images/l3.png'))?>); padding-top: 0;height: 28px;"><?= lang('legend_calen4')?>
            </li>
            <li style="background-image:url(<?= base_url(paths()->getAssetsDir('images/l4.png'))?>);"><?= lang('legend_calen5')?></li>
          </ul>
        </div>
      </td>
    </tr>
  </table>
</div>
