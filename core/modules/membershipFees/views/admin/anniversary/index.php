<?php
/**
 * @var array $tableList
 */

?>
<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">
  <tr>
    <?php foreach ($tableList['titles'] as $item) { ?>
      <th <?= (isset($item['width']) && $item['width'] ? 'width="' . $item['width'] . '"' : '') ?>>
        <?= $item['title'] ?>
      </th>
    <?php } ?>

  </tr>
  <?php foreach ($tableList['rows'] as $row) { ?>
    <tr>
      <?php foreach ($row as $item) { ?>
        <td class="<?= $item->class ?>" <?= (isset($item->id) ? 'id="' . $item->id . '"' : '') ?> <?= (isset($item->style)
          ? 'style="' . $item->style . '"' : '') ?>>
          <?= $item->value ?>
        </td>
      <?php } ?>
    </tr>
  <?php } ?>
</table>
