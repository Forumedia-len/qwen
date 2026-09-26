<?php
/**
 * @var object $field
 */
?>
  <div class="label-wrapper">
    <span class="label"><?= $field->title ?> </span>
    <?= $field->required ? '<span class="red">* </span>' : '' ?>
    <span class="label"> :</span>
  </div>
  <div style="position: relative">
  <span class="registration" <?= $field->input_attribute ?>>
    <?= $field->value ?>
  </span>
    <?= $field->additional ?>
  </div>