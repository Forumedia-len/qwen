<?php
/**
 *  Приходят переменные из Reservations::showOrder
 *
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
 *
 */

use AC\app\entities\enums\Encash;
use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\modules\reservations\entities\PaymentMethodContext;
use AC\core\modules\reservations\models\OrdersModelReservation;
use AC\core\modules\reservations\services\PaymentMethodResolver;
use AC\core\system\view\View;

$paymentMethods = (new PaymentMethodResolver(config('payment')))->resolve(
  new PaymentMethodContext(
    typeId: (int)$model->type_id,
    sportId: (int)$model->sport_id,
    clientMethod: Encash::from($model->current_client->encash),
    barClient: (bool)$model->barClient,
    price: (float)$sum->sum_price,
    privateAccountBalance: (float)$prepayment_sum,
    channel: 'site'
  )
);

?>
<div class="payment-method-block">
  <h3><?= lang('select_a_payment_method', 'show_order') ?>: </h3>
  <?php // платим наличкой ?>
  <?php if ($paymentMethods->isVisible(Encash::Cash)) { ?>
    <div class="orderItemBox">
      <h4><?= lang('cash_payers', 'show_order') ?>:</h4>
      <p class="display-right display-block">
        <input name="prepayment" value="0" type="radio" class="check"
          <?= $paymentMethods->checked(Encash::Cash) ?>
               id="item-order-bar"/>
        <span class="podlog2"></span>
        <label for="item-order-bar"><strong><?= lang('cash_payment', 'show_order') ?></strong></label>
      </p>
    </div>
    <div class="clear"></div>
  <?php } ?>
  <?php // платим по счету ?>
  <?php if ($paymentMethods->isVisible(Encash::Invoice)) { ?>
      <div class="orderItemBox">
        <p><?= lang('description_of_payment_from_a_invoice', 'show_order') ?></p>
        <h4><?= lang('invoice', 'show_order') ?>:</h4>
        <p class="display-right display-block">
          <input name="prepayment" value="1" type="radio" class="check" id="item-order-re"
            <?= $paymentMethods->checked(Encash::Invoice) ?>
          />
          <span class="podlog2"></span>
          <label for="item-order-re"><strong><?= lang('on_bill', 'show_order') ?></strong></label>
        </p>
      </div>
      <div class="clear"></div>
  <?php } ?>

  <?php // платим с лицевого счета ?>
  <?php if ($paymentMethods->isVisible(Encash::PrivateAccount)) { ?>
      <div class="orderItemBox">
        <h4><?= lang('your_online_credit', 'show_order') ?>:</h4>
        <p class="display-right display-block pay-guthaben">
          <?php if ($paymentMethods->isSelectable(Encash::PrivateAccount)) { ?>
            <input name="prepayment" value="2" type="radio" id="item-order-gh"
                   class="check" <?= $paymentMethods->checked(Encash::PrivateAccount) ?>
            />
            <span class="podlog2"></span>
          <?php } ?>
          <label for="item-order-gh">
            <?php if (!isset($model->current_client->show_client_data) || $model->current_client->show_client_data): ?>
              <strong><?= $prepayment_sum ?> <?= CURR_VALUTE ?></strong> <?= lang('click_here_to_top_up_your_credit',
                'show_order',
                ['action' => 'prepayment.php']) ?>
            <?php else: ?>
              <?= lang('Private account payment') ?>
            <?php endif; ?>
          </label>
        </p>
        <p class="display-bottom">
          <em><?= lang('description_of_payment_from_a_personal_account', 'show_order') ?></em>
        </p>
      </div>
  <?php } ?>
  <div class="clear"></div>

  <?php // онлайн оплата - доработать чтобы эти варианты шаблонов брались из способов оплаты?>
  <?php // платим с paypal ?>
  <?php if ($paymentMethods->isVisible('PP')) { ?>
    <div class="orderItemBox order-pp" <?php if (!$paymentMethods->isSelectable('PP')): ?>style="display: none"<?php endif ?> data-curr="<?= CURR_VALUTE ?>">
      <h4><?= lang('paypal', 'show_order') ?>:</h4>
      <p class="display-right display-block">
        <input name="prepayment" value="3" type="radio" id="item-order-pp"
               class="check" <?= $paymentMethods->checked('PP') ?>/>
        <span class="podlog2"></span>
        <label for="item-order-pp">
          <img src="<?= cdn_url(paths()->getAssetsDir('images/paypal_logo.png', 'common')) ?>" alt="PayPal" style="margin-bottom:-9px;"/>
        </label>
      </p>
      <p class="display-bottom">
        <?php if (!$model->barClient) { ?>
          <em><?= lang('description_of_payment_from_a_paypal', 'show_order') ?></em>
        <?php } ?>
      </p>
    </div>
    <div class="clear"></div>
  <?php } ?>
  <?php // платим с payone ?>
  <?php if ($paymentMethods->isVisible('PO')) { ?>
    <div class="orderItemBox order-pp" <?php if (!$paymentMethods->isSelectable('PO')): ?>style="display: none"<?php endif ?> data-curr="<?= CURR_VALUTE ?>">
      <h4 style="float: none;width: 100%"><?= lang('pay_payone') ?>:</h4>
      <?= OnlineGatewayService::payoneConfig()->renderMethods('order_site', $sum->sum_price); ?>
    </div>
    <div class="clear"></div>
  <?php } ?>
</div>
