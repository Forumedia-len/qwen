<?php
/** @var \AC\core\modules\specPrices\entities\dto\SpecPriceDto $specPrice
 * @var string $sports
 */

use AC\core\modules\specPrices\entities\dto\SpecPriceDto;
use AC\core\system\helpers\NumberHelper;

?>

<form action="<?= Service::structure()->getPageHrefByKey('spec_prices') ?>" method="post">
  <input type="hidden" name="action" value="<?= (!$specPrice->spriceId ? 'create' : 'update') ?>">
  <input type="hidden" name="sprice_id" value="<?= $specPrice->spriceId ?>">
  <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
    <tr>
      <th colspan="2"><?= (!$specPrice->spriceId ? lang('title_create') : lang('title_update')) ?></th>
    </tr>
    <tr>
      <td class="dark"><?= lang('Line', 'config_spec_price') ?>:</td>
      <td class="dark">
        <input type="text" name="sort" class="input wide" value="<?= $specPrice->sort ?? '' ?>"/>
      </td>
    </tr>
    <tr>
      <td class="light"><?= lang('Abbreviation', 'config_spec_price') ?>:</td>
      <td class="light">
        <input type="text" name="code" class="input wide" maxlength="2"
               value="<?= $specPrice->code ?? '' ?>"/>
      </td>
    </tr>
    <tr>
      <td class="dark"><?= lang('Title') ?>:</td>
      <td class="dark">
        <input type="text" name="title" class="input wide"
               value="<?= $specPrice->title ?? '' ?>"/>
      </td>
    </tr>
    <tr>
      <td class="light"><?= lang('Prices') ?>, <?= CURR_VALUTE ?>:</td>
      <td class="light">
        <input type="text" name="rate" class="input wide"
               value="<?= NumberHelper::format($specPrice->rate, 2, ',', '.') ?>"/>
      </td>
    </tr>
    <?php if (!empty($sports)): ?>
      <tr>
        <td class='light'><?= lang('title_choice_type_sport', 'config_stock') ?>:</td>
        <td class="light">
          <?= $sports ?>
        </td>
      </tr>
    <?php endif; ?>
    <tr>
      <td class="dark"><?= lang('For all users', 'config_spec_price') ?>:</td>
      <td class="dark">
        <input type="checkbox" name="for_all" class="input" value="1"
          <?= ($specPrice->forAll == 1) ? 'checked' : '' ?> />
      </td>
    </tr>
    <?php if (!empty($specPrice->conditions)) : ?>
      <?php foreach ($specPrice->conditions as $condition): ?>
        <tr>
          <td class="light" title="<?= $condition->description ?>"><?= $condition->title ?>:</td>
          <td class="light">
            <?= $condition->asHtml() ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    <tr>
      <th align="center" colspan="2">
        <input type="submit" value="<?= (!$specPrice->spriceId ? lang('button_create') : lang('button_update')) ?>" class="button">
        &nbsp;
        <input type="reset" value="<?= lang('button_reset') ?>" class="button">
      </th>
    </tr>
  </table>
</form>