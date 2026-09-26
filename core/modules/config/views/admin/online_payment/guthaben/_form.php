<?php
/**
 * @var ConfigPPModel $model
 */


use AC\core\modules\config\models\ConfigPPModel;
use AC\core\system\helpers\NumberHelper;

?>
<form action="<?= Service::structure()->getPageHrefByKey('config_online_payment_tab_guthaben') ?>&action=<?= $model->pp_id === null ? 'create' : 'update' ?><?= $model->pp_id !== null ? '&pp_id=' . (int)$model->pp_id : '' ?>"
      method="post">
  <input type="hidden" name="mode" value="online_payment">
  <input type="hidden" name="tab" value="guthaben">
  <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
    <tr>
      <th colspan="2"><?= lang('title_general_header', 'config_online_payment')?></th>
    </tr>
    <tr>
      <th colspan="2"><?= lang('title_'.($model->pp_id === null ? 'create' : 'update'), 'config_online_payment') ?></th>
    </tr>
    <tr>
      <td class="dark"><?= lang('title_sort', 'config_online_payment')?>:</td>
      <td class="dark"><input type="text" name="sort" class="input wide" value="<?= $model->sort ?>"/></td>
    </tr>
    <tr>
      <td class="light"><?= lang('title_amount_payment', 'config_online_payment')?> <?=CURR_VALUTE?>:</td>
      <td class="dark"><input type="text" name="price_real" class="input wide"
                              value="<?= NumberHelper::format($model->price_real ?? 0) ?>"/></td>
    </tr>
    <tr>
      <td class="light"><?= lang('title_amount_payment_income', 'config_online_payment')?> <?=CURR_VALUTE?>:</td>
      <td class="dark"><input type="text" name="price_account" class="input wide"
                              value="<?=  NumberHelper::format($model->price_account ?? 0) ?>"/></td>
    </tr>
    <tr>
      <th align="center" colspan="2">
        <input type="submit" value="<?= lang('button_' . ($model->pp_id === null ? 'create' : 'update')) ?>" class="button">
        &nbsp;
        <input type="reset" value="<?= lang('button_reset') ?>" class="button">
      </th>
    </tr>
  </table>
</form>
