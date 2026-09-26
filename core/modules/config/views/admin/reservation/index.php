<?php
/**
 * @var ConfigModel $models
 * @var View        $this
 */


use AC\core\system\view\View;
use AC\core\modules\config\models\ConfigModel;

?>
<form action="config.php?mode=reservation&action=save" method="post" onsubmit="return ifConfirm ()">
  <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">
    <tr>
      <th><?= lang('title_parameter', 'config') ?></th>
      <th><?= lang('title_value', 'config') ?></th>
    </tr>
    <?= $this->render('tickets', ['models' => $models])?>
    <tr>
      <th colspan="2"><input type="submit" value="<?= lang('button_save') ?>" class="button"></th>
    </tr>
  </table>
</form>