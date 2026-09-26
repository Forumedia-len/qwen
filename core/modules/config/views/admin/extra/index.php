<?php
/**
 * @var BaseObject       $models
 * @var ConfigExtraModel $model
 * @var View             $this
 */


use AC\core\system\object\BaseObject;
use AC\core\system\view\View;
use AC\core\modules\config\models\ConfigExtraModel;

?>
<div style="text-align: center">
  <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
    <tr>
      <th><?= lang('title_type', 'config_extra') ?></th>
      <?php foreach ($models->{0}->rate as $rate) { ?>
        <th><?= $rate->title ?></th>
      <?php } ?>
      <th><?= lang('title_action', 'config_extra') ?></th>
    </tr>

    <?php if ($models) {
      foreach ($models as $model) { ?>
        <tr>
          <td class="dark"><?= $model->title ?></td>
          <?php foreach ($model->rate as $rate) { ?>
            <td class="light">
              <?= ($rate->extra->use_time
                ? lang('label_use_time', 'config_extra') . '&nbsp;<i class="fas fa-check"></i>'
                : number_format(
                  $rate->rate,
                  2,
                  '.',
                  "'"
                ) . ' ' . CURR_VALUTE) ?>
            </td>
          <?php } ?>
          <td class="dark">
            <a href="config.php?mode=extra&action=update&type=<?= $model->type ?>&sport=<?= $model->sport ?>" class="btnEdit">
              <?= lang('button_update') ?>
            </a>
          </td>
        </tr>
        <?php
      }
    } ?>
  </table>
</div>

