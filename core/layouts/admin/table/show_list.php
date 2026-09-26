<?php
/**
 * @var string $h1
 * @var string $description
 * @var string $areaType
 * @var string $accountType
 * @var object $placeAboveTheTable
 * @var array $tableList
 * @var object $footer
 * @var string $popUpWindow
 * @var object $form
 */

use AC\core\system\object\entity\html\table\ThField;


?>
<h1><?= $h1 ?></h1>
<?php if (!empty($description)) { ?>
  <p><?= $description ?></p>
<?php } ?>
<?= (!empty($popUpWindow) ? $popUpWindow : '') ?>
<?php if (!empty($placeAboveTheTable) && !empty($placeAboveTheTable->text)) : ?>
  <div class="dark" <?= (isset($placeAboveTheTable->style)
    ? 'style="' . $placeAboveTheTable->style . '"' : '') ?>>
    <?= $placeAboveTheTable->text ?>
  </div>
<?php endif; ?>
<form
  name="<?= $form->getName()?>"
  action="<?= $form->action ?>"
  id="<?= $form->id ?? '' ?>"
  class="<?= $form->class ?? '' ?>"
  method="<?= $form->method?>">
  <?php if (!empty($form->hiddenFields)) { ?>
    <?php foreach ($form->hiddenFields as $hiddenField): ?>
      <input type="hidden" name="<?= $hiddenField->name ?>" value="<?= $hiddenField->value ?>"/>
    <?php endforeach; ?>
  <?php } ?>
  <table align="center" border="0" cellspacing="1" cellpadding="3" class="main wide">
    <?php foreach ($tableList['titles']->getRowsTitles() as $rowTitles) : ?>
      <tr>
      <tr>
        <?php /** @var ThField $item */
        foreach ($rowTitles as $item) :?>
          <?= $item->asString() ?>
        <?php endforeach; ?>
      </tr>
      </tr>
    <?php endforeach; ?>

    <?php foreach ($tableList['rows'] as $idRow => $row) : ?>
      <tr>
        <?php foreach ($row as $item) : ?>
          <td
            class="<?= $item->class ?>" <?= (isset($item->id) ? 'id="' . $item->id . '"' : '') ?> <?= (isset($item->style) ? 'style="' . $item->style . '"' : '') ?>>
            <?= $item->value ?>
          </td>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>

    <?php if (!empty($footer)) { ?>
      <tr>
        <td class="light" colspan="<?= $tableList['titles']->getCountCols() ?>" align="right">
          <?= $footer->buttons ?>
        </td>
      </tr>
    <?php } ?>
  </table>
</form>

