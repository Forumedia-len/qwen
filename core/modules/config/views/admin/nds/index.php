<?php
/**
 * @var BaseObject     $models
 * @var NdsDto $model
 */


use AC\core\modules\config\entities\dto\NdsDto;
use AC\core\system\object\BaseObject;

?>
<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
  <tr>
    <th><?= lang('title_rate', 'config_nds') ?></th>
    <th><?= lang('title_comment', 'config_nds') ?></th>
    <th><?= lang('title_action', 'config_nds') ?></th>
  </tr>

  <?php if ($models) {
    foreach ($models as $model) { ?>
      <tr>
        <td class="dark"><?= $model->rate ?>%</td>
        <td class="light"><?= $model->comment . ' ' . ($model->default->isOn() ? '<b>(' . lang('text_standard',
              'config_nds') . ')</b>' : '') ?></td>
        <td class="dark">
          <a href="<?= Service::structure()->getPageHrefByKey('config_nds')?>&action=update&nds_id=<?= $model->nds_id ?>" class="btnEdit"
             onclick="return ifConfirm('<?= lang('text_alert_update',
               'config_nds') ?>')"><?= lang('button_update') ?>
          </a>
        </td>
      </tr>
      <?php
    }
  } ?>
</table>
