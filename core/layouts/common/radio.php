<?php
/**
 * @var string     $name
 * @var string     $value
 * @var string|int $current
 */

?>
<input type="radio" name="<?= $name ?>" value="<?= $value ?>" <?= ($current == $value ? 'checked="checked"' : '') ?>>
