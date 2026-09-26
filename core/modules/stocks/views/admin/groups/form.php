<?php
/**
 * @var \AC\core\modules\stocks\entities\entity\StocksGroup $item
 * @var array                                               $types
 * @var string                                              $typesBySelect
 * @var string                                              $action
 */

use AC\core\modules\stocks\entities\entity\StocksGroup;

?>
<form action="<?= $action?>" method="post" >
  <input type="hidden" name="id" value="<?= $item->id ?? ''?>">
  <table border="0" cellspacing="1" cellpadding="0" bgcolor="#FFFFFF" align="center" style="border-spacing: 1px 0" class="main">
    <tr>
      <th><?= lang('title_' . ($item->id == null ? 'create' : 'update')) ?></th>
      <th><?= lang('Available types of booking option groups', 'stocks_groups') ?></th>
    </tr>
    <tr>
      <td style="padding: 0;margin: 0">
        <?= $this->render('groups/_form', ['item' => $item, 'typesBySelect' => $typesBySelect])?>
      </td>
      <td class="light" style="vertical-align: top;padding: 0;height: 100%">
        <?= $this->render('groups/types', ['items' => $types])?>
      </td>
    </tr>
    <tr>
      <th colspan="2"><input type="submit" value="<?= lang('button_save') ?>" class="button"></th>
    </tr>
  </table>
</form>