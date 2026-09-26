<?php
use AC\core\system\helpers\StringHelper;

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
<table class="mainScheduleList">
  <tr>
    <?php foreach ($areas as $area) { ?>
      <th class="cell-item-title">
        <div class="cell-item-title">
          <div class="placeButton<?= ($page == $area->id ? 'On' : '') ?>"
               onclick="location.href='reservations.php?type_id=<?= $type_id ?>&area_id=<?= $area->id ?>&page=<?= $area->id ?>&date=<?= $date ?>'"><?= $area->title ?>
          </div>
        </div>
      </th>
    <?php } ?>
      <th></th>
  </tr>
  <tr>
    <td colspan="<?= count($areas) ?>">
      <div id="scrollScheduleBlock">
        <table class="mainScheduleList">
          <tr>
          <?php
            //Размер максимального столбца, нужен для заполнения пустоты под более мелкими столбцами
            $max_period_number = 0;
            foreach ($areas as $area){
                if(count($area->periods) > $max_period_number){$max_period_number = count($area->periods);}
            }
          ?>

            <?php foreach ($areas as $area) { ?>
              <?php if (count($area->periods) == 0) { ?>
                <td class="avaliable" style="text-align: center"><?= lang('No operation', 'show_order')?></td>
              <?php } else { ?>
                <td>
                  <table class="inner-cell-block">
                    <?php foreach ($area->periods as $period) { ?>
                      <tr>
                        <td>
                          <div class="<?= $period->class_touch ?>">
                            <?php if ($period->client) { ?>
                              <div class="card-item <?= (($client_id !== null && $period->client->main_client_id !== null
                                && $client_id !== $period->client->main_client_id
                                && $period->client->action != false && $period->order_status == 0
                              ) ? 'btn-join' : '') ?>" style="top:10px; height:62px" rel="order">
                                <?php if ($period->client->visible == true) { ?>
                                <?= $period->client->name . (isset($period->ticket) && $period->ticket !== false ? ' (Abo)' : '') . (!$period->client->id && $period->client->name !== ' ' && !empty($period->client->name) && $period->class !== 'blocked' ? ' (Gast)' : '') ?>
                                <?= '<br>' . StringHelper::shield($period->client->second_player->name ?? '', doubleEncode: false) ?>
                                <?php } ?>
                                <div class="card-item-block">
                                  <?php
                                  $title_button = isset($period->button_confirmation) && $period->button_confirmation ? $period->button_confirmation->title_button_remove : 'löschen';
                                  if (($client_id != null && $period->client->main_client_id !== null
                                    && $client_id !== $period->client->main_client_id
                                    && $period->client->action != false && $period->order_status == 0
                                  )) {
                                    $title_button = 'sich anschliessen';
                                  }
                                  switch ($period->client->action) {
                                    case 'removeOrder':
                                      $period->client->action = 'removeOrderForm';
                                      break;
                                    case 'unJoin':
                                      $period->client->action = 'unJoinForm';
                                      break;
                                  }
                                  ?>
                                  <?php if ($period->client->action) {?>
                                    <?php $keyJs = ($period->ticket ? 'ticket_' . $period->ticket : 'order_' . $period->reservation_id);?>
                                    <script>
                                      todo.onload(function () {
                                        popupload_<?=$period->reservation_id?> = new Popup('', 50, 50)
                                        popupload_<?=$period->reservation_id?>.initialize(false)
                                        popupload_<?=$period->reservation_id?>.innerContent('<img src="<?= base_url(paths()->getAssetsDir('images/page_loader.gif'))?>"/>')

                                        popupcontent_<?=$period->reservation_id?> = new Popup('', 500, 400)
                                        popupcontent_<?=$period->reservation_id?>.initialize(true);
                                        <?= $keyJs ?> = new
                                        ajaxLoader('reservations.php?action=<?= $period->client->action ?>', popupcontent_<?=$period->reservation_id?>, popupload_<?=$period->reservation_id?>)
                                        <?php if(isset($period->button_confirmation) && $period->button_confirmation) :?>
                                        <?= 'confirm_'. $period->reservation_id?> = new ajaxLoader('reservations.php?action=confirmOrderForm', popupcontent_<?=$period->reservation_id?>, popupload_<?=$period->reservation_id?>)
                                        <?php endif;?>
                                      })
                                    </script>
                                    <div style="display: flex;justify-content:space-around">
                                      <?php if(isset($period->button_confirmation) && $period->button_confirmation):?>
                                        <div class="button-gray"
                                             onclick="<?= 'confirm_'. $period->reservation_id?>.loadModule('reservation_id=<?= $period->reservation_id?>&area_id=<?= $area->id ?>&date=<?= $date ?>&time=<?= $period->start ?>&page=<?= $page ?>&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>&ajax=1')">
                                          <?= $period->button_confirmation->title_button_confirmation ?>
                                        </div>
                                      <?php endif;?>
                                      <div class="button-gray"
                                           onclick="<?= $period->ticket ? 'ticket_' . $period->ticket : 'order_' . $period->reservation_id ?>.loadModule('<?= ($period->ticket ? 'ticket_id=' . $period->ticket : '') ?><?= ($period->main_reservation_id ? 'main_reservation_id=' . $period->main_reservation_id : '') ?>&area_id=<?= $area->id ?>&date=<?= $date ?>&time=<?= $period->start ?>&page=<?= $page ?>&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>&ajax=1')">
                                        <?= $title_button ?>
                                      </div>
                                    </div>
                                  <?php } ?>
                                </div>
                              </div>
                            <?php } else { ?>
                              <input type="hidden" name="action"
                                     value="action=showOrder&area_id=<?= $area->id ?>&date=<?= $date ?>&time=<?= $period->start ?>&page=<?= $page ?>&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>&ajax=1"/>
                            <?php } ?>
                            <div class="cell-front-item">
                              <table class="cell_front_image_table">
                                <tr>
                                  <td class="out-side cell_front_image_edges">
                                    <img src="<?= base_url(paths()->getAssetsDir('')) ?>images/cell_front_item/<?= $period->color ?>_cell_front_item_left.png"
                                         alt=""/>
                                  </td>
                                  <td class="center <?= $period->color . '-center' ?>">
                                    <?= $period->start . ' - ' . $period->finish . ' ' . lang('Clock')?>
                                    <?php if(!empty($period->tmp_info)):?>
                                      <b> (i)</b>
                                      <div class='popup-box'>
                                        <?= $period->tmp_info?>
                                      </div>
                                    <?php endif;?>
                                  </td>
                                  <td class="out-side cell_front_image_edges">
                                    <img src="<?= base_url(paths()->getAssetsDir('')) ?>images/cell_front_item/<?= $period->color ?>_cell_front_item_right.png"
                                         alt=""/>
                                  </td>
                                </tr>
                              </table>
                            </div>
                          </div>
                        </td>
                      </tr>
                    <?php } ?>
                    <?php for($i = count($area->periods); $i < $max_period_number; $i++){ ?>
                    <?php //Заполняем пустоты таблицы ячейками ?>
                       <tr>
                           <td>
                                <div class="cell-item-gone">
                                    <div class="cell-front-item">
                                        <table class="cell_front_image_table">
                                            <tbody><tr>
                                                <td class="out-side cell_front_image_edges">
                                                    <img src="<?= base_url(paths()->getAssetsDir('')) ?>images/cell_front_item/gray_cell_front_item_left.png" alt="">
                                                </td>
                                                <td class="center gray-center"></td>
                                                <td class="out-side cell_front_image_edges">
                                                    <img src="<?= base_url(paths()->getAssetsDir('')) ?>images/cell_front_item/gray_cell_front_item_right.png" alt="">
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                           </td>
                        </tr>
                    <?php } ?>
                  </table>
                </td>
              <?php } ?>
            <?php } ?>
          </tr>
        </table>
      </div>
    </td>
  </tr>
</table>
