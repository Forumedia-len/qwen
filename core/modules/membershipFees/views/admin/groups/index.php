<?php
/**
 * @var BaseObject     $models
 * @var MembershipFeesGroupsTable $model
 * @var string $baseUrl
 */


use AC\core\modules\membershipFees\MembershipFeesModule;
use AC\core\modules\membershipFees\tables\MembershipFeesGroupsTable;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\object\BaseObject;

?>
<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
  <tr>
    <th><?= lang('label_title', 'membership_fees_groups') ?></th>
    <th><?= lang('label_rate', 'membership_fees_groups') ?></th>
    <th><?= lang('label_inclusion_age', 'membership_fees_groups') ?></th>
    <th colspan="2"><?= lang('title_action') ?></th>
  </tr>

  <?php if ($models) {
    foreach ($models as $model) { ?>
      <tr>
        <td class="dark"><?= $model->title ?></td>
        <td class="dark"><?= NumberHelper::format($model->rate ?? 0) ?> <?= CURR_VALUTE?></td>
        <td class="light"><?= $model->inclusion_age ? : ''?></td>
        <td class="dark">
          <a href="<?= MembershipFeesModule::appendPath($baseUrl, 'update/id/' . $model->id) ?>" class="btnEdit">
            <?= lang('button_update') ?>
          </a>
        </td>
<!--        <td class="light">-->
<!--          <a href="--><?php //= $baseUrl?><!--/remove/id/--><?php //= $model->id ?><!--"-->
<!--             onclick="return ifConfirm ()"-->
<!--             class="btnRemove">--><?php //= lang('button_remove')?><!--</a>-->
<!--        </td>-->
      </tr>
      <?php
    }
  } ?>
</table>
