<?php
/**
 * @var object $field
 */
?>
<div class="display-block">
  <input name="<?= $field->base_name?>" type="checkbox" class="check"
         value="<?= $field->value->value ?>" <?= $field->value->checked ?>/>
  <label for="reg_<?= $field->name ?>"><span class="podlog2" style="left: 0;"></span>&nbsp;<?= $field->title ?></label>
</div>
