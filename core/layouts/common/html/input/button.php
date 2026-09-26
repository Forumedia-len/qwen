<?php
/**
 * @var Button $field
 */

use AC\app\helpers\LayoutHelper;
use AC\core\system\object\entity\html\Button;

?>
<input
  <?= LayoutHelper::attributeElementHtml('type', $field->type); ?>
  <?= LayoutHelper::attributeElementHtml('name', $field->getName()); ?>
  <?= LayoutHelper::attributeElementHtml('value', $field->value); ?>
  <?= $field->getAttributesAsString() ?>
/>
