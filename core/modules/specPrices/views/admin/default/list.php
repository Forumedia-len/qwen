<?php
/**
 * @var array $specPrices
 */

use AC\core\modules\specPrices\entities\dto\SpecPriceDto;

?>

<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
  <tr>
    <th><?= lang('Line', 'config_spec_price') ?></th>
    <th><?= lang('Abbreviation', 'config_spec_price') ?></th>
    <th><?= lang('Title') ?></th>
    <th><?= lang('Prices') ?></th>
    <th><?= lang('title_choice_type_sport','config_spec_price') ?></th>
    <th><?= lang('For all users', 'config_spec_price') ?></th>
    <th colspan="3"><?= lang('Action') ?></th>
  </tr>
  <?php /** @var SpecPriceDto $specPrice */
  foreach ($specPrices as $specPrice): ?>
    <tr>
      <td class="dark" align="center"><?= $specPrice->sort ?></td>
      <td class="light"><?= $specPrice->code ?></td>
      <td class="dark"><?= $specPrice->title ?></td>
      <td class="light" style="text-align: right"><?= $specPrice->amount() ?></td>
      <td class="dark">
        <?= $specPrice->titleSportByType() ?>
      </td>
      <td class="light" style="text-align: center">
        <?= $specPrice->forAll ? '<i class="fas fa-check"></i>' : '' ?>
      </td>
      <td class="light">
        <a href="<?= Service::structure()->getPageHrefByKey('spec_prices') ?>/editTime/specPriceId/<?= $specPrice->spriceId ?>" class="btnEdit">
          <?= lang('Validity in time', 'config_spec_price') ?>
        </a>
      </td>
      <td class="light">
        <a href="<?= Service::structure()->getPageHrefByKey('spec_prices') ?>/edit/specPriceId/<?= $specPrice->spriceId ?>" class="btnEdit">
          <?= lang('button_update') ?>
        </a>
      </td>
      <td class="light">
        <a href="<?= Service::structure()->getPageHrefByKey('spec_prices') ?>/remove/sprice_id/<?= $specPrice->spriceId ?>"
           onclick="return ifConfirm()" class="btnRemove">
          <?= lang('button_remove') ?>
        </a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>