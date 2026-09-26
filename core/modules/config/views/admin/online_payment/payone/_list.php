<?php
/**
 * Список Payone-профилей из `config` (type = `payone` и `payone|…`).
 *
 * @var list<array{typeKey:string,bindingLabel:string,mid:array,aid:array,portalid:array,key:array,payone_use:array,allow_remove:bool}> $contextRows
 * @var string $gatewayHref
 */

?>
<div>
  <b><?= lang('Transaction Status URL', 'config_online_payment') ?></b>:  <b><?= base_url('/pay/payone_status.php')?></b>
  <br> <span style="color: red"><?= lang('Enter this URL in the appropriate field in your Payone account.', 'config_online_payment')?></span>
</div>
<hr>
<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
  <tr>
    <th colspan="7"><?= lang('title_payment_provider_credentials_header', 'config_online_payment', ['provider' => 'Payone']) ?></th>
  </tr>
  <tr>
    <th><?= lang('title_profile_binding', 'config_online_payment') ?></th>
    <th><?= lang('label_payone_mid', 'config_online_payment') ?></th>
    <th><?= lang('label_payone_aid', 'config_online_payment') ?></th>
    <th><?= lang('label_payone_portalid', 'config_online_payment') ?></th>
    <th><?= lang('label_parameter_payment_provider_use', 'config_online_payment', ['provider' => 'Payone']) ?></th>
    <th><?= lang('title_action', 'config_online_payment') ?></th>
  </tr>
  <?php foreach ($contextRows as $row) { ?>
    <tr>
      <td class="dark">
        <?= htmlspecialchars($row['bindingLabel']) ?>
      </td>
      <td class="light"><?= htmlspecialchars($row['mid']['value']) ?></td>
      <td class="light"><?= htmlspecialchars($row['aid']['value']) ?></td>
      <td class="light"><?= htmlspecialchars($row['portalid']['value']) ?></td>
      <td class="dark" align="center">
        <a href="<?= htmlspecialchars($gatewayHref) ?>&action=active&active=<?= (int)(!$row['payone_use']['value']) ?>&config_type=<?= rawurlencode($row['typeKey']) ?>">
          <?= $row['payone_use']['value']
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