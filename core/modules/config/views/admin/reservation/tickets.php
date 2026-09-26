<?php

/**
 * @var ConfigModel $models
 */

use AC\core\modules\config\models\ConfigModel;

?>

<tr>
  <td colspan="2" class="dark" style="text-align: center;font-weight: bold"><?= lang('How should a subscription hour be credited?', 'config') ?></td>
</tr>
<tr>
  <td class="dark" style="text-align: right"><?= lang('The actual, possibly discounted, subscription price should be credited', 'config') ?>
  </td>
  <td class="light"><input type="radio" class="input" name="ticket[refund_method_full_price]" value="0" id="use_season_in_choosing_type_sport"
      <?= (!isset($models->ticket->refund_method_full_price) || $models->ticket->refund_method_full_price == 0 ?
        ' checked' : '') ?> >
  </td>
</tr>
<tr>
  <td class="dark" style="text-align: right"><?= lang('The hourly rate from the price list should be credited', 'config') ?>
  </td>
  <td class="light"><input type="radio" class="input" name="ticket[refund_method_full_price]" value="1" id="use_season_in_choosing_type_sport"
      <?= ($models->ticket->refund_method_full_price == 1 ?
        ' checked' : '') ?> >
  </td>
</tr>
