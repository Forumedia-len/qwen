<?php
/**
 * @var array $row
 */
?>
<p style="padding: 0;margin: 0">
  <?php if(!empty($row['title'])):?>
    <span style="font-size: 15px;font-weight: bold" class="blue"><?= $row['title'] ?></span>
  <?php endif;?>
  <?php
  if (!empty($row['items'])):
    foreach ($row['items'] as $key => $item):?>
      <?php if ($key != 0): ?> | <?php endif; ?>
      <?php if (isset($item['active']) && $item['active']): ?>
        <span style="font-weight: bold"><?= $item['value'] ?></span>
      <?php else: ?>
        <a href="<?= $item['link'] ?>"><?= $item['value'] ?></a>
      <?php endif; ?>
    <?php endforeach; endif; ?>
</p>

