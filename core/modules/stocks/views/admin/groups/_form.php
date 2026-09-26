<?php
/**
 * @var \AC\core\modules\stocks\entities\entity\StocksGroup $item
 */

use AC\core\modules\stocks\entities\entity\StocksGroup;

?>
<input type="hidden" name="id" value="<?= $item->id ?? ''?>">
<table border="0" cellspacing="1" cellpadding="0" bgcolor="#FFFFFF" align="center" class="main" style="margin:0 -1px">
  <tr>
    <th><?= lang('title_parameter', 'config') ?></th>
    <th><?= lang('title_value', 'config') ?></th>
  </tr>
  <tr>
    <td class="dark" align="right">
      <?= lang('Title' ) ?>
    </td>
    <td class="light">
      <input type="text" class="input wide" name="title" value="<?= $item->title ?? ''?>">
    </td>
  </tr>
  <tr>
    <td class="dark" align="right">
      <?= lang('Comment' ) ?>
    </td>
    <td class="light">
      <textarea name="description" cols="57" rows="3"><?= $item->description ?? '' ?></textarea>
    </td>
  </tr>
  <tr>
    <td class="dark" align="right">
      <?= lang('Type' ) ?>
    </td>
    <td class="light">
      <?= $item->typesBySelect() ?>
    </td>
  </tr>
  <tr>
    <td class="dark" align="right">
      <?= lang('Active' ) ?>
    </td>
    <td class="light">
      <input type="checkbox" name="active" value="1" <?= $item->active ? 'checked' : ''?>>
    </td>
  </tr>
</table>
