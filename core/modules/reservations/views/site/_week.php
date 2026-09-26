<?php
/**
 *  Вывод таблицы расписания для кортов
 * @var array  $sports         - все спроты этого типа площадок
 * @var int    $sport_id       - индетификатор текущего спорта
 * @var int    $type_id        - тип корта
 * @var int    $client_id      - индефикатор зарегистрированного клиента
 * @var int    $on_reservation - возможность бронировать этот день
 * @var string $date           - дата
 * @var int    $page           - номер страницы
 * @var array  $areas          - площадки
 */

?>
<div class="reservations-field reservations-field-week">
  <div class="table-left-arrow"></div>
  <div class="reservations-table-wrapper">
    <div class="week-reservations-time-block">
      <table class="week-reservations-table">
        <tr>
          <th></th>
        </tr>
        <?php foreach ($areas->periods as $periods) { ?>
          <tr>
            <td class="week-period-time-title">
              <?= $periods->start . ' - ' . $periods->finish ?>
            </td>
          </tr>
        <?php } ?>
      </table>
    </div>
    <div class="week-reservations-period-block">
      <table class="week-reservations-table">
        <tr>
          <?php foreach ($areas->weekdays as $weekday) { ?>
            <th>
            <span class="week_d_name">
              <?= $weekday->title ?>
            </span>
              <br/>
              <span class="week_d_date">
              <?= $weekday->date ?>
            </span>
            </th>
          <?php } ?>
        </tr>
        <?php foreach ($areas->periods as $periods) { ?>
          <tr>
            <?php foreach ($periods->period as $period) { ?>
              <td>
                <div class="week-row">
                  <?php foreach ($period->areas as $area) { ?>
                    <?php
                    $href = false;
                    if (!HIDE_URL_WOCHENANSICHT && $area->class === '') {
                      if (($client_id != null || $client_bar) && \AC\core\ReservationsVisualizationCommon::checkLimitDayReservation($area->weekday_date, $on_reservation)) {
                        $href = 'location.href=\'reservations.php?action=showOrder&area_id=' . $area->id . '&date=' . $area->weekday_date . '&time=' . $area->start . '&page=' . $page . '&type_id=' . $type_id . '&sport_id=' . $sport_id . '\'';
                      } else {
                        $href = USE_AUTHORIZATION ? 'AuthWindow(true)' : '';
                      }
                    }
                    $style = [];
                    if ($area->class == 'holiday') $style[] = 'color: #fff;background: #727271';
                    if ($href != false) $style[] = 'cursor: pointer !important';
                    ?>
                    <span title="<?= $area->title ?>"
                          class="week-box <?= $area->class ?> <?= ($href != false ? 'available' : '') ?>" <?= (!empty($style) ? 'style="' . implode(';', $style) . '"' : '') ?> <?= ($href != false ? 'onClick="' . $href . '"' : '') ?>>
              <?= $area->title ?>
                  </span>
                  <?php } ?>
                </div>
              </td>
            <?php } ?>
          </tr>
        <?php } ?>
      </table>
    </div>
  </div>
  <div class="table-right-arrow"></div>
</div>
<div class="legend-field">
  <div class="row">
    <div class="legend-field-title col-sm-3">
      <span><?= lang('Explanation of the schedule', 'show_order')?>: </span>
    </div>
    <div class="col-sm-8">
      <div>
        <span class="ordered col-sm-3 col-xs-12"><?= lang('legend_cort2')?></span>
        <span class="blocked col-sm-5 col-xs-12"><?= lang('legend_cort3')?></span>
        <span class="own col-sm-4 col-xs-12"><?= lang('legend_cort4')?></span>
      </div>
    </div>
  </div>
</div>
