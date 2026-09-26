<?php
/**
 * Добавление / изменение контекста paypal|… (два уровня заголовка таблицы).
 *
 * @var string $profileBindingOptions
 * @var array|null $contextEdit
 * @var string $gatewayHref
 */
$isEdit = !empty($contextEdit);
?>
<form action="<?= $gatewayHref ?>" method="post" onsubmit="return ifConfirm ()" style="margin:0">
  <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
  <?php if ($isEdit) { ?>
    <input type="hidden" name="paypal_profile[configTypeKey]" value="<?= htmlspecialchars($contextEdit['typeKey']) ?>">
  <?php } ?>
  <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
    <tr>
      <th colspan="2"><?= $isEdit
          ? lang('title_payment_profile_edit', 'config_online_payment', ['provider' => 'PayPal'])
          : lang('title_payment_profile_add', 'config_online_payment', ['provider' => 'PayPal']) ?></th>
    </tr>
    <tr>
      <td class="dark" align="right"><?= lang('title_profile_binding', 'config_online_payment') ?></td>
      <td class="light">
        <?php if ($isEdit) { ?>
          <?= htmlspecialchars($contextEdit['bindingLabel']) ?>
        <?php } else { ?>
          <?= $profileBindingOptions ?>
        <?php } ?>
      </td>
    </tr>
    <tr>
      <td class="dark" align="right"><?= lang('label_parameter_api_username', 'config_online_payment') ?></td>
      <td class="light">
        <input type="text" class="input wide" name="paypal_profile[api_username]" required
               value="<?= htmlspecialchars($isEdit ? $contextEdit['api_username']['value'] : '') ?>">
      </td>
    </tr>
    <tr>
      <td class="dark" align="right"><?= lang('label_parameter_api_password', 'config_online_payment') ?></td>
      <td class="light">
        <input type="text" class="input wide" name="paypal_profile[api_password]" value="" placeholder="">
        <?php if ($isEdit) { ?>
          <span class="small"><?= lang('text_payment_profile_masked_keep', 'config_online_payment', ['field' => lang('label_parameter_api_password', 'config_online_payment')]) ?></span>
        <?php } ?>
      </td>
    </tr>
    <tr>
      <td class="dark" align="right"><?= lang('label_parameter_api_signature', 'config_online_payment') ?></td>
      <td class="light">
        <input type="text" class="input wide" name="paypal_profile[api_signature]" value="" placeholder="<?= htmlspecialchars($contextEdit['api_signature']['masked'] ?? '') ?>">
        <?php if ($isEdit) { ?>
          <span class="small"><?= lang('text_payment_profile_masked_keep', 'config_online_payment', ['field' => lang('label_parameter_api_signature', 'config_online_payment')]) ?></span>
        <?php } ?>
      </td>
    </tr>
    <tr>
      <td class="dark" align="right"><?= lang('label_parameter_payment_provider_use', 'config_online_payment', ['provider' => 'PayPal']) ?></td>
      <td class="light">
        <label>
          <input type="checkbox" name="paypal_profile[paypal_use]" value="1" <?= $isEdit && $contextEdit['paypal_use']['value'] ? 'checked' : '' ?>>
        </label>
      </td>
    </tr>
    <tr>
      <th align="center" colspan="2">
        <input type="submit" value="<?= $isEdit ? lang('button_update') : lang('button_create') ?>" class="button">
        &nbsp;
        <?php if ($isEdit) { ?>
          <a href="<?= htmlspecialchars($gatewayHref) ?>" class="button"><?= lang('button_reset') ?></a>
        <?php } else { ?>
          <input type="reset" value="<?= lang('button_reset') ?>" class="button">
        <?php } ?>
      </th>
    </tr>
  </table>
</form>
