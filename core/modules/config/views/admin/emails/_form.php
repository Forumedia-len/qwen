<?php
/**
 * @var ConfigEmailModel $model
 * @var  array           $type_sport
 */

use AC\core\modules\config\models\ConfigEmailModel;

?>
<form action="config.php?mode=email&action=<?= (!$model->alias ? 'create' : 'update') ?>"
      method="post">
  <input type="hidden" name="alias" value="<?= $model->alias?>">
  <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
    <tr>
      <th colspan="2"><?= lang('title_' . (!$model->alias? 'create' : 'update')) ?></th>
    </tr>
    <tr>
      <td class="dark"><?= lang('title_name_court', 'config_emails') ?>:</td>
      <td class="dark"><input type="text" name="name_court" class="input wide" value="<?= $model->name_court ?>"/></td>
    </tr>
    <tr>
      <td class="dark"><?= lang('title_email', 'config_emails') ?>:</td>
      <td class="dark"><input type="text" name="email" class="input wide" value="<?= $model->email ?>"/></td>
    </tr>
    <tr>
      <td class="light"><?= lang('title_courts', 'config_emails') ?>:</td>
      <td class="dark">
        <select name="courts[]" id="MultipleSelectBox" multiple>
          <option value="0"></option>
          <?php
          foreach ($type_sport as $key => $type) { ?>
            <option
              value="<?= $key ?>" <?= (in_array($key, $model->courts) ? 'selected' : '') ?>>
              <?= $type->title_full ?>
            </option>
            <?php
          } ?>
        </select>
      </td>
    </tr>
    <tr>
      <th align="center" colspan="2">
        <input type="submit" value="<?= lang('button_' . (!$model->alias ? 'create' : 'update')) ?>" class="button">
        &nbsp;
        <input type="reset" value="<?= lang('button_reset') ?>" class="button">
      </th>
    </tr>
  </table>
</form>
<script>
  $('#MultipleSelectBox').select2()
</script>
