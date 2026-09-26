<?php
/**
 * @var array $sports_by_type
 * @var View  $this
 * @var array $variables - массив переданых с переменных
 */

?>

<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">
  <tr>
    <th><?= lang('title_parameter', 'config') ?></th>
    <th><?= lang('parameter_max_forward_reservation_days_count', 'config') ?></th>
    <th><?= lang('parameter_min_rejection_days_count', 'config') ?></th>
    <th><?= lang('parameter_min_rejection_days_count_ticket', 'config') ?></th>
  </tr>
  <?php foreach ($sports_by_type as $item) { ?>
    <tr>
      <td class="dark" align="right"><?= $item->title ?></td>
      <td class="light">
        <input type="text" class="input" name="max_forward_reservation_days_count[<?= $item->type_id ?>][<?= $item->sport_id ?>]"
               value="<?= $model_count->tableView[$item->type_id][$item->sport_id]['max_forward_reservation_days_count'] ?? 0 ?>">
      </td>
      <td class="light">
        <input type="text" class="input" name="min_rejection_days_count[<?= $item->type_id ?>][<?= $item->sport_id ?>]"
               value="<?= $model_count->tableView[$item->type_id][$item->sport_id]['min_rejection_days_count'] ?? 0 ?>">
      </td>
      <td class="light">
        <input type="text" class="input" name="min_rejection_days_count_ticket[<?= $item->type_id ?>][<?= $item->sport_id ?>]"
               value="<?= $model_count->tableView[$item->type_id][$item->sport_id]['min_rejection_days_count_ticket'] ?? 0 ?>">
      </td>
    </tr>
  <?php } ?>
</table>