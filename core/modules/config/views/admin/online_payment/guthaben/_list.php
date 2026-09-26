<?php
/**
 * Таблица сумм пополнения счёта (Guthaben) — предложения PP.
 *
 * @var ConfigPPModel $models
 * @var string        $guthabenHref Базовый URL вкладки Guthaben из структуры админки (без action/pp_id).
 */

use AC\core\modules\config\models\ConfigPPModel;
use AC\core\system\helpers\NumberHelper;

?>
<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
  <tr>
    <th colspan="5"><?= lang('title_general_header', 'config_online_payment')?></th>
  </tr>
  <tr>
    <th><?= lang('title_sort', 'config_online_payment')?></th>
    <th><?= lang('title_amount_payment', 'config_online_payment')?> <?=CURR_VALUTE?></th>
    <th><?= lang('title_amount_payment_income', 'config_online_payment')?> <?=CURR_VALUTE?></th>
    <th colspan="2"><?= lang('title_action', 'config_online_payment')?></th>
  </tr>
  <?php foreach ($models as $model) { ?>
      <tr>
        <td class="dark" align="center"><?= $model->sort ?></td>
        <td class="light"><?= NumberHelper::format($model->price_real) ?></td>
        <td class="light"><?= NumberHelper::format($model->price_account) ?></td>
        <td class="light">
          <a href="<?= $guthabenHref ?>&action=update&pp_id=<?= $model->pp_id ?>"
             class="btnEdit"><?= lang('button_update')?></a>
        </td>
        <td class="light">
          <a href="<?= $guthabenHref ?>&action=remove&pp_id=<?= $model->pp_id ?>"
             onclick="return ifConfirm ()"
             class="btnRemove"><?= lang('button_remove')?></a>
        </td>
      </tr>
  <?php } ?>
</table>