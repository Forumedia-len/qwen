<?php
/** @var \AC\core\modules\stocks\entities\dto\StockDto $stock */
/** @var string $sports */

/** @var array $groups */

use AC\core\modules\stocks\entities\dto\StockDto;
use AC\core\system\helpers\NumberHelper;

?>

<form action="<?= Service::structure()->getPageHrefByKey('stocks') ?>" method="post">
  <input type="hidden" name="action" value="<?= (!$stock->stockId ? 'create' : 'update') ?>">
  <input type="hidden" name="stock_id" value="<?= $stock->stockId ?>">
  <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
    <tr>
      <th colspan="2"><?= (!$stock->stockId ? lang('title_create') : lang('title_update')) ?></th>
    </tr>
    <tr>
      <td class="dark"><?= lang('Line', 'config_stock') ?>:</td>
      <td class="dark">
        <input type="text" name="sort" class="input wide" value="<?= $stock->sort ?? '' ?>"/>
      </td>
    </tr>
    <tr>
      <td class="light"><?= lang('Letter code', 'config_stock') ?>:</td>
      <td class="light">
        <input type="text" name="code" class="input wide" maxlength="2"
               value="<?= $stock->code ?? '' ?>"/>
      </td>
    </tr>
    <tr>
      <td class="dark"><?= lang('Title') ?>:</td>
      <td class="dark">
        <input type="text" name="title" class="input wide"
               value="<?= $stock->title ?? '' ?>"/>
      </td>
    </tr>
    <tr>
      <td class="dark"><?= lang('Type') ?>:</td>
      <td class="dark">
        <input type="radio" name="dimension" value="1" <?= $stock->dimension === '1' ? 'checked' : '' ?> />
        <?= CURR_VALUTE ?>
        <input type="radio" name="dimension" value="2" <?= $stock->dimension === '2' ? 'checked' : '' ?> />
        %
      </td>
    </tr>
    <tr>
      <td class="light"><?= lang('Rate') ?>, <?= CURR_VALUTE ?>:</td>
      <td class="light">
        <input type="text" name="rate" class="input wide"
               value="<?= NumberHelper::format($stock->rate, 2, ',', '.') ?>"/>
      </td>
    </tr>
    <tr>
      <td class="dark" colspan="2" style="text-align:right;padding: 7px">
        <?= lang('Set a negative value if the options are used as a discount.', 'config_stock') ?>
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
    <?php if ($groups && count($groups)): ?>
      <tr>
        <td class="light"><?= lang('Group', 'stocks_groups') ?>:</td>
        <td class="light">
          <select name="group" class="input wide">
            <option value=""><?= lang('no') ?></option>
            <?php foreach ($groups as $group): ?>
              <option value="<?= $group->id ?>"
                <?= $stock->group == $group->id ? 'selected' : '' ?>
                <?= !$group->active ? 'disabled' : '' ?>>
                <?= $group->title ?>
              </option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
    <?php endif; ?>
    <tr>
      <td class="dark"><?= lang('For all users', 'config_stock') ?>:</td>
      <td class="dark">
        <input type="checkbox" name="for_all" class="input" value="1"
          <?= ($stock->forAll == 1) ? 'checked' : '' ?> />
      </td>
    </tr>
    <tr>
      <td class="dark"><?= lang('calculate_only_once', 'config_stock') ?>:</td>
      <td class="dark">
        <input type="checkbox" name="only_once" class="input" value="1"
          <?= ($stock->onlyOnce == 1) ? 'checked' : '' ?> />
      </td>
    </tr>
    <tr>
      <th align="center" colspan="2">
        <input type="submit" value="<?=(!$stock->stockId ? lang('button_create') : lang('button_update')) ?>" class="button">
        &nbsp;
        <input type="reset" value="<?= lang('button_reset') ?>" class="button">
      </th>
    </tr>
  </table>
</form>