<?php
/**
 *  Переменные для календаря
 * @var stdClass   $date     - дата
 * @var int        $type_id  - тип корта
 * @var int        $sport_id - тип спорта
 * @var int        $area_id  - номер площадки
 * @var int        $page     - номер страницы
 * @var array      $months   - месяцы
 * @var array      $weeks    - недели
 * @var int        $week_id  - какую таблицуу показывать недельную или колоночную
 */

use AC\core\system\helpers\TranslateHelper;

?>
<div class="calendar-wrapper visible-md-block visible-lg-block">
  <div class="button-close calendar-close-button"></div>
  <div class="shadow"></div>
  <div class="calendar-block">
    <div id="calendar">
      <div id="today_head">
        <table>
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
              <a href="<?= site_url() ?>reservations.php?action=showReservations&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>&area_id=<?= $area_id ?>&date=<?= $action->date ?>&page=<?= $page ?>&week=<?= $week_id ?>"
                 class="<?= $action->action ?>_month"></a>
            <?php } ?>
            <ul class="month">
              <li class="active">
                <?= $month->title . ', ' . $date->selected_year ?>
              </li>
            </ul>
          <?php } ?>
        <?php } ?>
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
                    <a href="<?= site_url()?>reservations.php?action=showReservations&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>&area_id=<?= $area_id ?>&date=<?=  $_date ?>&page=<?= $page ?>&week=<?= $week_id ?>">
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
    </div>
    <div class="legend">
      <h3><?= lang('Explanation of the schedule', 'show_order')?>:</h3>
      <ul>
        <li>
          <div class="legend-img today">00</div>
          <?= lang('legend_calen2')?>
        </li>
        <li>
          <div class="legend-img select_day">00</div>
          <?= lang('legend_calen3')?>
        </li>
        <li>
          <div class="legend-img locked">00</div>
          <span style="position: relative;bottom: 8px"><?= lang('legend_calen4')?></span>
        </li>
        <li>
          <div class="legend-img holiday">00</div>
          <?= lang('legend_calen5')?>
        </li>
      </ul>
    </div>
  </div>
</div>
