<?php
/**
 * @var ConfigModel $models
 * @var string $caption
 * @var string $type
 * @var int|null $type_id
 * @var View  $this
 */


use AC\core\system\view\View;
use AC\core\modules\config\models\ConfigModel;

$typeIdQuery = !empty($type_id) ? '&type_id=' . (int)$type_id : '';

?>
<form action="config.php?mode=open_type&action=saveOptionSystemPrice<?= $typeIdQuery ?>" method="post">
  <input type="hidden" name="type_option" value="<?= $type?>">
  <?php if (!empty($type_id)): ?>
    <input type="hidden" name="type_id" value="<?= (int)$type_id ?>">
  <?php endif; ?>
  <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide open-type">
    <caption><?= $caption ?></caption>
    <tr>
      <th><?= lang('title_parameter', 'config') ?></th>
      <th><?= lang('title_value', 'config') ?></th>
    </tr>
    <tr>
      <th><?= lang('guest_as_no_member', 'config_open_type') ?></th>
      <td>
        <input type="hidden" name="<?= $type ?>[guest_as_no_member]" value="0">
        <input type="checkbox" name="<?= $type?>[guest_as_no_member]" value="1" <?= ($models->guest_as_no_member ? 'checked' : '') ?>>
      </td>
    </tr>
    <tr>
      <th><?= lang('main_no_member_price_for_no_member', 'config_open_type') ?></th>
      <td>
        <input type="hidden" name="<?= $type ?>[main_no_member_price_for_no_member]" value="0">
        <input type="checkbox" name="<?= $type?>[main_no_member_price_for_no_member]" value="1" <?= ($models->main_no_member_price_for_no_member ? 'checked' : '') ?>>
      </td>
    </tr>
    <tr>
      <th><?= lang('double_count_other_players_only', 'config_open_type') ?></th>
      <td>
        <input type="hidden" name="<?= $type ?>[double_count_other_players_only]" value="0">
        <input type="checkbox" name="<?= $type?>[double_count_other_players_only]" value="1" <?= ($models->double_count_other_players_only ? 'checked' : '') ?>>
      </td>
    </tr>
    <tr>
      <th colspan="2"><input type="submit" value="<?= lang('button_save') ?>" class="button"></th>
    </tr>
  </table>
</form>
