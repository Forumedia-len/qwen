<?php
/**
 * @var \AC\core\modules\reservations\entities\dto\PriceOptionsFormBlockDto $priceOptionsBlock - доступные опции
 */

use AC\core\modules\reservations\entities\dto\PriceOptionFormInputDto;
use AC\core\modules\reservations\entities\dto\PriceOptionsFormBlockDto;

?>
<?php if (count($priceOptionsBlock->priceOptions) > 0) { ?>
  <div class="col-md-6" id="<?= $priceOptionsBlock->id ?>">
    <h3><?= $priceOptionsBlock->h3 ?></h3>
    <?php if (!empty($priceOptionsBlock->description)) { ?>
      <p>
        <em><?= $priceOptionsBlock->description ?></em>
      </p>
    <?php } ?>
    <?php
    /** @var PriceOptionFormInputDto $option */
    foreach ($priceOptionsBlock->priceOptions as $option) { ?>
      <p class="display-block">
        <input type="<?= $option->typeInput ?>" name="<?= $option->name ?>" id="<?= $option->id ?>"
               value="<?= $option->value ?>" class="<?= $option->classInput ?>" <?= ($option->checked ? 'checked="checked"' : '') ?>>
        <span class="<?= $option->podlogInput ?>"></span>
        <label for="<?= $option->id ?>"><?= $option->title ?></label>
      </p>
    <?php } ?>
    <?php if ($priceOptionsBlock->alias === 'stock') { ?>
      <div class="hidden_stock hidden">
        <p class="text comment-title red"><?= lang('show_order_text_stock_less_than_zero', 'show_order') ?></p>
      </div>
    <?php } ?>
  </div>
<?php } else { ?>
  <input name="<?= $priceOptionsBlock->alias . '_id' ?>" value="<?= $priceOptionsBlock->value ?>" type="hidden" class="radio"/>
<?php } ?>
