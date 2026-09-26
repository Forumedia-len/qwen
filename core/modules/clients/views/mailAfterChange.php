<?php
/**
 * @var array $change
 * @var string $title
 * @var string $locale
 */
?>
<style>
  table {
    min-width: 600px;
    tr {
      min-width: 100px;
    }
    td, th {
      padding: 3px 10px;
      border: 1px solid #ddd;
    }
    th {
      text-align: left;
    }
  }
</style>
<table>
  <caption><?= $title ?>:</caption>
  <tr>
    <th><?= lang('Field', 'mailing.send_after_change_client', [], null, $locale) ?></th>
    <th><?= lang('Old entry', 'mailing.send_after_change_client', [], null, $locale) ?></th>
    <th><?= lang('New entry', 'mailing.send_after_change_client', [], null, $locale) ?></th>
  </tr>
  <?php foreach ($change as $item) { ?>
    <tr>
      <td><?= $item['title'] ?></td>
      <td><?= $item['old'] ?></td>
      <td><?= $item['new'] ?></td>
    </tr>
  <?php } ?>
</table>