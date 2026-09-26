<?php
/**
 * @var \AC\core\modules\reservations\entities\dto\PriceOptionsFormBlockDto $priceOptionsBlock - доступные опции
 */

use AC\core\modules\reservations\entities\dto\PriceOptionFormInputDto;
use AC\core\modules\reservations\entities\dto\PriceOptionsFormBlockDto;

?>
<?php if (count($priceOptionsBlock->priceOptions) > 0) { ?>
  <span class="zag"><?= $priceOptionsBlock->h3 ?></span>
  <?php if (!empty($priceOptionsBlock->description)) { ?>
    <span class="font20"><?= $priceOptionsBlock->description ?></span><br/>
  <?php } ?>
  <table class="items">
    <?php
    /** @var PriceOptionFormInputDto $option */
    foreach ($priceOptionsBlock->priceOptions as $option) { ?>
      <tr>
        <td>
          <input type="<?= $option->typeInput ?>" name="<?= $option->name ?>" id="<?= $option->id ?>"
                 value="<?= $option->value ?>" class="<?= $option->classInput ?>" <?= ($option->checked ? 'checked="checked"' : '') ?>>
          <span class="<?= $option->podlogInput ?>"></span>
        </td>
        <td class="td-item-label">
          <label for="<?= $option->id ?>"><?= $option->title ?></label>
        </td>
      </tr>
    <?php } ?>
    <?php if ($priceOptionsBlock->alias === 'stock') { ?>
      <tr>
        <td colspan="2">
          <div class='hidden_stock hidden'>
            <p class='text comment-title red'><?= lang('show_order_text_stock_less_than_zero', 'show_order') ?></p>
          </div>
        </td>
      </tr>
    <?php } ?>
  </table>
<?php } else { ?>
  <input name="<?= $priceOptionsBlock->alias . '_id' ?>" value="<?= $priceOptionsBlock->value ?>" type='hidden' class='radio'/><?php } ?>
