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
 * @var object  $areas          - площадки
 */
?>
<table width="100%" cellspacing="8" cellpadding="0" class="week-block">
  <tr>
    <th></th>
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
      <td>
        <?= $periods->start . ' - ' . $periods->finish ?>
      </td>
      <?php foreach ($periods->period as $period) { ?>
        <td>
          <div class="week-row">
            <?php foreach ($period->areas as $area) { ?>
              <?php
                $href = false;
                if($area->class === ''){
                  if($client_id != null && \AC\core\ReservationsVisualizationCommon::checkLimitDayReservation($area->weekday_date, $on_reservation)) {
                    $href = 'location.href=\'reservations.php?action=showOrder&area_id='.$area->id.'&date='.$area->weekday_date.'&time='.$area->start.'&page='.$page.'&type_id='.$type_id.'&sport_id='.$sport_id.'\'';
                  } else {
                    $href = 'AuthWindow(true)';
                  }
                }
              ?>
              <span title="<?=$area->title?>" class="week-box <?=$area->class?> <?=($href != false ? 'available': '')?>" <?=($href != false ? 'onClick="'.$href.'"' : '')?>>
              <?=$area->title?>
            </span>
            <?php } ?>
          </div>
        </td>
      <?php } ?>
    </tr>
  <?php } ?>
</table>
