<?php
/**
 * @var object|array $field
 */
$field = (object)$field;
?>

<input name="<?= $field->name ?>" type="checkbox" value="<?= $field->value ?>" <?= ($field->checked ? 'checked' : '') ?> id="<?= $field->id ?>" <?= $field->otherProperties ?? '' ?>/>
