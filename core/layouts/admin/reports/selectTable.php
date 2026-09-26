<?php
/**
 * @var object $field
 */

?>
<table cellspacing="2" cellpadding="0" border="0">
  <tbody>
  <tr>
    <?php foreach ($field->values as $key => $values) { ?>
      <td><?= useLayout()->render('select', [
          'name'    => $field->name[$key],
          'values'  => $values,
          'current' => $field->current[$key],
          'class'   => $field->class[$key] ?? ($field->class ?? ''),
        ], 'common') ?></td>
    <?php } ?>
  </tr>
  </tbody>
</table>
