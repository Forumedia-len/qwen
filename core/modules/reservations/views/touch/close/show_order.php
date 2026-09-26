<?php
/**
 *  Приходят переменные из Reservations::showOrder
 * @var OrdersModelReservation $model                    -
 * @var AreasModel             $area_data                - данные площадки
 * @var object                 $date                     - (дата , день недели , временные промежутки)
 * @var object                 $sum                      - общаяя стоимость бронирования
 * @var array                  $webIo                    - выбор света , тепла, сети
 * @var array                  $options                  - литеркоды и спеццены
 * @var int                    $page                     - номер страницы
 * @var array                  $stocks                   - наценки
 * @var array                  $sprice                   - спеццена
 * @var string                 $prepayment_sum           - Сколько денег у клиента на счете
 * @var int                    $min_rejection_days_count - количество дней для изменения бронирования
 * @var array                  $light_times              - выбор света , тепла, сети зависит по времени
 * @var array                  $prices
 * @var array                  $variables                - массив переданых с переменных
 * @var bool                   $new_strategy
 * @var string                 $courtType                - массив переданых с переменных
 */


use AC\core\modules\areas\models\AreasModel;
use AC\core\modules\reservations\models\OrdersModelReservation;

?>
<form name="order" action="<?= site_url() ?>reservations.php" method="post" class="form_order">
  <?php //выводим скрытые поля и блок с заголовками ?>
  <?= $this->render('_proceed_order_hidden_input', compact($variables)) ?>
  <table class="res_con">
    <tr>
      <td>
        <?= $this->render('_header_block', compact($variables)) ?>
      </td>
    </tr>
    <tr>
      <td>
        <div>
          <?php if ($new_strategy) { ?>
            <div style="text-align: center" class="orderItemBox row">
              <h3 style="margin: 0">
                <strong><?= lang('Book now', 'show_order') ?> <span style="font-size: 15px;color: #000">(<?= lang('According to price list',
                      'show_order') ?>)</span></strong>
              </h3>
              <p><?= lang('The hall fees are invoiced by our hall office.', 'show_order') ?></p>
              <input name="prepayment" value="1" type="hidden" class="check"/>
            </div>
          <?php } else { ?>
            <?php //выводим цену заказа ?>
            <?php if ($model->barClient) { ?>
              <?= langByAreaType('info_text_before_guest_booking_without_login', $courtType, $area_data->type_id, ['price' => $prices['sum_price']]) ?>
            <?php } elseif ($model->current_client->encash == 0) { ?>
              <?= langByAreaType('info_text_before_booking_cash_payer', $courtType, $area_data->type_id, ['price' => $prices['sum_price']]) ?>
            <?php } else {
              echo langByAreaType('info_text_before_booking_invoice', $courtType, $area_data->type_id, ['price' => $prices['sum_price']]);
            }
          } ?>
        </div>
        <div style="margin-top: 7px">
          <?php // выводим опции света, тепла или сети ?>
          <?php if (count($webIo) > 0) { ?>
            <?= $this->render('_webIo', ['webIo' => $webIo]); ?>
          <?php } ?>
        </div>
        <?php // акциии ?>
        <div class="divider"></div>
        <div id="reservationForm">
          <?php if ($options['use_options']) { ?>
            <?php if (!empty($options['data']['stock'])) { ?>
              <div class="dop_inform opts" id="stock_block">
                <?= $this->render('_options', ['priceOptionsBlock' => $options['data']['stock']]) ?>
              </div>
            <?php } ?>
            <?php if (!empty($options['data']['sprice'])) { ?>
              <div class="dop_inform sond" id="sprice_block">
                <?= $this->render('_options', ['priceOptionsBlock' => $options['data']['sprice']]) ?>
              </div>
            <?php } ?>
          <?php } ?>
          <?php if (!$new_strategy) { ?>
            <?php //блок способы оплаты ?>
            <?= $this->render('_payment_method_block', compact($variables)); ?>
          <?php } ?>
          <div class="clear"></div>
        </div>
        <div class="clear"></div>
        <div class="divider"></div>
        <div style="margin:20px auto 10px auto; width:1000px;">
          <table class="commentik" style="margin:20px auto 10px auto; width:704px;">
            <tr>
              <td style="vertical-align: middle"><?= lang('comment') ?>:</td>
              <td><input name="memo" value="" title="Vorname" type="text" class="memo loadKeyboard"></td>
              <td>
            </tr>
          </table>
          <div><?= langByAreaType('info_text_when_booking', $courtType, $area_data->type_id, [], ' ') ?></div>
          <table class="commentik" style="margin:20px auto 10px auto; width: 335px;">
            <tr>
              <td><input type="submit" value="<?= $prices['text_button'] ?>" class="button show_order_button" style="height: 65px;"></td>
              <td>
                <a
                  href="<?= site_url() ?>reservations.php?action=showReservations&type_id=<?= $model->type_id ?>&sport_id=<?= $model->sport_id ?>&date=<?= $model->date ?>&page=<?= $model->page ?>&area_id=<?= $model->area_id ?>"
                  class="button button-back"><?= lang('Back') ?></a>
              </td>
            </tr>
          </table>
        </div>
      </td>
    </tr>
    <tr>
      <td></td>
    </tr>
  </table>
</form>

