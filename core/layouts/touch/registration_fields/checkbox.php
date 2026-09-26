<?php
/**
 * @var object $field
 */
?>

<div class="display-block">
  <input name="<?= $field->base_name?>" type="checkbox" class="check"
         value="<?= $field->value->value ?>" <?= $field->value->checked ?>/>
  <span class="podlog2"></span> <?= $field->title ?>
</div>
