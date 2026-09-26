<?php
/**
 * @var \AC\core\modules\news\entities\dto\NewsDto[] $news
 */
?>
<?php if (!empty($news)) { ?>
  <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
    <tr>
      <th><?= lang('Date') ?></th>
      <th width="50%"><?= lang('Title') ?></th>
      <th><?= lang('Publish') ?></th>
      <th colspan="2"><?= lang('Action') ?></th>
    </tr>
    <?php foreach ($news as $item) { ?>
      <tr>
        <td class="dark" align="center"><?= $item->getDate('d.m.Y') ?: '' ?></td>
        <td class="light">
          <?= htmlspecialchars($item->getTitle()) ?>
        </td>
        <td class="dark" align="center"><?= ($item->isPublished() ? lang('Yes') : lang('Not')) ?></td>
        <td class="light">
          <a href="<?= Service::structure()->getPageHrefByKey('news') ?>/edit/news_id/<?= $item->getId() ?>" class="btnEdit">
            <?= lang('button_update') ?>
          </a>
        </td>
        <td class="light">
          <a href="<?= Service::structure()->getPageHrefByKey('news') ?>/remove/news_id/<?= $item->getId() ?>"
             onclick="return ifConfirm()" class="btnRemove">
            <?= lang('button_remove') ?>
          </a>
        </td>
      </tr>
    <?php } ?>
  </table>
<?php } else { ?>
  <p><?= lang('No data') ?></p>
<?php } ?>

