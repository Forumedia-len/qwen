<?php
/**
 * @var object $field
*/
?>
<input type="submit"
       name="<?= $field->name ?>"
       value="<?= $field->value ?>"
      <?= $field->class ? ' class="'.$field->class.'"' : ''?>
      <?= $field->style ? ' style="'.$field->style.'"' : ''?>
      <?= $field->id ? ' id="' . $field->id . '"' : ''?>
      <?= $field->otherProperties ?? '' ?>
/>