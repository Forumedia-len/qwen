<?php
/**
 * @var array      $templates
 * @var string     $baseUrl
 * @var array      $typeModes
 * @var string|int $activeType
 *
 */

use AC\core\modules\mailing\entities\dto\LetterTemplateDto;
use AC\core\modules\mailing\entities\enums\ModeTemplate;

?>
<h1><?= lang('Email Templates', 'config_letters') ?></h1>
<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">
  <?php if (!empty($typeModes)) { ?>
    <caption>
      <div class='list-type list-type-content'>
        <?php /** @var ModeTemplate $mode */
        foreach ($typeModes as $mode): ?>
          <div class="item-type <?= ($activeType === $mode->value ? 'active' : '') ?>">
            <a href="<?= $baseUrl ?>?typeMode=<?= $mode->value ?>"><?= $mode->label() ?></a>
          </div>
        <?php endforeach; ?>
      </div>
    </caption>
  <?php } ?>
  <tr>
    <th><?= lang('Subject', 'config_letters') ?></th>
    <th colspan="2"><?= lang('Action') ?></th>
  </tr>
  <?php
  /** @var LetterTemplateDto $row */
  foreach ($templates as $row) { ?>
    <tr>
      <td class="light"><?= $row->getTitle() ?></td>
      <td class="dark">
        <a href="<?= $baseUrl ?>/update?typeMode=<?= $row->getMode() ?>&alias=<?= $row->getAlias() ?>" class="btnEdit"><?= lang('button_update') ?></a>
        <?php if ($row->useRemove ?? false) { ?>
          &nbsp;
          <a href="<?= $baseUrl ?>/remove?typeMode=<?= $row->getMode() ?>&alias=<?= $row->getAlias() ?>" class="btnRemove"><?= lang('button_remove') ?></a>
        <?php } ?>

      </td>
    </tr>
  <?php } ?>
</table>
