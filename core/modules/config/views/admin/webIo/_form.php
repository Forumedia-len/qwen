<?php
/**
 * @var WebIoModel $model
 * @var View  $this
 */


use AC\core\system\view\View;
use AC\core\modules\webIo\models\WebIoModel;

?>
<form action="config.php" method="post" onsubmit="return ifConfirm ()">
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="mode" value="webIo">
  <input type="hidden" name="id" value="<?= $model->id ?>">
  <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">
    <tr>
      <th colspan="2"><?= lang('title_webIo_settings', 'webIo') ?></th>
    </tr>
    <tr>
      <th><?= lang('title_parameter', 'config') ?></th>
      <th><?= lang('title_value', 'config') ?></th>
    </tr>
    <tr>
      <td class="dark" align="right">
        <?= lang('label_parameter_webIo_name' , 'webIo') ?>
      </td>
      <td class="light">
        <input type="text" class="input wide" name="name" value="<?= $model->name ?>">
      </td>
    </tr>
    <tr>
      <td class="dark" align="right">
        <?= lang('label_parameter_webIo_use' , 'webIo') ?>
      </td>
      <td class="light">
          <input type="checkbox" class="webIo-input input" value="1" name="use" <?= $model->use == 1 ? 'checked' : '' ?>>
      </td>
    </tr>
    <tr>
      <td class="dark" align="right">
        <?= lang('label_parameter_webIo_ip' , 'webIo') ?>
      </td>
      <td class="light">
          <input type="text" class="input wide" name="ip" value="<?= $model->ip ?>">
      </td>
    </tr>
    <tr>
      <td class="dark" align="right">
        <?= lang('label_parameter_webIo_port' , 'webIo') ?>
      </td>
      <td class="light">
          <input type="text" class="input wide" name="port" value="<?= $model->port ?>">
      </td>
    </tr>
    <tr>
      <td class="dark" align="right">
        <?= lang('label_parameter_webIo_password' , 'webIo') ?>
      </td>
      <td class="light">
          <input type="password" class="input wide" name="password" value="<?= $model->password ?>">
      </td>
    </tr>
    <?php foreach ($model->pre_start_time as $alias => $pre_start_time): ?>
      <tr>
        <td class="dark" align="right">
          <?= lang('label_parameter_webIo_pre_start_time_' . $alias , 'webIo') ?>
        </td>
        <td class="light">
          <input type="text" class="input wide" name="pre_start_time[<?= $alias ?>]" value="<?= $pre_start_time->value ?>">
        </td>
      </tr>
      <?php if($alias == 'all'):?>
      <tr>
        <td class="dark">&nbsp;</td>
        <td class="light"><?= lang('text_webIo_pre_start_time', 'webIo') ?></td>
      </tr>
    <?php endif;?>
    <?php endforeach;?>
    <tr>
      <th colspan="2"><input type="submit" value="<?= lang('button_save') ?>" class="button"></th>
    </tr>
  </table>
</form>

