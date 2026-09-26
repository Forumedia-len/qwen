<?php
/**
 * @var  $groups
 * @var array $types
 */
?>

<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
  <tr>
    <th colspan="5"><?= lang('Groups of booking options', 'stocks_groups') ?></th>
  </tr>
  <tr>
    <th><?= lang('Title') ?></th>
    <th><?= lang('Comment') ?></th>
    <th><?= lang('Type') ?></th>
    <th><?= lang('Active') ?></th>
    <th><?= lang('Action') ?></th>
  </tr>

  <?php if ($groups) {
    foreach ($groups as $group) { ?>
      <tr>
        <td class="dark"><?= $group->title ?></td>
        <td class="light"><?= $group->description ?></td>
        <td class="light"><?= $group->type ? $types[$group->type]->title : ''?></td>
        <td class="light" style="text-align: center">
          <a href="<?= Service::structure()->getPageHrefByKey('stocks_groups')?>/active/id/<?= $group->id ?>">
            <?= $group->active ? useLayout()->svg('active') : useLayout()->svg('not_active') ?>
          </a>
        </td>
        <td class="dark">
          <a href="<?= Service::structure()->getPageHrefByKey('stocks_groups')?>/option/id/<?= $group->id ?>" class="btnEdit">
             <?= lang('Lettercode') ?>
          </a>&nbsp;
          <a href="<?= Service::structure()->getPageHrefByKey('stocks_groups')?>/edit/id/<?= $group->id ?>" class="btnEdit">
             <?= lang('button_update') ?>
          </a>&nbsp;
          <a href="<?= Service::structure()->getPageHrefByKey('stocks_groups')?>/remove/id/<?= $group->id ?>" onclick="return ifConfirm ()" class="btnRemove"><?= lang('button_remove') ?></a>
        </td>
      </tr>
      <?php
    }
  } ?>
</table>
