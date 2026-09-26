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
    channel: 'touch'
  )
);
?>
<div class="dop_inform guth">
  <?php // платим наличкой ?>
  <?php if ($paymentMethods->isVisible(Encash::Cash)) { ?>
    <span class="zag"><?= lang('cash_payers', 'show_order') ?>:</span>
    <table class="items">
      <tr>
        <td><input name="prepayment" value="0" type="radio" class="check" <?= $paymentMethods->checked(Encash::Cash) ?>
                   id='item-order-bar'/>
          <span class="podlog2"></span>
        </td>
        <td><label for='item-order-bar'> <?= lang('cash_payment', 'show_order') ?></label></td>
      </tr>
    </table>
  <?php } ?>
  <?php // платим по счету ?>
  <?php if ($paymentMethods->isVisible(Encash::Invoice)) { ?>
      <span class="zag"><?= lang('invoice', 'show_order') ?>:</span>
      <table class="items">
        <tr>
          <td><input name="prepayment" value="1" type="radio" class="check"
                     id='item-order-re' <?= $paymentMethods->checked(Encash::Invoice) ?>/>
            <span class="podlog2"></span>
          </td>
          <td class="td-item-label"><label for='item-order-re'> <?= lang('on_bill', 'show_order') ?></label></td>
        </tr>
      </table>
      <?php
  } ?>
  <?php // платим с лицевого счета ?>
  <?php if ($paymentMethods->isVisible(Encash::PrivateAccount)) { ?>
      <span class="zag"><?= lang('Your credit', 'show_order') ?>:</span>
      <?php
    if ($paymentMethods->isSelectable(Encash::PrivateAccount)) { ?>
        <span class="font20"><?= lang('Please tick the box if the amount is to be paid from your credit account.', 'show_order') ?></span>
        <br/>
        <table class="items">
          <tr>
            <td><input name="prepayment" value="2" type="radio" id='item-order-gh'
                       class="check" <?= $paymentMethods->checked(Encash::PrivateAccount) ?>/>
              <span class="podlog2"></span>
            </td>
            <td class="td-item-label">
              <label for="item-order-gh">
                <?php if (!isset($model->current_client->show_client_data) || $model->current_client->show_client_data): ?>
                  <strong><?= $prepayment_sum ?> <?= CURR_VALUTE ?></strong>
                <?php else: ?>
                  <?= lang('Private account payment') ?>
                <?php endif; ?>
              </label>
            </td>
          </tr>
        </table>
        <?php
      } else { ?>
        <span class="font20"><?= lang('You have on your credit account', 'show_order', ['currency' => CURR_VALUTE]) ?></span>
      <?php } ?>
  <?php } ?>

  <?php // онлайн оплата - доработать чтобы эти варианты шаблонов брались из способов оплаты?>
  <?php // платим с paypal ?>
  <?php if ($paymentMethods->isVisible('PP')) { ?>
    <div class="order-pp" <?php if (!$paymentMethods->isSelectable('PP')): ?>style="display: none"<?php endif; ?> data-curr="<?= CURR_VALUTE ?>">
      <input type="hidden" name="ajaxPayPal" value="1"/>
      <span class="zag"><?= lang('PayPal', 'show_order') ?>:</span>
      <?php if (!$model->barClient) { ?>
        <?= lang('Please tick the box if you want to transfer the amount via PayPal want to pay. You will then be forwarded to PayPal.',
          'show_order') ?>
        <br>
      <?php } ?>
      <table class="items">
        <tr>
          <td>
            <input name="prepayment" value="3" type="radio" id='item-order-pp'
                   class="check" <?= $paymentMethods->checked('PP') ?>/>
            <span class="podlog2"></span>
          </td>
          <td class="td-item-label">
            <label for='item-order-pp'>
              <img src="<?= cdn_url(paths()->getAssetsDir('images/paypal_logo.png', 'common')) ?>" alt="PayPal"
                   style="margin-bottom:-9px;width: 99px;"/>
            </label>
          </td>
        </tr>
      </table>
    </div>
  <?php } ?>
  <?php // платим с payone ?>
  <?php if ($paymentMethods->isVisible('PO')) { ?>
    <div class="order-pp" <?php if (!$paymentMethods->isSelectable('PO')): ?>style="display: none"<?php endif; ?> data-curr="<?= CURR_VALUTE ?>">
      <input type="hidden" name="ajaxPayPal" value="1"/>
      <span class="zag"><?= lang('pay_payone') ?>:</span>
      <table class="items">
        <?= OnlineGatewayService::payoneConfig()->renderMethods('order_touch', $sum->sum_price); ?>
      </table>
    </div>
  <?php } ?>
</div>
