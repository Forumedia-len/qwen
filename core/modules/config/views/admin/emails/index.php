<?php
/**
 * @var ConfigPPModel $models
 */

use AC\core\modules\config\models\ConfigPPModel;

?>
<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
  <tr>
    <th colspan="5"><?= lang('title_general_header', 'config_emails')?></th>
  </tr>
  <tr>
    <th><?= lang('title_name_court', 'config_emails')?></th>
    <th><?= lang('title_email', 'config_emails')?></th>
    <th><?= lang('title_courts', 'config_emails')?></th>
    <th colspan="2"><?= lang('title_action')?></th>
  </tr>

  <?php if (!empty($models)) {
    foreach ($models as $model) { ?>
      <tr>
        <td class="dark" align="center"><?= $model->name_court ?></td>
        <td class="light"><?= $model->email ?></td>
        <td class="light">
          <?php foreach ($model->courts as $t_s) { ?>
            <?= (in_array($t_s, array_keys($type_sport)) ? '<div style="padding: 3px">' .$type_sport[$t_s]->title_full .'</div>' : '') ?>
          <?php } ?>
        </td>
        <td class="light">
          <a href="config.php?mode=email&action=edit&alias=<?= $model->alias ?>"
             class="btnEdit"><?= lang('button_update')?></a>
        </td>
        <td class="light">
          <a href="config.php?mode=email&action=remove&alias=<?= $model->alias ?>"
             onclick="return ifConfirm ()"
             class="btnRemove"><?= lang('button_remove')?></a>
        </td>
      </tr>
      <?php
    }
  } ?>
</table>