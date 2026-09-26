<?php
/**
 * @var string                                              $action
 * @var \AC\core\modules\stocks\entities\entity\StocksGroup $group
 * @var string                                              $message
 */

use AC\core\modules\stocks\entities\entity\StocksGroup;

?>
<form action="<?= $action ?>" method="post">
  <input type="hidden" name="act" value="condition">
  <input type="hidden" name="id" value="<?= $group->id ?>">
  <table border="0" cellspacing="1" cellpadding="0" bgcolor="#FFFFFF" align="center" style="border-spacing: 1px 0" class="main">
    <tr>
      <th><?= lang('Adds a message when booking', 'stocks_groups') ?></th>
    </tr>
    <tr>
      <td style="text-align: center">
        <textarea name="message" class="ckeditor"><?= ($message ?? '') ?></textarea>
      </td>
    </tr>
    <tr>
      <th colspan="2"><input type="submit" value="<?= lang('button_save') ?>" class="button"></th>
    </tr>
  </table>
</form>
