<?php
/**
 * @var array $items
 */

use AC\core\modules\stocks\entities\entity\groupTypes\GroupType;


?>

<table border="0" cellspacing="0" cellpadding="0" align="center" style="width: calc(100% + 2px); border-spacing: 1px;border-right-width: 1px;margin:0 -1px" class="main">
  <tr>
    <th><?= lang('Title') ?></th>
    <th><?= lang('Comment') ?></th>
<!--    <th>--><?php //= lang('Active') ?><!--</th>-->
  </tr>

  <?php
  if ($items) {

    /** @var GroupType $item */
    foreach ($items as $item) { ?>
      <tr>
        <td class="dark"><?= $item->title ?></td>
        <td class="light"><?= $item->description  ?></td>
      </tr>
      <?php
    }
  } ?>
</table>