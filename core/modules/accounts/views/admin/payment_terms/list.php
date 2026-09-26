<?php
/**
 * @var array $itemsText
 */
?>
<h1><?= lang('Account payment terms', 'structure') ?></h1>
<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">
  <thead>
  <tr>
    <th><?= lang('Title') ?></th>
    <th width="80px"><?= lang('Action') ?></th>
  </tr>
  </thead>
  <tbody>
  <?php
  foreach ($itemsText as $item) {
    ?>
    <tr>
      <td class="dark"><?= $item['name'] ?></td>
      <td class="dark">
        <a
          href="<?= $item['url'] ?>"
          class="btnEdit"><?= lang('button_update') ?></a>
      </td>
    </tr>
    <?php
  }
  ?>
  </tbody>
</table>
