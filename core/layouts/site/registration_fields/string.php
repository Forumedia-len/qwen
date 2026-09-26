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
  <span class="registration" <?= $field->input_attribute ?>>
    <?= $field->value ?>
  </span>
<?= $field->additional ?>