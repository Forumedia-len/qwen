<?php
/**
 * @var object $field
 */

?>
<table cellspacing="2" cellpadding="0" border="0">
  <tbody>
  <?php foreach ($field->values as $key => $value) { ?>
    <tr>
      <td><?= useLayout()->render('radio', [
          'name' => $field->name,
          'value' => $key,
          'current' => $field->current
        ], 'common') ?></td>
      <td><?= $value ?></td>
    </tr>
  <?php } ?>
  </tbody>
</table>
