<?php
/**
 * @var array $stocks
 * @var bool  $useConditions
 */

use AC\core\modules\stocks\entities\dto\StockDto;
use AC\core\system\helpers\StringHelper;

?>

<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
  <tr>
    <th><?= lang('Line', 'config_stock') ?></th>
    <th><?= lang('Letter code', 'config_stock') ?></th>
    <th><?= lang('Title') ?></th>
    <th><?= lang('Rate') ?></th>
    <th><?= lang('title_choice_type_sport', 'config_stock') ?></th>
    <?php if ($useConditions): ?>
      <th><?= lang('Group', 'stocks_groups') ?></th>
    <?php endif; ?>
    <th><?= lang('For all users', 'config_stock') ?></th>
    <th><?= lang('calculate_only_once', 'config_stock') ?></th>
    <th colspan="3"><?= lang('Action') ?></th>
  </tr>
  <?php /** @var StockDto $stock */
  foreach ($stocks as $stock): ?>
    <tr>
      <td class="dark" align="center"><?= $stock->sort ?></td>
      <td class="light"><?= $stock->code ?></td>
      <td class="dark"><?= $stock->title ?></td>
      <td class="light"><?= $stock->amount() ?></td>
      <td class="dark">
        <?= $stock->titleSportByType() ?>
      </td>
      <?php if ($useConditions): ?>
        <td class="dark">
          <?php if (count($stock->conditions)): ?>
            <?php foreach ($stock->conditions as $condition): ?>
              <?php if ($condition->active): ?>
                <a href="<?= Service::structure()->getPageHrefByKey('stocks_groups') . '/condition/id/' . $condition->id ?>"
                   class="btnEdit"><?= StringHelper::shield($condition->title) ?></a>
              <?php else: ?>
                <span style="pointer-events: none; color: #6d6d6d;">
                <?= StringHelper::shield($condition->title) ?>
              </span>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </td>
      <?php endif; ?>
      <td class="light" style="text-align: center">
        <?= $stock->forAll ? '<i class="fas fa-check"></i>' : '' ?>
      </td>
      <td class="light" style="text-align: center">
        <?= $stock->onlyOnce ? '<i class="fas fa-check"></i>' : '' ?>
      </td>
      <td class="light">
        <a href="<?= Service::structure()->getPageHrefByKey('stocks') ?>/editTime/stock_id/<?= $stock->stockId ?>" class="btnEdit">
          <?= lang('Validity in time', 'config_stock') ?>
        </a>
      </td>
      <td class="light">
        <a href="<?= Service::structure()->getPageHrefByKey('stocks') ?>/edit/stock_id/<?= $stock->stockId ?>" class="btnEdit">
          <?= lang('button_update') ?>
        </a>
      </td>
      <td class="light">
        <a href="<?= Service::structure()->getPageHrefByKey('stocks') ?>/remove/stock_id/<?= $stock->stockId ?>"
           onclick="return ifConfirm()" class="btnRemove">
          <?= lang('button_remove') ?>
        </a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>