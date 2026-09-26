<?php
/**
 * @var OrdersModelOpenReservation $model
 * @var  BaseObject                $date
 */

use AC\core\modules\reservations\models\OrdersModelOpenReservation;
use AC\core\system\object\BaseObject;

?>
<form name="order" action="reservations.php" method="post">
  <input type="hidden" name="action" value="join"/>
  <input type="hidden" name="main_reservation_id" value="<?= $model->main_reservation_id ?>"/>
  <input type="hidden" name="area_id" value="<?= $model->area_id ?>"/>
  <input type="hidden" name="type_id" value="<?= $model->type_id ?>"/>
  <input type="hidden" name="sport_id" value="<?= $model->sport_id ?>"/>
  <input type="hidden" name="date" value="<?= $model->date ?>"/>
  <input type="hidden" name="time" value="<?= $model->time ?>"/>
  <input type="hidden" name="page" value="<?= $model->page ?>"/>
  <h2 style="padding:0 0 10px 0;">
    <?= $model->areas->type_title . ' - ' . $model->areas->sport_title . ', ' . $model->areas->title ?>
    <br/><?= $date->date . ', ' . $date->weekday ?><br/>
    <?php foreach ($date->times as $time) { ?>
      <?= $time->title . ' '.lang('Clock').'<br />' ?>
    <?php } ?>
  </h2>
  <p>
    <?= lang('Thank you for your booking. You hereby acknowledge that any non-member fees will be charged to the main player', 'show_order')?>
  </p>
  <p>
    <input value="<?= lang('confirm')?>" class="button" type="submit" style="margin: 0 auto!important;"/>
  </p>
</form>
