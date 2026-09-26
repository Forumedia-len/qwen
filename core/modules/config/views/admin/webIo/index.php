<?php
/**
 * @var BaseObject       $models
 * @var ConfigExtraModel $model
 * @var  View            $this
 */


use AC\core\system\object\BaseObject;
use AC\core\system\view\View;
use AC\core\modules\config\models\ConfigExtraModel;

?>
<div style="text-align: center">
  <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
    <tr>
      <th><?= lang('label_parameter_webIo_name', 'webIo') ?></th>
      <th><?= lang('label_parameter_webIo_ip', 'webIo') ?></th>
      <th><?= lang('label_parameter_webIo_port', 'webIo') ?></th>
      <th><?= lang('label_parameter_webIo_use', 'webIo') ?></th>
      <th><?= lang('title_action') ?></th>
    </tr>

    <?php if ($models) {
      foreach ($models as $model) { ?>
        <tr>
          <td class="dark"><?= $model->name ?></td>
          <td class="dark"><?= $model->ip ?></td>
          <td class="light"><?= $model->port ?></td>
          <td class="light"><?= $model->use ? '<i class="fas fa-check"></i>' : '' ?></td>
          <td class="dark">
            <a href="config.php?mode=webIo&action=edit&id=<?= $model->id ?>" class="btnEdit">
              <?= lang('button_update') ?>
            </a>
            <a href="config.php?mode=webIo&action=remove&id=<?= $model->id ?>" class="btnRemove">
              <?= lang('button_remove') ?>
            </a>
          </td>
        </tr>
        <?php
      }
    } ?>
  </table>
</div>

