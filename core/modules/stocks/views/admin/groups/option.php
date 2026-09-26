<?php
/**
 * @var string $action
 * @var string $stocks
 */
?>
<form action="<?= $action ?>" method="post">
  <input type="hidden" name="act" value="addOptions">
  <table border="0" cellspacing="1" cellpadding="0" bgcolor="#FFFFFF" align="center" style="border-spacing: 1px 0" class="main">
    <tr>
      <th><?= lang('Add options to the group', 'stocks_groups') ?></th>
    </tr>
    <tr>
      <td style="text-align: center">
        <?= $stocks ?>
      </td>
    </tr>
    <tr>
      <th colspan="2"><input type="submit" value="<?= lang('button_save') ?>" class="button"></th>
    </tr>
  </table>
</form>