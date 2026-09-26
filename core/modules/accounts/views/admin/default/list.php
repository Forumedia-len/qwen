<?php
/**
 * @var string $h1
 * @var string $description
 * @var string $areaType
 * @var string $accountType
 * @var object $placeAboveTheTable
 * @var array  $tableList
 * @var string $footerButtons
 * @var string $popUpWindow
 * @var string $formAction
 */

?>
<h1><?= $h1 ?></h1>
<?php if (!empty($description)) { ?>
  <p><?= $description ?></p>
<?php } ?>
<?= (!empty($popUpWindow) ? $popUpWindow : '') ?>
<table align="center" border="0" cellspacing="1" cellpadding="3" class="main wide" width="80%">
  <?php if (!empty($placeAboveTheTable)) { ?>
    <tr>
      <td class="dark" colspan="<?= count($tableList['titles']) ?>" <?= (isset($placeAboveTheTable->style)
        ? 'style="' . $placeAboveTheTable->style . '"' : '') ?>>
        <?= $placeAboveTheTable->text ?>
      </td>
    </tr>
  <?php } ?>
  <tr>
    <?php foreach ($tableList['titles'] as $item) { ?>
      <th <?= (isset($item['width']) && $item['width'] ? 'width="' . $item['width'] . '"' : '') ?>>
        <?= $item['title'] ?>
      </th>
    <?php } ?>

  </tr>
  <form action="<?= $formAction ?>" method="POST" id="accounts_list">
    <input type="hidden" name="account_type" value="<?= $accountType ?>"/>
    <input type="hidden" name="type" value="<?= $areaType ?>"/>
    <?php foreach ($tableList['rows'] as $accountId => $row) { ?>
      <tr>
        <?php foreach ($row as $item) { ?>
          <td class="<?= $item->class ?>" <?= (isset($item->id) ? 'id="' . $item->id . '"' :'')?> <?= (isset($item->style) ? 'style="' . $item->style . '"' :'')?>>
            <?= $item->value ?>
          </td>
        <?php } ?>
      </tr>
    <?php } ?>


    <?php if (!empty($footerButtons)) { ?>
      <tr>
        <td class="light" colspan="<?= count($tableList['titles']) ?>" align="right">
          <?= $footerButtons ?>
        </td>
      </tr>
    <?php } ?>
  </form>
</table>
