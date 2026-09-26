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
 * @var object                     $area_data
 */

use AC\core\modules\reservations\models\OrdersModelOpenReservation;


?>
<script>
  window.checkBlockGuest = Boolean(<?= (int)(isset($checkBlockGuest) ? $checkBlockGuest : 0)?>)
  window.errorTextBlockGuest = '<?= lang('error_block_guest_by_datetime', 'message_error') ?>'
</script>
<form name="order" action="reservations.php" method="post"
      onsubmit="return checkSubmitFormOrderOpenCort()">
  <?= $this->render('_proceed_order_hidden_input', compact($variables)) ?>
  <?= $this->render('_header_block', compact($variables)) ?>

  <?php //выводим цену заказа ?>
  <?= $this->render('_price_block', compact($variables)) ?>

  <?php // выводим опции света, тепла или сети?>
  <?= $this->render('_webIo', ['webIo' => $webIo]); ?>

  <?php //блок коментариев ?>
  <?= $this->render('_comment_block'); ?>

  <?php //блок поля ввода коментария ?>
  <?= $this->render('_memo_block'); ?>

  <?php // выводим опции и спец цены?>
  <?php if ($options['use_options']) { ?>
    <div class="orderItemBox row">
      <?php foreach ($options['data'] as $option) {
        echo $this->render('_options', ['priceOptionsBlock' => $option]);
      } ?>
    </div>
  <?php } ?>
  <?= $numberOfPeriods ?>
  <?php // выбор игроков ?>
  <?= $this->render('_choice_players', compact($variables)); ?>

  <?php //блок способы оплаты ?>
  <?= $this->render('_payment_method_block', compact($variables)); ?>

  <br>
  <?php if (langByAreaType('info_text_when_booking', $courtType, $area_data->type_id, [], ' ') != ' '): ?>
    <div class="orderItemBox"><?= langByAreaType('info_text_when_booking', $courtType, $area_data->type_id, [], ' ') ?></div><?php endif; ?>

  <p>
    <input value="<?= $prices['text_button'] ?>" class="button show_order_button" type="submit" style="margin: 0 auto!important;">
  </p>
</form>

