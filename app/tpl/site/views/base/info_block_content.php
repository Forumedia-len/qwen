<?php
/**
 * @var $header
 * @var $content
 * @var $back_href
 * @var $style
 * @var $footer
 * @var $class
 */
?>
<div class="content aroundBox">
  <div class="header-block">
    <?php if ($header) { ?>
      <h2><?= $header ?></h2>
    <?php } ?>
  </div>
  <div class="<?= $class ?>" style="<?= $style ?>">
    <?= $content ?>
  </div>
  <div class="footer-block">
    <?php if ($back_href !== false) { ?>
      <a href="<?= $back_href ?>" class="back"><?= lang('Back')?></a>
    <?php } ?>
    <?= $footer ?>
  </div>
</div>
