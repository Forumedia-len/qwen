<?php
/**
 * @var object $tableForm
 * @var object $form
 */
?>

<form action="<?= $form->action ?>" method="<?= $form->method ?>" <?= ($form->target ? 'target="' . $form->target . '"' : '') ?>>
  <table cellspacing="1" cellpadding="3" class="main" border="0">
    <tr>
      <th colspan="2"><?= $tableForm->caption ?></th>
    </tr>

    <?php foreach ($tableForm->rows as $i => $row) {
      $colspan = $row->title && $row->value ? 1 : 2;
      ?>
      <tr>
        <?php if ($row->title) { ?>
          <td class="<?= ($i % 2 == 0 ? 'light' : 'dark') . ($row->class ?? '') ?>" colspan="<?= $colspan ?>">
            <?= $row->title . ($colspan == 1 ? ':' : '') ?>
          </td>
        <?php } ?>
        <?php if ($row->value) { ?>
          <td class="<?= ($i % 2 == 0 ? 'light' : 'dark') . ($row->class ?? '') ?>" colspan="<?= $colspan ?>">
            <?= $row->value ?>
          </td>
        <?php } ?>
      </tr>
    <?php } ?>
    <tr>
      <td class="dark" colspan="2" align="center">
        <?= $tableForm->actions ?>
    </tr>
  </table>
</form>
