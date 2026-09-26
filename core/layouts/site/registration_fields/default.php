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
<input name="<?= $field->name ?>"
       class="registration"
  <?= $field->input_attribute ?>
       title="<?= $field->input_title ?>"
       type="<?= $field->type ?>"
       value="<?= $field->value ?>" autocomplete="nope">
<?= $field->additional ?>
