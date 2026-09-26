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
<div class="inline-block">
  <select name="<?=$field->name?>" class="select_new">
    <option value="" <?= (empty($field->value) ? ' selected' : '') ?>></option>
    <?php
    if(defined('USE_FIRM_FIELD_REGISTRATION_AS_SELECT') && USE_FIRM_FIELD_REGISTRATION_AS_SELECT && !empty($field->values)) {
      foreach ($field->values as $value) {
        echo '<option value="' . $value . '" '.(isset($field->value)  && $field->value == $value ? ' selected' : '' ).'>' . $value . '</option>' . "\n";
      }
    }
    ?>
  </select>
</div>
