<?php
/**
 * @var array  $menu
 * @var string $currentItem
 */

foreach ($menu as $key => $item) {
  if ($item->key == $currentItem) { ?>
    <strong><?= $item->title ?></strong>
  <?php } else { ?>
    <a href="<?= $item->href ?>"><?= $item->title ?></a>
  <?php }
  if(count($menu) > $key + 1) { ?>
     |
  <?php }
}
?>
