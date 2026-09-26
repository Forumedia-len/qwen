<?php
/**
 * @var object $field
 */

?>
<a href="<?= $field->href ?>"
  <?= $field->class ? ' class="'.$field->class.'"' : ''?>
  <?= $field->style ? ' style="'.$field->style.'"' : ''?>
  <?= $field->target ? ' target="' . $field->target . '"' : ''?>
  <?= $field->id ? ' id="' . $field->id . '"' : ''?> <?= $field->otherProperties ?? '' ?>>
  <?= $field->value ?>
</a>
