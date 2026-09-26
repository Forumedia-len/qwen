<?php

use AC\app\helpers\LayoutHelper;

?>
<h1><?= lang('title_h1', 'blocks') ?></h1>

<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">
  <tr>
    <th rowspan="2"><?= lang('title_title', 'blocks') ?></th>
    <th colspan="2"><?= lang('title_date', 'blocks') ?></th>
    <th rowspan="2" colspan="2"><?= lang('title_area_name', 'blocks') ?></th>
    <th rowspan="2" colspan="2"><?= lang('title_action') ?></th>
  </tr>
  <tr>
    <th><?= lang('title_date_start', 'blocks') ?></th>
    <th class="light"><?= lang('title_date_end', 'blocks') ?></th>
  </tr>
  <?php


  foreach ($models as $block) { ?>
    <tr>
      <td class="dark"><?= $block['reason'] ?></td>
      <td class="light" align="center"><?= date('<b>d.m.Y</b> H:i', strtotime($block['start'])) ?></td>
      <td class="dark" align="center"><?= date('<b>d.m.Y</b> H:i', strtotime($block['finish'])) ?></td>
      <td class="light"><?= LayoutHelper::renderSquareByTypeSport($block['type_id'], $block['sport_id']) ?></td>
      <td class="dark"><?= $block['area_title'] ?></td>
      <td class="light">
        <a href="blocks.php?action=update&block_id=<?= $block['block_id'] ?>" class="btnEdit"><?= lang('button_update') ?></a>
      </td>
      <td class="light">
        <a href="blocks.php?action=remove&block_id=<?= $block['block_id'] ?>"
           onclick="return ifConfirm ()" class="btnRemove"><?= lang('button_remove') ?></a>
      </td>
    </tr>
  <?php } ?>
</table>

