<?php
/**
 * @var object $item
 * @var string $backUrl
 */

?>
<div class="content news">
  <div class="news-item">
    <h2 class="headerContent"><?= $item->title ?></h2>
    <div class="news-item-content">
      <p><?= $item->content ?></p>
      <p><a href="<?= $backUrl ?>" class="a-back"><?= lang('back') ?></a></p>
    </div>
  </div>
</div>
