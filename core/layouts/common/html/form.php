<?php
/**
 * @var \AC\core\system\object\entity\html\Form $form
 */

use AC\app\helpers\LayoutHelper;
use AC\core\system\object\entity\html\Form;

?>
<form
  <?= $form->getAttributesAsString() ?>>
  <?php foreach ($form->getHiddenFields() as $field): ?>
    <?= $field->asString()?>
  <?php endforeach; ?>
  <?= $form->getContent()?>
</form>
