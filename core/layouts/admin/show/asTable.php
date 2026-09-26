<?php
/**
 * @var ShowAsTable $block
 */

use AC\core\system\actions\admin\contentBlock\ShowAsTable;

?>
<?= $block->getTitle() ?>
<?= $block->getDescription() ?>
<?= $block->getHeader()?>
<?= $block->getContent()?>
<?= $block->getFooter()?>