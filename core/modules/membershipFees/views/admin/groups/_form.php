<?php
/**
 * @var MembershipFeesGroupsModel $model
 */

use AC\core\modules\membershipFees\MembershipFeesModule;
use AC\core\modules\membershipFees\models\MembershipFeesGroupsModel;
use AC\core\system\helpers\NumberHelper;

?>
<form action="<?= MembershipFeesModule::appendPath($baseUrl, $model->id ? 'update' : 'create') ?>" method="post">
  <input type="hidden" name="id" value="<?= $model->id ?? '' ?>">
  <input type="hidden" name="active" value="<?= $model->active === null ? 1 : $model->active ?>">
  <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
    <tr>
      <th colspan="2"><?= lang('title_' . ($model->id ? 'update' : 'create')) ?></th>
    </tr>
    <tr>
      <td class="dark"><?= $model->getAttributeLabel('title') ?>:</td>
      <td class="dark">
        <input type='text' class='input wide' name='title' value="<?= $model->title ?? '' ?>">
      </td>
    </tr>
    <tr>
      <td class="dark"><?= $model->getAttributeLabel('rate') ?>:</td>
      <td class="dark"><input type="text" name="rate" class="input" value="<?= NumberHelper::format($model->rate ?? 0) ?>"/> <?= CURR_VALUTE ?>
      </td>
    </tr>
    <tr>
      <td class="dark"><?= $model->getAttributeLabel('inclusion_age') ?>:</td>
      <td class="dark"><input type="text" name="inclusion_age" class="input" value="<?=  $model->inclusion_age ? : '' ?>"/>
      </td>
    </tr>
    <tr>
      <th align="center" colspan="2">
        <input type="submit" value="<?= lang('button_' . ($model->id ? 'update' : 'create')) ?>"
               class="button">
        &nbsp;
        <input type="reset" value="<?= lang('button_reset') ?>" class="button">
      </th>
    </tr>
  </table>
</form>
