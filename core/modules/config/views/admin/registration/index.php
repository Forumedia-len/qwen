<?php
/**
 * @var ConfigModel $models
 * @var View        $this
 */

use AC\core\system\helpers\ArrayHelper;

use AC\core\system\view\View;
use AC\core\modules\config\models\ConfigModel;

useClass('core\helpers\ArrayHelper');
?>
<form action="config.php?mode=registration&action=saveRegistrationFields" method="post" onsubmit="return ifConfirm ()">
  <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">
    <tr>
      <th rowspan="2" colspan="2"><?= lang('title_parameter', 'config') ?></th>
      <th colspan="2"><?= lang('title_value', 'config') ?></th>
    </tr>
    <tr>
      <th><?= lang('title_show_field', 'config_registration_fields') ?></th>
      <th><?= lang('title_required_field', 'config_registration_fields') ?></th>
    </tr>
    <?php foreach ($models as $groupKey => $group) { ?>
      <?php foreach ($group as $key => $model) { ?>
        <tr>
          <?php if (ArrayHelper::keyFirstArray($group) == $key) { ?>
            <td class="dark" rowspan="<?= count($group) ?>"><strong><?= lang(
                'parameter_registration_field_group_' . $groupKey,
                'registration_fields'
                ) ?></strong></td>
          <?php } ?>
          <td class="dark"
              align="right" ><?= lang(
              'parameter_registration_field_' . $model->name,
              'registration_fields'
            ) ?></td>
          <td class="light" align="center">
            <input type="checkbox" name="<?= $model->name . '[show]' ?>" value="1" <?= ($model->show ?
              ' checked' : '') ?>>
          </td>
          <td class="dark" align="center">
            <?php if (!in_array($model->type, array('radio', 'date'))) { ?>
              <input type="checkbox" name="<?= $model->name . '[required]' ?>" value="1"
                     <?= ($model->required ?
                ' checked' : '') ?>>
            <?php } ?>
          </td>
        </tr>
      <?php } ?>
    <?php } ?>
    <tr>
      <th colspan="4"><input type="submit" value="<?= lang('button_save') ?>" class="button"></th>
    </tr>
  </table>
</form>

