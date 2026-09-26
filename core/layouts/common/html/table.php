<?php
/**
 * @var Table $table
 */

use AC\core\system\object\entity\html\Table;
use AC\core\system\object\entity\html\table\ThField;

?>
<table <?= $table->getAttributesAsString() ?>>
  <?php foreach ($table->getRowsTitles() as $rowTitles) : ?>
    <tr>
      <?php /** @var ThField $item */
      foreach ($rowTitles as $item) :?>
        <?= $item->asString() ?>
      <?php endforeach; ?>
    </tr>
  <?php endforeach; ?>
  <?php foreach ($table->getRows() as $row) : ?>
    <?= $row->asString() ?>
  <?php endforeach; ?>
</table>

