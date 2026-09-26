<?php
/**
 * @var object $field
 */
?>
<div class="display-block">
  <input id="<?= $field->base_name?>_<?= $field->value->value ?>" name="<?= $field->base_name?>" type="radio" class="radio"
         value="<?= $field->value->value ?>" <?= $field->value->checked ?> autocomplete="nope"/>
  <span class="podlog"></span><label for="<?= $field->base_name?>_<?= $field->value->value ?>" style="cursor:pointer;"><?= $field->title ?></label>
</div>
