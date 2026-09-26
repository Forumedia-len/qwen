<?php

/**
 * @var array $menu
 */
?>
<td id="section_left">
  <a href="<?= site_url() ?>" class="logotype">
    <img src="<?= base_url(paths()->getAssetsDir('images/logo_sm.png', 'common')) ?>">
  </a>
  <?php
  foreach ($menu as $menu_item) { ?>
    <a href="<?= $menu_item['url'] ?>" class="<?= $menu_item['class'] ?> memki">
      <img src="<?= $menu_item['image'] ?>" alt="<?= $menu_item['title'] ?>">
      <span><?= $menu_item['title'] ?></span>
    </a>
  <?php } ?>
</td>
