<?php
/**
 * @var list<array{alias:string,label:string,input:string,value:mixed}> $fields
 * @var int $type_id
 * @var View $this
 */

use AC\core\system\view\View;

$typeIdQuery = !empty($type_id) ? '&type_id=' . (int)$type_id : '';

?>
<form action="config.php?mode=open_type&action=saveDouble<?= $typeIdQuery ?>" method="post">
  <?php if (!empty($type_id)): ?>
    <input type="hidden" name="type_id" value="<?= (int)$type_id ?>">
  <?php endif; ?>
  <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide open-type open-type-double">
    <caption><?= lang('title_double_game', 'config_open_type') ?></caption>
    <tr>
      <th><?= lang('title_parameter', 'config') ?></th>
      <th><?= lang('title_value', 'config') ?></th>
    </tr>
    <?php foreach ($fields as $field): ?>
      <tr>
        <th style="text-align:right"><?= htmlspecialchars($field['label'], ENT_QUOTES) ?></th>
        <td>
          <?php if ($field['input'] === 'bool'): ?>
            <input type="hidden" name="double[<?= $field['alias'] ?>]" value="0">
            <input type="checkbox" name="double[<?= $field['alias'] ?>]" value="1" <?= ($field['value'] ? 'checked' : '') ?>>
          <?php else: ?>
            <input type="text" class="input" name="double[<?= $field['alias'] ?>]" value="<?= (int)$field['value'] ?>">
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <tr>
      <th colspan="2"><input type="submit" value="<?= lang('button_save') ?>" class="button"></th>
    </tr>
  </table>
</form>
