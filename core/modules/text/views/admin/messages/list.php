<?php
/**
 * @var array  $groups
 * @var string $localMenu
 * @var string $currentTypeTitle
 */

use AC\core\system\helpers\StringHelper;
?>

<h1><?= lang('config_messages_title', 'structure'); ?> ( <?= StringHelper::shield($currentTypeTitle) ?> )</h1>

<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">
  <caption><?= $localMenu ?></caption>
  <thead>
  <tr>
    <th></th>
    <th><?= lang('Title') ?></th>
    <th width="200px"><?= lang('Photo') ?></th>
    <th width="80px"><?= lang('Action') ?></th>
  </tr>
  </thead>
  <tbody>
  <?php foreach ($groups as $group): ?>
    <?php foreach ($group['messages'] as $index => $message): ?>
      <tr>
        <?php if ($index === 0): ?>
          <td class="dark" rowspan="<?= count($group['messages']) ?>">
            <?= StringHelper::shield($group['title']) ?>
          </td>
        <?php endif; ?>
        <td class="dark"><?= StringHelper::shield($message['name']) ?></td>
        <td class="light">
          <?php if (!empty($message['image'])): ?>
            <a href="<?= StringHelper::shield($message['image']) ?>" target="_blank">
              <img src="<?= StringHelper::shield($message['image']) ?>" height="50"/>
            </a>
          <?php endif; ?>
        </td>
        <td class="dark">
          <a href="<?= StringHelper::shield($message['editHref']) ?>" class="btnEdit">
            <?= lang('button_update') ?>
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php endforeach; ?>
  </tbody>
</table>

