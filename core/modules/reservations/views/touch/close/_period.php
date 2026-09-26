<?php
/**
 * @var string     $date           - дата
 * @var int        $page           - номер страницы
 * @var array      $areas          - площадки
 * @var bool       $week           - недельный отчет показывать
 * @var View       $this
 * @var int        $on_reservation - возможность бронировать этот день
 * @var int        $sport_id       - индетификатор текущего спорта
 * @var int        $type_id        - тип корта
 * @var int        $client_id      - индефикатор зарегистрированного клиента
 * @var bool       $client_bar     - зарегился ли левый клиент
 * @var array      $params
 * @var string      $courtType
 * @var BaseObject $period
 * @var BaseObject $area
 */


use AC\core\system\object\BaseObject;
use AC\core\system\view\View;

?>
<?php if ($period) { ?>
  <td class="<?= $period->class ?>">
    <?php // время - старт ?>
    <span class="period-time-text">
    <?php
    $href    = false;
    $onClick = false;
//    if ($period->class == 'available' && ($client_id != null || $client_bar)) {
//      if ($client_id != null || $client_bar) {
//        $href = 'reservations.php?action=showOrder&area_id=' . $area->id . '&date=' . $date . '&time=' . $period->start . '&page=' . $page . '&type_id=' . $type_id . '&sport_id=' . $sport_id;
//      } else {
//        $href    = '#';
//        $onClick = 'onclick="return AuthWindow(true)"';
//      }
//    }
    ?>
    <?= ($href ? '<a href="' . $href . '" ' . ($onClick ? $onClick : '') . '>' : '') ?>
    <?= $period->start . ' - ' . $period->finish . (isset($period->ticket) && $period->ticket !== false ? ' ('. lang('Abo') . ')' : '')
    . ($period->class == 'unAvailable' && $period->client  && $period->client->visible ? ' (' . lang('Single_title') . ')' : '')?>
    <?= ($href ? '</a>' : '') ?>
      <?php if(!empty($period->tmp_info)):?>
        <b> (i)</b>
        <div class='popup-box'>
                                <?= $period->tmp_info?>
                              </div>
      <?php endif;?>

  </span>
    <?php // время - конец ?>
    <?php // иконки - старт ?>
    <span class="period-time-state">
    <?php
    /** Делаем ссылками когда:
     * 1.клиент зарегился
     * 2.если закрытые корты - type_id = 1
     * 3. В этот день можно бронировать
     * 4. Этот промежуток можно забронировать
     */
    if ($period->state !== false && !$show_form) {
      foreach ($period->state as $key_st => $state) {
        $href   = false;
        $yellow = ' yellow';
        if ($area->{$key_st . '_on'} == 1) {
          switch (1) {
            case (int)($period->class == 'available' && $state->visible == 0): // промежуток можно забронировать у него нету ссылок
              $href   = false;
              $yellow = '';
              break;
//                                    case (int)($period->class != 'available' && $state->visible == 1): // промежуток можно забронировать у него нету ссылок
//                                      $href = false;
//                                      $yellow = ' yellow';
//                                      break;
            case (int)($client_id != null && isset($period->client->id) && $client_id == $period->client->id && $state->visible == 0):
              $href   = 'reservations.php?action=lightOrder' . ($period->ticket ? '&ticket_id=' . $period->ticket
                  : '') . '&area_id=' . $area->id . '&date=' . $date . '&time=' . $period->start . '&page=' . $page . '&type_id=' . $type_id . '&sport_id=' . $sport_id . '&state=' . $key_st;
              $yellow = '';
              break;
            default :
              $href   = false;
              $yellow = ' yellow';
          }
          ?>
          <? if ($period->link_reserv): ?>
            <?php if ($href !== false) { ?>
              <a href="<?= $href ?>" class="state-icon">
            <?php } ?>
            <?php if ($period->class == 'own' || $period->class == 'available') { ?>
              <span class="state-icon state-icon-<?= $key_st ?><?= $yellow ?>"> </span>
            <?php } ?>
            <?php if ($href !== false) { ?>
              </a>
            <?php } ?><?endif ?>
          <?php
        }
      }
    } ?>
  </span>
    <?php // иконки - конец ?>
    <?php // кнопка remove - начало?>
    <?php if ($client_id != null && isset($period->client->id)
      && $client_id == $period->client->id
      && $period->client->action != false && $show_form
    ) { // кнопка remove?>
      <span class="period-time-button">
      <a href="reservations.php?action=<?= $period->client->action ?><?= ($period->ticket ? '&ticket_id=' . $period->ticket
        : '') ?>&area_id=<?= $area->id ?>&date=<?= $date ?>&time=<?= $period->start ?>&page=<?= $page ?>&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>"
         onclick="return ifConfirm ()" class="a-icon">
        <img src="<?= base_url(paths()->getAssetsDir('images/order_remove.jpg'))?>" border="0" class="order_remove checkboxImage"/>
      </a>
      </span>
    <?php } ?>
    <?php // кнопка remove - конец?>
    <?php // чекбокс - старт ?>
    <?php if (($client_bar || $client_id != null) && !config('reservations')->isOpenType((int)$type_id) && \AC\core\ReservationsVisualizationCommon::checkLimitDayReservation($date, $on_reservation) && $period->class == 'available' && $show_form) {
      /** показывать когда:
       * 1.клиент зарегился
       * 2.если закрытые корты - type_id = 1
       * 3. В этот день можно бронировать
       * 4. Этот промежуток можно забронировать
       */
      ?>
      <span class="period-time-check" onclick="return checkedButton(this)"
            onmousedown="return backlightButton(this)">
      <img src="<?= base_url(paths()->getAssetsDir('images/check_empty.png'))?>" alt="" class="checkboxImage"/>
      <input type="hidden" name="time[<?= $period->start ?>]" value="0"/>
    </span>
    <?php } ?>
    <?php // чекбокс - конец ?>
    <?php if ($period->client != false && $period->client->visible == true) { ?>
      <div class="period-text"><?= $period->client->name ?></div>
    <?php } ?>
    <?php if ($period->class == 'available' && !$show_form) { ?>
      <span class="period-price" style="<?= ((Service::request()->_get('price') != 0) || $client_id != null || $client_bar ? 'display:inline-block'
        : '') ?>"><?= $period->price ?></span>
      <span class="period-sale"><?= $period->sale ?></span>
    <?php } ?>
  </td>
<?php } ?>