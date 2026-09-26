<?php
/**
 * Добавление / изменение контекста payone|… (два уровня заголовка таблицы).
 *
 * @var string     $profileBindingOptions
 * @var array|null $contextEdit
 * @var string     $gatewayHref
 */

use AC\core\modules\payment\services\OnlineGatewayService;
$isEdit         = !empty($contextEdit);
?>
<form action="<?= $gatewayHref ?>" method="post" onsubmit="return ifConfirm ()" style="margin:0">
  <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
  <?php if ($isEdit) { ?>
    <input type="hidden" name="payone_profile[configTypeKey]" value="<?= htmlspecialchars($contextEdit['typeKey']) ?>">
  <?php } ?>
  <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
    <tr>
      <th colspan="2"><?= $isEdit
          ? lang('title_payment_profile_edit', 'config_online_payment', ['provider' => 'Payone'])
          : lang('title_payment_profile_add', 'config_online_payment', ['provider' => 'Payone']) ?></th>
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
      <td class="dark" align="right"><?= lang('label_payone_mid', 'config_online_payment') ?></td>
      <td class="light">
        <input type="text" class="input wide" name="payone_profile[mid]" required
               value="<?= htmlspecialchars($isEdit ? $contextEdit['mid']['value'] : '') ?>">
      </td>
    </tr>
    <tr>
      <td class="dark" align="right"><?= lang('label_payone_aid', 'config_online_payment') ?></td>
      <td class="light">
        <input type="text" class="input wide" name="payone_profile[aid]" required
               value="<?= htmlspecialchars($isEdit ? $contextEdit['aid']['value'] : '') ?>">
      </td>
    </tr>
    <tr>
      <td class="dark" align="right"><?= lang('label_payone_portalid', 'config_online_payment') ?></td>
      <td class="light">
        <input type="text" class="input wide" name="payone_profile[portalid]" required
               value="<?= htmlspecialchars($isEdit ? $contextEdit['portalid']['value'] : '') ?>">
      </td>
    </tr>
    <tr>
      <td class="dark" align="right"><?= lang('label_payone_key', 'config_online_payment') ?></td>
      <td class="light">
        <input type="text" class="input wide" name="payone_profile[key]" value="" placeholder="<?= $contextEdit['key']['masked'] ?? '' ?>">
        <?php if ($isEdit) { ?>
          <span class="small"><?= lang('text_payment_profile_masked_keep', 'config_online_payment',
              ['field' => lang('label_payone_key', 'config_online_payment')]) ?></span>
        <?php } ?>
      </td>
    </tr>
    <tr>
      <td class="dark" align="right"><?= lang('label_payone_methods', 'config_online_payment') ?></td>
      <td class="light">
        <input type="hidden" name="payone_profile[payone_methods]" value="">
        <?php
        $opts = OnlineGatewayService::payoneConfig()->availableMethods();
        foreach ($opts as $code => $meta) {
          $checked = ($isEdit && in_array((string)$code, $contextEdit['payone_methods']['useValue'], true)) ? 'checked' : '';
          $icons   = $meta['icons'] ?? [];
          $title   = (string)($meta['title'] ?? (string)$code);
          ?>
          <label style="display:inline-block;margin-right:12px;">
            <input type="checkbox" name="payone_profile[payone_methods][]" value="<?= htmlspecialchars((string)$code) ?>" <?= $checked ?>>
            <?php if (is_array($icons) && $icons !== []) { ?>
              <?php foreach ($icons as $asset) { ?>
                <img
                  src="<?= cdn_url(paths()->getAssetsDir($asset, 'common')) ?>"
                  alt="<?= htmlspecialchars($title) ?>"
                  title="<?= htmlspecialchars($title) ?>"
                  style="margin: 0 0 -4px 0; height: 22px;"
                />
              <?php } ?>
            <?php } else { ?>
              <?= htmlspecialchars($title) ?>
            <?php } ?>
          </label>
        <?php } ?>
      </td>
    </tr>
    <tr class="payone-method-extra" data-method="bnpl" style="display:none">
      <td class="dark" align="right"><?= lang('label_payone_bnpl_df_partner_id', 'config_online_payment') ?></td>
      <td class="light">
        <input
          type="text"
          class="input wide"
          name="payone_profile[payone_method_params][bnpl][df_partner_id]"
          value=""
          placeholder="<?= $contextEdit['payone_method_params']['maskedValues']['bnpl']['df_partner_id'] ?? '' ?>"
        >
        <?php if ($isEdit && $contextEdit['payone_method_params']['useValue']['bnpl']['df_partner_id'] !== '') { ?>
          <span class="small"><?= lang('text_payment_profile_masked_keep', 'config_online_payment',
              ['field' => lang('label_payone_bnpl_df_partner_id', 'config_online_payment')]) ?></span>
        <?php } ?>
      </td>
    </tr>
    <tr class="payone-method-extra" data-method="bnpl" style="display:none">
      <td class="light" colspan="2">
        <p style="margin: 8px 0; font-size: 12px; line-height: 1.4;">
          <?= lang('text_payone_bnpl_portalid_note', 'config_online_payment',
            ['alias' => lang('option_payone_method_bnpl', 'config_online_payment')]) ?>
        </p>
      </td>
    </tr>
    <tr class="payone-method-extra" data-method="bnpl" style="display:none">
      <td class="dark" align="right"><?= lang('label_payone_bnpl_portalid', 'config_online_payment') ?></td>
      <td class="light">
        <input
          type="text"
          class="input wide"
          name="payone_profile[payone_method_params][bnpl][portalid]"
          value="<?= $contextEdit['payone_method_params']['maskedValues']['bnpl']['portalid'] ?? '' ?>"
        >
      </td>
    </tr>
    <tr class="payone-method-extra" data-method="bnpl" style="display:none">
      <td class="dark" align="right"><?= lang('label_payone_bnpl_key', 'config_online_payment') ?></td>
      <td class="light">
        <input
          type="text"
          class="input wide"
          name="payone_profile[payone_method_params][bnpl][key]"
          value=""
          placeholder="<?= $contextEdit['payone_method_params']['maskedValues']['bnpl']['key'] ?? '' ?>"
        >
        <?php if ($isEdit && $contextEdit['payone_method_params']['useValue']['bnpl']['key'] !== '') { ?>
          <span class="small"><?= lang('text_payment_profile_masked_keep', 'config_online_payment',
              ['field' => lang('label_payone_bnpl_key', 'config_online_payment')]) ?></span>
        <?php } ?>
      </td>
    </tr>
    <tr>
      <td class="dark" align="right"><?= lang('label_parameter_payment_provider_use', 'config_online_payment', ['provider' => 'Payone']) ?></td>
      <td class="light">
        <label>
          <input type="checkbox" name="payone_profile[payone_use]" value="1" <?= $isEdit && $contextEdit['payone_use']['value'] ? 'checked' : '' ?>>
        </label>
      </td>
    </tr>
    <tr>
      <td class='dark' align='right'><?= lang('Transaction Status URL', 'config_online_payment') ?></td>
      <td class="light">
        <b><?= base_url('/pay/payone_status.php') ?></b><br> <span
          style="color: red"><?= lang('Enter this URL in the appropriate field in your Payone account.', 'config_online_payment') ?></span>
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

<script>
  (function () {
    function toggleExtras() {
      const enabled = new Set()
      document.querySelectorAll('input[name="payone_profile[payone_methods][]"]').forEach(el => {
        if (el.checked) enabled.add(String(el.value).toLowerCase())
      })
      document.querySelectorAll('.payone-method-extra').forEach(tr => {
        const m = String(tr.getAttribute('data-method') || '').toLowerCase()
        tr.style.display = enabled.has(m) ? '' : 'none'
      })
    }

    document.addEventListener('change', function (e) {
      const t = e.target
      if (t && t.name === 'payone_profile[payone_methods][]') {
        toggleExtras()
      }
    })
    toggleExtras()
  })()
</script>

