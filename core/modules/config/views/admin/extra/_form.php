<?php
/**
 * @var ConfigExtraModel $model
 */

use AC\core\modules\config\models\ConfigExtraModel;

?>
<div>
  <form action="config.php?mode=extra&action=<?= ($model->new ? 'create' : 'update&type=' . $model->type . '&sport=' . $model->sport) ?>"
        method="post">

    <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
      <tr>
        <th colspan="3"><span style="font-size: 15px;font-weight: bold"><?= $model->title ?></span></th>
      </tr>
      <tr>
        <th colspan="3"><?= lang('title_' . ($model->new ? 'create' : 'update'), 'config_extra') ?></th>
      </tr>
      <?php foreach ($model->rate as $rate) { ?>
        <tr>
          <td class="light"><?= $rate->title ?>:</td>
          <td class="light"><input type="text" name="rate[<?= $rate->extra_id ?>]" class="input"
                                   value="<?= $rate->rate ?>"/></td>
          <td class="light"><input type="checkbox" name="use_time[<?= $rate->extra_id ?>]" class="input"
                                   value="1" <?= ($rate->extra->use_time ? 'checked' : '') ?>/><?=lang('label_use_time', 'config_extra')?></td>
        </tr>
      <?php } ?>
      <tr>
        <th align="center" colspan="3">
          <input type="submit" value="<?= lang('button_' . ($model->new ? 'create' : 'update')) ?>" class="button">
          &nbsp;
          <input type="reset" value="<?= lang('button_reset') ?>" class="button">
        </th>
      </tr>
    </table>
  </form>
</div>

