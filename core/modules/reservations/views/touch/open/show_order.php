<?php
/**
 *  Приходят переменные из Reservations::showOrder
 * @var OrdersModelOpenReservation $model                    - данные площадки
 * @var object                     $sum                      - общаяя стоимость бронирования
 * @var array                      $stocks                   - наценки
 * @var array                      $sprice                   - спеццена
 * @var string                     $prepayment_sum           - Сколько денег у клиента на счете
 * @var int                        $min_rejection_days_count - количество дней для изменения бронирования
 * @var object                     $guest                    - форма гостя
 * @var array                      $clients                  - массив клиентов - используется если включена опция двойной игры
 * @var boolean                    $checkBlockGuest          - включать блокировку гостя
 * @var array                      $variables                - массив переданых с переменных
 * @var array                      $options                  - литеркоды и спеццены
 * @var array                      $webIo                    - выбор света , тепла, сети
 * @var array                      $prices
 * @var string                     $numberOfPeriods
 * @var string                     $courtType
 */


use AC\core\modules\config\models\ConfigModel;
use AC\core\modules\reservations\models\OrdersModelOpenReservation;

?>
<script>
  window.checkBlockGuest = Boolean(<?= (int)(isset($checkBlockGuest) ? $checkBlockGuest : 0)?>)
  window.errorTextBlockGuest = '<?= lang('error_block_guest_by_datetime', 'message_error') ?>'
</script>

<div class="inner-window-content">
  <form name="order" action="<?= site_url() ?>reservations.php" method="post"
        onchange="ajaxOrderFormSetPrice(this);"
        onsubmit="return checkSubmitFormOrderOpenCort()">
    <?= $this->render('_proceed_order_hidden_input', compact($variables)) ?>
    <div class="alignC">
      <?= $this->render('_header_block', compact($variables)) ?>
      <?php //выводим цену заказа ?>
      <span class="f24"><?= lang('Charge', 'show_order') ?>: <span class="sum-price sum_price"> <?= $sum->title ?> <?= CURR_VALUTE ?></span></span>
      <?php
      // выводим опции света, тепла или сети?>
      <?php if (count($webIo) > 0) { ?>
        <?= $this->render('../_webIo', ['webIo' => $webIo]); ?>
      <?php } ?>

      <!---->
      <!--      <br>-->
      <!--      <div style=" margin-right:10px;"><?= lang('Comment') ?>:</div>-->
      <!--      <textarea name="memo" value="" type="text" class="memo"></textarea>-->
      <!--      <br>-->
      <?php // выбор игроков ?>
      <?= $this->render('_choice_players', compact($variables)); ?>
      <?= $numberOfPeriods ?>
      <?php if ($options['use_options']) { ?>
        <div class='orderItemBox row' style="display: flex;">
          <?php foreach ($options['data'] as $option) {
            echo $this->render('_options', ['priceOptionsBlock' => $option]);
          } ?>
        </div>
      <?php } ?>
      <?php //блок способы оплаты ?>
      <h3><?= lang('select_a_payment_method', 'show_order') ?>:</h3>
      <?= $this->render('_payment_method_block', compact($variables)); ?>
      <p>
        <?= langByAreaType('info_text_when_booking', $courtType, $area_data->type_id) ?>
      </p>
      <p>
        <input type="submit" name="go"
               value="<?= lang('show_order_button_submit', 'show_order', ['sum_price' => $sum->title, 'currency' => CURR_VALUTE]) ?>"
               class="big-button show_order_button" onclick=""/>
      </p>
    </div>
  </form>
</div>
