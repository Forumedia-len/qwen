<?php
/**
 *  переменные из Resrvations::proceedOrder
 * @var int          $sum     - Общая сумма бронирования для paypal
 * @var string       $res_ids - Id бронирований разделенных |
 */

use AC\core\modules\payment\services\OnlineGatewayService;

?>
<div class="paypal-load">
  <img src="<?= cdn_url(paths()->getAssetsDir('images/ajax_loader.gif', 'common')) ?>" alt=""/><br/><?= lang('Loading paypal')?>
</div>
<form action="<?= site_url('payment/paypal/'. OnlineGatewayService::paypalConfig()->useVersion .'/checkDetails') ?>" method="post" id="paypal_form">
  <input type="hidden" name="typeDetails" value="payPerGame"/>
  <input type="hidden" name="payment_data" value="<?= $res_ids . ';' . $sum ?>"/>
  <input type="hidden" name="configTypeKey" value="<?= OnlineGatewayService::paypalConfig()->typeKey ?>"/>
</form>
<script>
    document.getElementById('paypal_form').submit()
</script>