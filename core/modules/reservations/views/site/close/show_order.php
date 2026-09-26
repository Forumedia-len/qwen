<?php
/**
 *  Приходят переменные из Reservations::showOrder
 * @var OrdersModelReservation $model
 * @var object                 $sum                      - общаяя стоимость бронирования
 * @var array                  $light                    - выбор света , тепла, сети
 * @var array                  $webIo                    - выбор света , тепла, сети
 * @var int                    $page                     - номер страницы
 * @var array                  $options                  - литеркоды и спеццены
 * @var string                 $prepayment_sum           - Сколько денег у клиента на счете
 * @var int                    $min_rejection_days_count - количество дней для изменения бронирования
 * @var array                  $prices
 * @var View                   $this
 * @var array                  $variables                - массив переданых с переменных
 * @var bool                   $new_strategy
 * @var string                 $courtType
 * @var object                 $area_data
 *
 */

use AC\core\modules\reservations\models\OrdersModelReservation;
use AC\core\system\view\View;


?>
<form name="order" action="reservations.php" method="post">
  <?php //выводим скрытые поля и блок с заголовками ?>
  <?= $this->render('_proceed_order_hidden_input', compact($variables)) ?>
  <?= $this->render('_header_block', compact($variables)) ?>

  <?php //выводим цену заказа ?>
  <?= (!$new_strategy ? $this->render('_price_block', compact($variables)) : '') ?>

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
  <?php //блок способы оплаты ?>
  <?php if ($new_strategy) { ?>
    <div style="text-align: center" class="orderItemBox row">
      <h3 style="margin: 0">
        <strong><?= lang('Book now', 'show_order') ?> <span style="font-size: 15px;color: #000">(<?= lang(
              'According to price list',
              'show_order'
            ) ?>)</span></strong>
      </h3>
      <p><?= lang('The hall fees are invoiced by our hall office.', 'show_order') ?></p>
      <input name="prepayment" value="1" type="hidden" class="check"/>
    </div>
  <?php } else {
    echo $this->render('_payment_method_block', compact($variables));
  }
  ?>
  <?php if (!$new_strategy) { ?>
    <div class="orderItemBox">
      <p><?= lang('now_click_on_Confirm_to_complete_the_reservation', 'show_order') ?></p>
      <p><?= lang(
          'you_can_cancel_your_reservation_up_to_day_before_your_appointment',
          'show_order',
          ['min_rejection_days_count' => $min_rejection_days_count]
        ) ?></p>
    </div>
  <?php } ?>
  <?php
  if (langByAreaType('info_text_when_booking', $courtType, $area_data->type_id, [], ' ') != ' '): ?>
    <div class="orderItemBox"><?= langByAreaType('info_text_when_booking', $courtType, $area_data->type_id, [], ' ') ?></div>
  <?php endif; ?>
  <p>
    <input value="<?= $prices['text_button'] ?>" class="button show_order_button" type="submit"
           style="margin: 0 auto 0 calc(50% - 125px) !important;"/>
  </p>
</form>

