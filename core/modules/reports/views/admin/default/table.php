<?php
/**
 * @var array $data
 */
?>
<table border="0" cellspacing="0" class="periods">
  <?php foreach ($data['titles'] as $keyRow => $row) { ?>
    <tr>
      <?= (!empty($data['fields']['useNumber']) && $keyRow == 1 ? '<th rowspan="' . ($data['fields']['title_row']) . '">#</th>' : '') ?>
      <?php foreach ($row as $item) { ?>
        <th <?= (!empty($item['col']) ? 'colspan="' . $item['col'] . '"' : '') ?> <?= (!empty($item['row']) ? ' rowspan="' . $item['row'] . '"' : '') ?>><?= $item['title'] ?></th>
      <?php } ?>
    </tr>
  <?php } $num = 1;?>
  <?php foreach ($data['rows'] as $row) { ?>
    <tr>
      <?= (!empty($data['fields']['useNumber']) ? '<td>' .$num . '</td>' : '') ?>
      <?php $i = 0; foreach ($row as $key => $item) { ?>
        <td class="<?= ($data['fields']['fields'][$key]['class'] ?? ($i % 2 == 0 ? 'p' : '')) ?>" style="<?= ($data['fields']['fields'][$key]['style']) ?? '' ?>"><?= ($item ?? '')?></td>
      <?php $i++; } ?>
    </tr>
  <?php $num++; } ?>
</table>

