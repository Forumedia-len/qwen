<?php
/**
 * Отдельная форма выбора основного онлайн-провайдера (вкладка Guthaben).
 *
 * @var string $current
 * @var array  $gateways
 * @var string $gatewayPayone
 * @var bool   $onlinePaymentUse
 * @var string $baseHref
 */

?>
<div class="pp-online-gateway-block">
  <form action="<?= $baseHref ?>" method="post" onsubmit="return ifConfirm ()">
    <input type="hidden" name="action" value="saveGateway">
    <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">
      <tr>
        <th colspan="2"><?= lang('title_online_gateway_block', 'config') ?></th>
      </tr>
      <tr>
        <th><?= lang('title_parameter', 'config') ?></th>
        <th><?= lang('title_value', 'config') ?></th>
      </tr>
      <tr>
        <td class='dark' align='right'><?= lang('label_parameter_online_payment_use', 'config') ?></td>
        <td class="light">
          <label>
            <input type="checkbox" name="payment[online_payment_use]" value="1" <?= $onlinePaymentUse ? 'checked' : '' ?>>
            <?= lang('option_online_payment_use_on', 'config') ?>
          </label>
        </td>
      </tr>
      <tr>
        <td class="dark" align="right"><?= lang('label_parameter_online_gateway_primary', 'config') ?></td>
        <td class="light">
          <?php foreach ($gateways as $gateway => $params): ?>
          <label><input type="radio" name="payment[online_gateway_primary]" value="<?= $gateway ?>"
              <?= $current === $gateway ? 'checked' : '' ?>> <?= $params['title'] ?></label>
          &nbsp;&nbsp;
          <?php endforeach; ?>
      </tr>
      <tr>
        <th colspan="2"><input type="submit" value="<?= lang('button_save') ?>" class="button"></th>
      </tr>
    </table>
  </form>
</div>
