<?php
/**
 * @var object $field
 */
?>
<div class="label-wrapper">
  <label for="<?= 'reg_' . $field->name ?>">
    <span class='label'><?= $field->title ?> </span>
    <?= $field->required ? '<span class="red">* </span>' : '' ?>
    <span class="label"> :</span>
  </label>
</div>
<div class="inline-block">
  <select name="<?= $field->name ?>" class="select_new" id="<?= 'reg_' . $field->name?>" style="max-width: 250px">
    <?php
    if (defined('USE_FIRM_FIELD_REGISTRATION_AS_SELECT') && USE_FIRM_FIELD_REGISTRATION_AS_SELECT && !empty($field->values) && $field->name == 'firm') {
      echo ' <option value=""' . (empty($field->value) ? ' selected' : '') .'></option>' . "\n";
      foreach ($field->values as $value) {
        echo '<option value="' . $value . '" ' . (isset($field->value) && $field->value == $value ? ' selected' : '') . '>' . $value . '</option>' . "\n";
      }
    } else {
      if (!empty($field->values)) {
        foreach ($field->values as $key => $value) {
          echo '<option value="' . $key . '" ' . ((!empty($field->value) && $field->value == $key) || (empty($field->value) && isset($field->default) && $field->default == $key) ? ' selected' : '') . '>' . $value . '</option>' . "\n";
        }
      }
    }
    ?>
  </select>
</div>
