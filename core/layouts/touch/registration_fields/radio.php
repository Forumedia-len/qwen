<?php
/**
 * @var object $field
 */
?>

<div class="display-block">
  <input name="<?= $field->base_name?>" type="radio" class="radio"
         value="<?= $field->value->value ?>" <?= $field->value->checked ?>/>
  <span class="podlog"></span> <?= $field->title ?>
</div>
