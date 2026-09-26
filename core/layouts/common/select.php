<?php
/**
 * @var string           $name
 * @var bool             $multiple
 * @var array            $values
 * @var string|int|array $current
 */

?>
<select name="<?= $name ?>"<?= (isset($multiple) && $multiple ? ' multiple="multiple" ' : '') ?><?= (isset($size) ? ' size="' . $size . '"' : '') ?><?= (isset($id) ? ' id="' . $id . '"' : '') ?> class="input <?= (isset($class) ? $class : 'small') ?>"<?= $otherAttribute ?? '' ?>>
  <?php foreach ($values as $key => $value) { ?>
    <option value="<?= $key ?>"<?= ((is_array($current) && in_array($key,
        $current)) || $key == $current ? ' selected' : '') ?>><?= $value ?></option>
  <?php } ?>
</select>