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
?>
<table width="100%" cellspacing="8" cellpadding="0" class="allarea">
  <tr>
    <?php
    $width_column = floor(100 / (count($areas)));
    foreach ($areas as $area) { ?>
      <td width="<?= $width_column ?>%" align="center" valign="top">
        <table cellspacing="0" cellpadding="0" width="100%" class="area">
          <tr>
            <th>
              <?= $area->title ?>
              <?php if (count($area->periods) > 0) { ?>
                <br>
                <?= $area->periods[0]->start . ' - ' . $area->periods[count($area->periods) - 1]->finish ?>
              <?php } ?>
            </th>
          </tr>
          <?php if (count($area->periods) == 0) { ?>
            <tr>
              <td class="avaliable" style="text-align: center"><?= lang('No operation', 'show_order')?></td>
            </tr>
          <?php } else { ?>
            <tr>
              <td bgcolor="#FFFFFF">
                <table cellspacing="1" cellpadding="2" border="0" width="100%" class="area-period">
                  <?php foreach ($area->periods as $period) { ?>
                    <tr>
                      <td class="<?= $period->class ?>">
                        <div class="period" <?= $period->memo ? 'title="'.lang('Comment').':' . $period->memo . '"' : '' ?> >
                          <?php // время - старт ?>
                          <span class="period-time-text">
                                <?php
                                $href = false;
                                if ($period->class == 'period_avaliable') {
                                  $href = 'reservations.php?action=requestForm&area_id=' . $area->id . '&date=' . $date . '&time=' . $period->start . '&page=' . $page . '&type_id=' . $type_id . '&sport_id=' . $sport_id;
                                } elseif (($period->admin_change_gone_reservation || $period->class == 'period_ordered') && !$period->ticket) {
                                  $href = 'reservations.php?action=editRequest&area_id=' . $area->id . '&date=' . $date . '&time=' . $period->start . '&page=' . $page . '&type_id=' . $type_id . '&sport_id=' . $sport_id;
                                }
                                ?>
                                <?= ($href ? '<a href="' . $href . '">' : '') ?><?= trim($period->start . ' - ' . $period->finish) ?><?= ($href ? '</a>' : '') ?>
                          </span>
                          <?php // время - конец ?>

                          <?php // иконки свет,тепло,сеть,сток,коментарий и другие - старт ?>
                          <?php if ($period->stock || $period->memo || $period->state || $period->customer) { ?>
                            <span class="period-icons">
                            <?= $period->stock ? ' | [' . $period->stock . ']' : '' ?>
                            <?= $period->memo ? ' | [<i><b> i </b></i>]' : '' ?>
                            <?= $period->customer ? ' | [<i><b> A </b></i>]' : '' ?>
                            <?php
                            if ($period->state && !empty($period->client->name)) {
                              foreach ($period->state as $key_st => $state) {
                                if ($area->{$key_st . '_on'} == 1 && $state->visible) {
                                  if ($period->client) {
                                    echo ' | [<b>' . strtoupper($key_st[0]) . '</b>]';
                                  }
                                }
                              }
                            } ?>
                          </span>
                          <?php } ?>
                          <?php // иконки - конец ?>

                          <?php // Имя клиента - начало ?>
                          <?php if (isset($period->client->name) && $period->client->name) { ?>
                            <span class="period-name" style="position: relative;line-height: 15px">
                              <?= $period->client->name && $period->client->name != ' ' ? ' |' : '' ?>
                              <?php if ($period->client->id) { ?>
                              <a href="<?= Service::structure()->getPageHrefByKey('clients_personal_data')?>?client_id=<?= $period->client->id ?>"
                                 onclick="return showUserInfo (this)"
                                 class="white">
                                <?php } ?>
                                <?= $period->client->name . (isset($period->ticket) && $period->ticket !== false ? ' (Abo)' : '').((!$period->client->id || $period->client->id == 'guest') && $period->client->name != ' '  && $period->class !== 'period_blocked' ? ' (G)' : '') ?>
                                <?php if ($period->client->id) { ?>
                              </a>
                              <?php } ?>

                              <?php if ($period->client->second_player) { ?>
                                 <?php if(count($areas) > 3):?><br><span style="display: inline-block;min-width: 68px;line-height: 15px"></span><?php endif;?>|
                                <?php if ($period->client->second_player && $period->client->second_player->id !== null) { ?>
                                  <a href="<?= Service::structure()->getPageHrefByKey('clients_personal_data')?>?client_id=<?= $period->client->second_player->id ?>"
                                  onclick="return showUserInfo (this)"
                                  class="white">
                                <?php } ?>
                                <?= $period->client->second_player->name . (!$period->client->second_player->id || $period->client->second_player->id == 'guest' ? ' (G)' : '') ?>
                                <?php if ($period->client->second_player && $period->client->second_player->id !== null) { ?>
                                  </a>
                                <?php } ?>
                              <?php } ?>
                            </span>
                          <?php } ?>
                          <?php if ($period->class == 'period_blocked') { ?>
                              | <?= $period->client->name ?>
                          <?php } ?>

                          <?php // Имя клиента - конец ?>
                          <?php
                          // Способ оплаты и цена - начало ?>
                          <?php
                          if (USE_CHANGE_PRICE_AND_ENCASH_IN_ADMIN
                            && $period->main_reservation_id == null
                            && ($period->encash === '0' || $period->encash === 0) ) {
                            $color   = 'red';
                            $style   = ' cursor:pointer';
                            $onclick = 'onclick="return changeCashStat(change_state_' . $period->reservation_id . ')"';
                            if ($period->order_status == 1) {
                              $color   = 'green';
                              $style   = '';
                              $onclick = '';
                            }
                            ?>
                            | [<b><?= $period->encash_title ?></b>] <strong><?= $period->price_a ?></strong>
                            |
                          <?php
                          if ($color == 'red') { ?>
                            <script>
                              var change_state_<?=$period->reservation_id?> = new
                              ajaxLoader('change_state_<?=$period->reservation_id?>',
                                'ajax_reservation_state.php?reservation_id=<?=$period->reservation_id?>',
                                'changeStateBlock_<?=$period->reservation_id?>', '')
                            </script>
                          <?php
                          } ?>
                            <div style="display:inline;<?= $style ?>" <?= $onclick ?>>
                              <?= ($color == 'red' ? '<span class="eur-admin" id="changeStateBlock_' . $period->reservation_id . '">' : '') ?>
                              <strong style="color:<?= $color ?>">[<?=CURR_VALUTE?>]</strong>
                              <?= ($color == 'red' ? '</span>' : '') ?>
                            </div>
                            <?php
                          } ?>
                          <?php
                          // Способ оплаты - конец ?>

                          <?php // кнопки начало?>
                          <?php if (isset($period->client->action) && $period->client->action) { ?>
                            <span style="float: right" class="period-action">
                            <?php // кнопка remove - начало?>
                              <?php if ($period->client && $period->main_reservation_id == null) { // кнопка remove?>
                                <a href="reservations.php?action=<?= $period->client->action ?><?= ($period->ticket ? '&ticket_id=' . $period->ticket : '') ?>&area_id=<?= $area->id ?>&date=<?= $date ?>&time=<?= $period->start ?>&page=<?= $page ?>&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>"
                                   onclick="return ifConfirm ()">
                                    <img src="<?= base_url(paths()->getAssetsDir('images/order_remove.gif'))?>" alt="<?= lang('button_remove')?>" border="0" hspace="3"/>
                                 </a>
                              <?php } ?>
                              <?php // кнопка remove - конец?>
                              <?php // кнопка joinForm - начало?>
                              <?php if (!$period->order_status && $period->client->main_client_id != null) { // кнопка joinForm?>
                                <a href="reservations.php?action=<?= $period->client->action ?><?= ($period->main_reservation_id ? '&main_reservation_id=' . $period->main_reservation_id : '') ?>&area_id=<?= $area->id ?>&date=<?= $date ?>&time=<?= $period->start ?>&page=<?= $page ?>&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>"
                                   class="a-icon">
                              <img src="<?= base_url(paths()->getAssetsDir('images/fastener.png'))?>" alt="<?= lang('button_remove')?>" border="0" hspace="3"
                                   style="width: 14px"/>
                            </a>
                              <?php } ?>
                              <?php // кнопка joinForm - конец?>
                              <?php // кнопка unJoin - начало ?>
                              <?php if ($period->order_status && $period->client->main_client_id != null ) { // кнопка unJoin?>
                                <a href="reservations.php?action=<?= $period->client->action ?><?= ($period->main_reservation_id !== null ? '&main_reservation_id=' . $period->main_reservation_id : '') ?>&area_id=<?= $area->id ?>&date=<?= $date ?>&time=<?= $period->start ?>&page=<?= $page ?>&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>"
                                   class="a-icon">
                              <img src="<?= base_url(paths()->getAssetsDir('images/order_remove.gif'))?>" alt="<?= lang('button_remove')?>" border="0" hspace="3"/>
                            </a>
                              <?php } ?>
                            </span>
                          <?php } ?>
                          <?php // кнопка unJoin - конец?>
                          <?php // кнопки начало?>
                        </div>
                      </td>
                    </tr>
                  <?php } ?>
                </table>
              </td>
            </tr>
          <?php } ?>
        </table>
      </td>
    <?php } ?>
  </tr>
</table>



