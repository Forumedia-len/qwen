<?php
/**
 * @var ConfigNdsModel $model
 */


use AC\core\modules\config\models\ConfigNdsModel;

?>
<form action="<?= Service::structure()->getPageHrefByKey('config_nds')?>&action=<?= ($model->nds_id == null ? 'create' : 'update&nds_id=' . $model->nds_id) ?>"
      method="post">
  <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
    <tr>
      <th colspan="2"><?= lang('title_' . ($model->nds_id == null ? 'create' : 'update'), 'config_nds') ?></th>
    </tr>
    <tr>
      <td class="dark"><?= $model->getAttributeLabel('rate') ?>:</td>
      <td class="dark"><input type="text" name="rate" class="input" value="<?= $model->rate ?>" <?php //maxlength="2" ?> />
        %
      </td>
    </tr>
    <tr>
      <td class="dark"><?= $model->getAttributeLabel('comment') ?>:</td>
      <td class="dark"><input type="text" name="comment" class="input wide"
                              value="<?= str_replace('"', '&amp;', $model->comment??'') ?>"/></td>
    </tr>
    <tr>
      <th align="center" colspan="2">
        <input type="submit" value="<?= lang('button_' . ($model->nds_id == null ? 'create' : 'update')) ?>"
               class="button">
        &nbsp;
        <input type="reset" value="<?= lang('button_reset') ?>" class="button">
      </th>
    </tr>
  </table>
</form>