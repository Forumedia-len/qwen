<?php
/**
 * @var FieldForm $field
 */

use AC\app\helpers\LayoutHelper;
use AC\core\system\object\entity\html\form\FieldForm;

?>
  <input <?= LayoutHelper::attributeElementHtml('name', $field->getName()); ?> <?= $field->getAttributesAsString() ?>/>
<?php
