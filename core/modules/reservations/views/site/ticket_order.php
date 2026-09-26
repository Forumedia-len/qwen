<?php
/**
 * @var TicketsModelOrder $model
 * @var AreasModel        $area_data
 * @var float             $return_price
 * @var string            $h2
 */

use AC\core\modules\areas\models\AreasModel;
use AC\core\modules\reservations\models\TicketsModelOrder;
use AC\core\system\helpers\NumberHelper;

?>
<form name="order" action="reservations.php" method="post">
  <h2 style="padding:0 0 10px 0;"><?= $area_data->type_title . ' - ' . $area_data->sport_title . ', ' . $area_data->title ?>
    <br/><?= $date->date . ', ' . $date->weekday ?><br/>
    <?php foreach ($date->times as $time) { ?>
      <?= $time->title . ' '.lang('Clock').'<br />' ?>
    <?php } ?>
  </h2>
    <?php if($message_view):?>
  <p style="padding:0 0 10px 0;font-size: 16px"><?= lang('This will be added to your credit account.', 'show_order', ['price' =>  NumberHelper::format($return_price), 'currency' =>CURR_VALUTE])?>
  </p>
    <?php else:?>
        <p style="padding:0 0 10px 0;font-size: 16px"><?= lang('Do you want to delete your booking definitively?', 'show_order')?>
        </p>
    <?php endif;?>
  <input type="hidden" name="action" value="removeTicketProceed" />
  <input type="hidden" name="ticket_id" value="<?= $model->ticket_id ?>"/>
  <input type="hidden" name="area_id" value="<?= $model->area_id ?>"/>
  <input type="hidden" name="type_id" value="<?= $model->type_id ?>"/>
  <input type="hidden" name="sport_id" value="<?= $model->sport_id ?>"/>
  <input type="hidden" name="date" value="<?= $model->date ?>"/>
  <input type="hidden" name="time" value="<?= $model->time ?>"/>
  <input type="hidden" name="page" value="<?= $model->page ?>"/>
  <p>
    <input value="<?= lang('confirm')?>" class="button" type="submit" />
  </p>
</form>
