<?php
/**
 * Список PayPal-профилей из `config` (type = `paypal` и `paypal|…`).
 *
 * @var list<array{typeKey:string,bindingLabel:string,api_username:string,sigDisp:string,paypal_use:int,allow_remove:bool}> $contextRows
 * @var string $gatewayHref
 */

?>
<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
  <tr>
    <th colspan="5"><?= lang('title_payment_provider_credentials_header', 'config_online_payment', ['provider' => 'PayPal']) ?></th>
  </tr>
  <tr>
    <th><?= lang('title_profile_binding', 'config_online_payment') ?></th>
    <th><?= lang('label_parameter_api_username', 'config_online_payment') ?></th>
    <th><?= lang('label_parameter_api_signature', 'config_online_payment') ?></th>
    <th><?= lang('label_parameter_payment_provider_use', 'config_online_payment', ['provider' => 'PayPal']) ?></th>
    <th><?= lang('title_action', 'config_online_payment') ?></th>
  </tr>
  <?php foreach ($contextRows as $row) { ?>
    <tr>
      <td class="dark">
        <?= htmlspecialchars($row['bindingLabel']) ?>
      </td>
      <td class="light"><?= htmlspecialchars($row['api_username']['value']) ?></td>
      <td class="light"><?= htmlspecialchars($row['api_signature']['masked']) ?></td>
      <td class="dark" align="center">
        <a href="<?= htmlspecialchars($gatewayHref) ?>&action=active&active=<?= (int)(!$row['paypal_use']['value']) ?>&config_type=<?= rawurlencode($row['typeKey']) ?>">
          <?= $row['paypal_use']['value']
            ? '<i class="fas fa-check" style="color: green"></i>'
            : '<i class="fa fa-ban fa-rotate-90" aria-hidden="true"  style="color: red"></i>' ?>
        </a>
      </td>
      <td class="light" nowrap>
        <a href="<?= htmlspecialchars($gatewayHref) ?>&action=update&config_type=<?= rawurlencode($row['typeKey']) ?>"
           class="btnEdit"><?= lang('button_update') ?></a>
        <?php if (!empty($row['allow_remove'])) { ?>
          &nbsp;
          <a href="<?= htmlspecialchars($gatewayHref) ?>&action=remove&config_type=<?= rawurlencode($row['typeKey']) ?>"
             onclick="return ifConfirm ()"
             class="btnRemove"><?= lang('button_remove') ?></a>
        <?php } ?>
      </td>
    </tr>
  <?php } ?>
</table>
