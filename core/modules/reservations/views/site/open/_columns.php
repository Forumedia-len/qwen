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
 * @var array  $pages          - вкладки для страниц страницы
 * @var array  $areas          - площадки
 * @var bool   $week           - недельный отчет показывать
 */


use AC\core\system\helpers\StringHelper;

$area_id = (int)Service::request()->_('area_id', $areas[0]->id);

?>
<?php // -------------- Вывод табов для площадок на мобильной версии ----?>
<?php if (count($areas) > 1) { ?>
  <div class="reservations-areas-tabs-block">
    <?php foreach ($areas as $area) { ?>
      <div class="reservations-areas-tabs-item <?= ($area_id == $area->id ? ' active' : '') ?>"
           style="width: <?= 100 / count($areas) ?>%">
        <?php if ($area_id != $area->id) { ?>
        <a
          href="<?= site_url() ?>reservations.php?action=showReservations&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>&date=<?= $date ?>&page=<?= $page ?>&area_id=<?= $area->id ?>">
          <?php } else { ?>
          <span>
        <?php } ?>
        <?= $area->title ?>
        <?php if ($area_id != $area->id) { ?>
        </a>
      <?php } else { ?>
        </span>
      <?php } ?>
      </div>
    <?php } ?>
  </div>
<?php } ?>
<?php // -------------- Вывод табов для площадок на мобильной версии ----?>
<div class="reservations-field">
  <div class="table-left-arrow"></div>
  <div class="reservations-table-wrapper">
    <table class="day-reservations-table">
      <tr>
        <?php
        $width_column = floor(100 / (count($areas)));
        foreach ($areas as $area) { ?>
          <td width="<?= $width_column ?>%" align="center" valign="top" class="column-item"
              data-area-id="<?= $area->id ?>">
            <form action="<?= site_url() ?>reservations.php" method="post">
              <input type="hidden" name="area_id" value="<?= $area->id ?>"/>
              <input type="hidden" name="type_id" value="<?= $type_id ?>"/>
              <input type="hidden" name="sport_id" value="<?= $sport_id ?>"/>
              <input type="hidden" name="action" value="showOrder"/>
              <input type="hidden" name="date" value="<?= $date ?>"/>
              <input type="hidden" name="page" value="<?= $page ?>"/>
              <table class="day-reservations-table-area">
                <tr>
                  <th>
                    <?= $area->title ?>
                  </th>
                </tr>
                <?php if (count($area->periods) == 0) { ?>
                  <tr>
                    <td class="avaliable" style="text-align: center"><?= lang('No operation', 'show_order') ?></td>
                  </tr>
                <?php } else {
                  foreach ($area->periods as $period) { ?>
                    <tr class="<?= (isset($period->show_order_action) && $period->show_order_action
                    && ($client_id != null || $client_bar)
                    && $period->class !== 'unAvailable' ? 'hover-show-order' : '') ?>">
                      <td class="<?= $period->class ?>">
                        <div class="period">
                          <?php // чекбокс - старт ?>
                          <div class="period-time-check">
                            <?php if (($client_bar || $client_id != null) && config('reservations')->isCloseType((int)$type_id) && \AC\core\ReservationsVisualizationCommon::checkLimitDayReservation($date,
                                $on_reservation) && $period->class == 'available') {
                              /** показывать когда:
                               * 1.клиент зарегился
                               * 2.если закрытые корты - type_id = 1
                               * 3. В этот день можно бронировать
                               * 4. Этот промежуток можно забронировать
                               */
                              ?>
                              <input type="checkbox" name="time[<?= $period->start ?>]" value="1"
                                     class="check check-on"/>
                            <?php } ?>
                          </div>
                          <?php // чекбокс - конец ?>
                          <?php // кнопки - старт ?>
                          <div class="period-time-button open-court">

                            <?php // кнопка remove - начало?>
                            <?php if ($client_id != null && $period->main_reservation_id == null
                              && isset($period->client) && $period->client && $client_id == $period->client->id
                              && $period->client->action != false
                            ) { // кнопка remove?>
                              <? if ($period->link_reserv): ?><a
                                href="reservations.php?action=<?= $period->client->action ?><?= ($period->ticket ? '&ticket_id=' . $period->ticket
                                  : '') ?>&area_id=<?= $area->id ?>&date=<?= $date ?>&time=<?= $period->start ?>&page=<?= $page ?>&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>&type_reservation=<?= $period->type_reservation ?>"
                                onclick="return ifConfirm ()" class="icon-btn remove-btn">
                                </a><? endif ?>
                            <?php } ?>
                            <?php // кнопка remove - конец?>
                            <?php // кнопка joinForm - начало?>
                            <?php if ($client_id != null && isset($period->client) && $period->client && $period->client->main_client_id !== null
                              && $client_id !== $period->client->main_client_id
                              && $period->client->action != false && $period->order_status == 0
                            ) { // кнопка joinForm?>
                              <? if ($period->link_reserv): ?><a
                                href="reservations.php?action=<?= $period->client->action ?><?= ($period->main_reservation_id
                                  ? '&main_reservation_id=' . $period->main_reservation_id
                                  : '') ?>&area_id=<?= $area->id ?>&date=<?= $date ?>&time=<?= $period->start ?>&page=<?= $page ?>&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>"
                                class="icon-btn fastener-btn">
                                </a><? endif ?>
                            <?php } ?>
                            <?php // кнопка joinForm - конец?>
                            <?php // кнопка unJoin - начало ?>
                            <?php if ($client_id != null && isset($period->client) && $period->client && $period->client->main_client_id !== null
                              && $client_id !== $period->client->main_client_id && $client_id == $period->client->id
                              && $period->client->action != false && $period->order_status == 1
                            ) { // кнопка unJoin?>
                              <? if ($period->link_reserv): ?><a href="reservations.php?action=<?= $period->client->action ?><?= ($period->main_reservation_id !== null
                                ? '&main_reservation_id=' . $period->main_reservation_id
                                : '') ?>&area_id=<?= $area->id ?>&date=<?= $date ?>&time=<?= $period->start ?>&page=<?= $page ?>&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>"
                                class="icon-btn remove-btn"><? endif ?>
                              </a>
                            <?php } ?>
                            <?php // кнопка unJoin - конец?>
                          </div>
                          <?php // кнопки - конец ?>
                          <?php // время - старт ?>
                          <div class="period-time-text open-court">
                            <div class="period-time-text-title">
                              <?php
                              $href    = false;
                              $onClick = false;
                              if (isset($period->link_reserv) && $period->link_reserv) {
                                if ($period->class == 'available') {
                                  if ($client_id != null || $client_bar) {
                                    if ($period->show_order_action) {
                                      $href = 'reservations.php?action=showOrder&area_id=' . $area->id . '&date=' . $date . '&time=' . $period->start . '&page=' . $page . '&type_id=' . $type_id . '&sport_id=' . $sport_id;
                                    }
                                  } elseif (USE_AUTHORIZATION) {
                                    $href    = '#';
                                    $onClick = 'onclick="return AuthWindow(true)"';
                                  }
                                } elseif ($period->class == 'half') {
                                  if (($client_bar || $client_id != null) && $period->client->main_client_id !== null
                                    && $client_id !== $period->client->main_client_id
                                    && $period->client->action != false && $period->order_status == 0
                                  ) {
                                    $href = 'reservations.php?action=' . $period->client->action . ($period->main_reservation_id
                                        ? '&main_reservation_id=' . $period->main_reservation_id
                                        : '') . '&area_id=' . $area->id . '&date=' . $date . '&time=' . $period->start . '&page=' . $page . '&type_id=' . $type_id . '&sport_id=' . $sport_id;
                                  }
                                }
                              }
                              ?>
                              <?= ($href ? '<a href="' . $href . '" ' . ($onClick ? $onClick : '') . '>' : '') ?>
                              <?= $period->start . ' - ' . $period->finish . ($period->class == 'own' && $period->client->visible && isset($period->ticket) && $period->ticket !== false
                                ? ' (' . lang('Abo') . ')' : '') ?>
                              <?= ($href ? '</a>' : '') ?>
                              <?php if(!empty($period->tmp_info)):?>
                                <b> (i)</b>
                                <div class='popup-box'>
                                  <?= $period->tmp_info?>
                                </div>
                              <?php endif;?>
                            </div>
                            <?php if ($period->client != false && $period->client->visible == true) { ?>
                              <div class="period-time-text-client-name">
                                <?= $period->client->name . (!$period->client->id && $period->client->name !== ' ' && !empty($period->client->name) && $period->class !== 'blocked'
                                  ? ' (' . lang('Guest') . ')' : '') ?>
                                <?= (!empty($period->client->second_player) && !empty($period->client->second_player->name)
                                  ? '<br>' . StringHelper::shield($period->client->second_player->name, doubleEncode: false)
                                    . (!$period->client->second_player->id ? ' (' . lang('Guest') . ')'
                                    : '') : '') ?>
                              </div>
                            <?php } ?>
                          </div>
                          <?php // время - конец ?>
                          <?php // иконки - старт ?>
                          <div class="period-time-state">
                            <?php
                            /** Делаем ссылками когда:
                             * 1.клиент зарегился
                             * 2.если закрытые корты - type_id = 1
                             * 3. В этот день можно бронировать
                             * 4. Этот промежуток можно забронировать
                             */
                            if ($period->state !== false) {
                              foreach ($period->state as $key_st => $state) {
                                $href   = false;
                                $yellow = ' yellow';
                                if ($area->{$key_st . '_on'} == 1) {
                                  switch (1) {
                                    case (int)($period->class == 'available' && $state->visible == 0): // промежуток можно забронировать у него нету ссылок
                                      $href   = false;
                                      $yellow = '';
                                      break;
                                    case (int)($client_id != null && /*$client_id == $period->client->id &&*/
                                      $state->visible == 0):
                                      $href   = 'reservations.php?action=lightOrder' . ($period->ticket ? '&ticket_id=' . $period->ticket
                                          : '') . '&area_id=' . $area->id . '&date=' . $date . '&time=' . $period->start . '&page=' . $page . '&type_id=' . $type_id . '&sport_id=' . $sport_id . '&state=' . $key_st;
                                      $yellow = '';
                                      break;
                                  }
                                  ?>
                                  <?php if ($href !== false) { ?>
                                    <a href="<?= $href ?>" class="state-icon">
                                  <?php } ?>
                                  <?php if ($period->class == 'own' || $period->class == 'available') { ?>
                                    <span class="state-icon state-icon-<?= $key_st ?><?= $yellow ?>"> </span>
                                  <?php } ?>
                                  <?php if ($href !== false) { ?>
                                    </a>
                                  <?php } ?>
                                  <?php
                                }
                              }
                            } ?>
                          </div>
                          <?php // иконки - конец ?>
                        </div>
                      </td>
                    </tr>
                  <?php }
                } ?>
              </table>
              <?php if (USE_AUTHORIZATION && config('reservations')->isCloseType((int)$type_id) && count($area->periods) != 0) { // закрытые корты: кнопки бронирования; на открытых — один период без этой формы ?>
                <div style="text-align:center;">
                  <input type="submit" name="go" value="<?= lang('Booking_button') ?>" class="button reservation-button"
                         style="display:inline" <?= (($client_id === null && !$client_bar) ? 'onclick="return AuthWindow(true);return false;"'
                    : '') ?>/>
                </div>
              <?php } ?>
            </form>
          </td>
        <?php } ?>
      </tr>
    </table>
  </div>
  <div class="table-right-arrow"></div>
</div>
<div class="legend-field">
  <div class="row">
    <div class="legend-field-title col-sm-3">
      <span><?= lang('legend_cort1') ?>: </span>
    </div>
    <div class="col-sm-9">
      <div>
        <span class="ordered col-sm-2 col-xs-12"><?= lang('legend_cort2') ?></span>
        <span class="blocked col-sm-4 col-xs-12"><?= lang('legend_cort3') ?></span>
        <span class="own col-sm-4 col-xs-12"><?= lang('legend_cort4') ?></span>
        <?php if (!config('DoubleGame')->doubleFriendsEnabled($type_id, $sport_id, $area_id)) { ?>
          <span class="half col-sm-2 col-xs-12"><?= lang('Connect', 'show_order') ?></span>
        <?php } ?>
      </div>
    </div>
  </div>
</div>




