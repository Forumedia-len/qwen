<?php
/**
 * @var int    $client_id
 * @var array  $items
 * @var string $action
 * @var string $methods
 */

use AC\core\modules\payment\services\OnlineGatewayService;

?>
<div class="orderItemBox">
  <form action="<?= $action ?>" method="post">
    <input type='hidden' name='paymentType' value='AccountReplenishment'/>
    <input type='hidden' name='r' value='<?= $client_id ?>'/>
    <input type='hidden' name='h' value='<?= md5($client_id . OnlineGatewayService::payoneConfig()->key) ?>'/>
    <?= $methods ?>
    <?php $i = 0; ?>
    <ul>
      <?php foreach ($items as $item) { ?>
        <li>
          <input type="radio" name="payment_data" value="<?= $item->pp_id . ';' . $item->price_real ?>"<?= ($i == 0 ? 'checked' : '') ?>
                 class="radio" id="item-pp-<?= $item->pp_id ?>"/>
          <span class='podlog'></span>
          <label for="item-pp-<?= $item->pp_id ?>"> <span class="text-item"> <?= lang('paymentAmount', 'payment_pp') ?> <strong><?=
                $item->price_real_title ?></strong> <?= $item->valute ?> -&#187; <?= lang('transferAmount', 'payment_pp') ?> <strong
                class="green"><?= $item->price_account_title ?></strong>
              <?= $item->valute ?></span>
          </label>
        </li>
        <?php $i++;
      } ?>
    </ul>
    <input type="submit" name="go" value="<?= lang('send_request', 'payment_pp') ?>" class="button orderButton"/>
  </form>
</div>