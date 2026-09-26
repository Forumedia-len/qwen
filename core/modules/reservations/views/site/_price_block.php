<?php
/**
 * @var array $prices
 */
//выводим цену заказа
?>
<div class="block-prices">
  <div class="price-block-item item-price">
    <div class="price-item-title">
      <?= lang('show_order_block_prices_price', 'show_order') ?>
    </div>
    <div class="price-item-value">
      <span class="price"><?=$prices['price']?></span>
    </div>
  </div>
  <div class="price-block-item item-extra">
    <div class="price-item-title">
      <?= lang(
        'show_order_block_prices_extra',
        'show_order')?>
    </div>
    <div class="price-item-value">
      <span class="extra"><?=$prices['extra']?></span>
    </div>
  </div>
  <div class="price-block-item item-discount">
    <div class="price-item-title">
      <?= lang(
        'show_order_block_prices_discount',
        'show_order')?>
    </div>
    <div class="price-item-value">
      <span class="discount"><?=$prices['discount']?></span>
    </div>
  </div>
  <div class="price-block-item item-spec-price">
    <div class="price-item-title">
      <?= lang(
        'show_order_block_prices_spec_price',
        'show_order')?>
    </div>
    <div class="price-item-value">
      <span class="spec_price"><?=$prices['spec_price']?></span>
    </div>
  </div>
  <div class="price-block-item item-stock">
    <div class="price-item-title">
      <?= lang(
        'show_order_block_prices_stock',
        'show_order')?>
    </div>
    <div class="price-item-value">
      <span class="stock"><?=$prices['stock']?></span>
    </div>
  </div>
  <?php foreach ($prices['webIo'] as $alias => $st_price) { ?>
    <div class="price-block-item item-webIo item-<?=$alias?> <?=($st_price['hidden'] ? 'hidden' : '')?>">
      <div class="price-item-title">
        <?= lang(
          'show_order_block_prices_webIo_' . $alias,
          'show_order')?>
      </div>
      <div class="price-item-value">
        <span class="<?=$alias?>"><?=$st_price['price']?></span>
      </div>
    </div>
  <?php }?>
  <div class="price-block-item item-sum-price">
    <div class="price-item-title">
      <?= lang(
        'show_order_block_prices_sum_price',
        'show_order')?>
    </div>
    <div class="price-item-value">
      <span class="sum_price"><?=$prices['sum_price']?></span>
    </div>
  </div>
</div>