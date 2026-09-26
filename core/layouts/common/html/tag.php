<?php
/**
 * @var TagHtml $tag
 */

use AC\core\system\object\entity\html\TagHtml;

?>
<<?= $tag->getTag() ?> <?= $tag->getAttributesAsString() ?>><?= $tag->getContent() ?></<?= $tag->getTag() ?>>
