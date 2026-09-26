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
  <select name="<?=$field->name?>_day" class="select_new">
    <?
    for ($i = 1; $i <= 31; $i++) {
      $j = sprintf("%02d", $i);
      echo '<option value="' . $j . '"' . ($j == $field->value->day ? ' selected' : '') . '>' . $j . '</option>' . "\n";
    }
    ?>
  </select>
  <select name="<?=$field->name?>_month" class="select_new">
    <?
    for ($i = 1; $i <= 12; $i++) {
      $j = sprintf("%02d", $i);
      echo '<option value="' . $j . '"' . ($j == $field->value->month ? ' selected' : '') . '>' . $j. '</option>' . "\n";
    }
    ?>
  </select>
  <select name="<?=$field->name?>_year" class="select_new">
    <?
    for ($i = date('Y') - 85; $i <= date('Y'); $i++) {
      echo '<option value="' . $i . '"' . ($i == $field->value->year ? ' selected' : '') . '>' . $i . '</option>' . "\n";
    }
    ?>
  </select>
</div>
