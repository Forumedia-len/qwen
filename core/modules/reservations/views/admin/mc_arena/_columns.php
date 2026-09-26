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
<a name="requests"></a>
<div class="pupopWindowPlace" id="pupopWindowPlace"></div>
<div class="pupopWindow" id="pupopWindow"></div>
<script>
  var popup = new popupAjaxWindow('pupopWindowPlace', 'pupopWindow')
</script>
<style>
    .eur-admin{
        display: inline !important;
    }
</style>

<table width="100%" cellspacing="5" cellpadding="0" class="allarea">
  <tr>
    <?php
    $width_column = floor(100 / (count($areas)));
    foreach ($areas as $area) { ?>
      <td width="<?= $width_column ?>%" align="center" valign="top">
        <table cellspacing="1" cellpadding="0" width="100%" class="area">
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
                        <div class="period" <?= $period->memo ? 'title="'. lang('Comment').':' . $period->memo . '"' : '' ?>
                             style="height: 15px">
                          <?php // время - старт ?>
                          <span class="period-time-text">
                                <?php
                                $href = false;
                                if ($period->class == 'period_avaliable' || $period->gone_action) {
                                  $href = 'reservations.php?action=requestForm&area_id=' . $area->id . '&date=' . $date . '&time=' . $period->start . '&page=' . $page . '&type_id=' . $type_id . '&sport_id=' . $sport_id;
                                } elseif (($period->admin_change_gone_reservation || $period->class == 'period_ordered') && !$period->ticket) {
                                  $href = 'reservations.php?action=editRequest&area_id=' . $area->id . '&date=' . $date . '&time=' . $period->start . '&page=' . $page . '&type_id=' . $type_id . '&sport_id=' . $sport_id;
                                }
                                ?>
                                <?= ($href ? '<a href="' . $href . '">' : '') ?><?= trim($period->start . ' - ' . $period->finish) ?><?= ($href ? '</a>' : '') ?>
                          </span>
                          <?php // время - конец ?>

                          <?php // иконки свет,тепло,сеть,сток,коментарий и другие - старт ?>
                          <?php if ($period->stock ||  $period->memo || $period->state || $period->customer || $period->client) { ?>
                            <span class="period-icons">
                            <?= $period->stock ? ' | [' . $period->stock . ']' : '' ?>
                            <?= $period->memo ? ' | [<i><b> i </b></i>]' : '' ?>
                            <?= $period->customer || ($period->client && $period->client->id == null && !empty($period->client->name) && $period->client->name != ' ') ? ' | [<i><b> A </b></i>]' : '' ?>
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
                          <?php if ($period->client != false && $period->client->id) { ?>
                            <span class="period-name">
                              &nbsp;|
                              <a href="<?= Service::structure()->getPageHrefByKey('clients_personal_data')?>?client_id=<?= $period->client->id ?>"
                                 onclick="return showUserInfo (this)"
                                 class="white"><?= $period->client->name . (isset($period->ticket) && $period->ticket !== false ? ' (Abo)' : '') ?></a>
                            </span>
                          <?php } elseif ($period->client && !empty($period->client->name) && $period->client->name != ' ') { ?>
                            <span class="period-name">
                              &nbsp;|
                                <?= $period->client->name . ($period->class !== 'period_blocked' ? ' (PP Gast)' : '') ?>
                            </span>
                          <?php } ?>
                          <?php // Имя клиента - конец ?>

                          <?php // Способ оплаты и цена - начало ?>
                          <?php if (USE_CHANGE_PRICE_AND_ENCASH_IN_ADMIN && $period->encash !== null) {
                            $color   = 'red';
                            $style   = ' cursor:pointer';
                            $onclick = 'onclick="return changeCashStat(change_state_' . $period->reservation_id . ')"';
                            if ($period->order_status == 1 || $period->encash == 3) {
                              $color   = 'green';
                              $style   = '';
                              $onclick = '';
                            }
                            ?>
                            | [<b><?= $period->encash_title ?></b>] <strong><?= $period->price_a ?></strong>
                            |
                          <?php if ($color == 'red') { ?>
                            <script>
                              var change_state_<?=$period->reservation_id?> = new
                              ajaxLoader('change_state_<?=$period->reservation_id?>',
                                'ajax_reservation_state.php?reservation_id=<?=$period->reservation_id?>',
                                'changeStateBlock_<?=$period->reservation_id?>', '')
                            </script>
                          <?php } ?>
                            <div style="display:inline;<?= $style ?>" <?= $onclick ?>>
                              <?= ($color == 'red' ? '<span class="eur-admin" id="changeStateBlock_' . $period->reservation_id . '">' : '') ?>
                              <strong style="color:<?= $color ?>">[<?=CURR_VALUTE?>]</strong>
                              <?= ($color == 'red' ? '</span>' : '') ?>
                            </div>
                            <?php if ($period->encash != 3) { ?>
                            <a href="#"
                               onclick="return popup.createWindow('get_ajax_data.php?action=getOrdersData&type_id=<?=$type_id?>&sport_id=<?=$sport_id?>&area_id=<?=$area->id?>&date=<?=$date?>&time=<?=$period->start?>&page=<?=$page?>')">
                              <img src="<?= base_url(paths()->getAssetsDir('images/buttons/update.gif'))?>" alt="<?= lang('Edit')?>"/>
                            </a>
                            <?php } ?>
                          <?php } ?>
                          <?php // Способ оплаты - конец ?>

                          <?php // кнопка remove - начало?>
                          <?php if ($period->client && isset($period->client->action) && $period->client->action) { // кнопка remove?>
                            <span style="float: right">
                                  <a href="reservations.php?action=<?= $period->client->action ?><?= ($period->ticket ? '&ticket_id=' . $period->ticket : '') ?>&area_id=<?= $area->id ?>&date=<?= $date ?>&time=<?= $period->start ?>&page=<?= $page ?>&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>"
                                     onclick="return ifConfirm ()">
                                    <img src="<?= base_url(paths()->getAssetsDir('images/order_remove.gif'))?>" alt="<?= lang('button_remove')?>" border="0" hspace="3"/>
                                  </a>
                              </span>
                          <?php } ?>
                          <?php // кнопка remove - конец?>
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



