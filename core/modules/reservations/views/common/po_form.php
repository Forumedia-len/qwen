<?php

/**
 *  переменные из Resrvations::proceedOrder
 * @var int    $sum     - Общая сумма бронирования для paypal
 * @var string $res_ids - Id бронирований разделенных |
 * @var string $pay_type - тип оплаты
 */

use AC\core\modules\payment\services\OnlineGatewayService;
use Service;
?>
<div class="paypal-load" style="text-align: center;margin-top: 20px">
  <img src="<?= cdn_url(paths()->getAssetsDir('images/ajax_loader.gif', 'common')) ?>" alt=""/><br/>
</div>
<form action="<?= site_url('payment/payone/authorization') ?>" method="post" id="payone_form">
  <input type='hidden' name='paymentType' value='reservation'/>
  <input type='hidden' name='r' value='<?= $res_ids ?>'/>
  <input type='hidden' name='h' value='<?= md5($res_ids . OnlineGatewayService::payoneConfig()->key) ?>'/>
  <input type="hidden" name="payment_data" value="<?= $res_ids . ';' . $sum ?>"/>
  <input type="hidden" name="pay_type" value="<?= $pay_type ?>"/>
  <input type="hidden" name="device_token" value="<?= htmlspecialchars((string)Service::request()->_post('device_token', '')) ?>"/>
  <input type='hidden' name='configTypeKey' value='<?= OnlineGatewayService::payoneConfig()->typeKey ?>'/>
</form>
<script>
  document.getElementById('payone_form').submit()
</script>