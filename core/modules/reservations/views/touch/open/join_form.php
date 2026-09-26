<?php
/**
 * @var OrdersModelOpenReservation $model
 * @var  object                    $date
 */

use AC\core\modules\reservations\models\OrdersModelOpenReservation;

?>
<div class="inner-window-content">
  <form name="order" action="reservations.php" method="post">
    <input type="hidden" name="action" value="join"/>
    <input type="hidden" name="main_reservation_id" value="<?= $model->main_reservation_id ?>"/>
    <input type="hidden" name="area_id" value="<?= $model->area_id ?>"/>
    <input type="hidden" name="type_id" value="<?= $model->type_id ?>"/>
    <input type="hidden" name="sport_id" value="<?= $model->sport_id ?>"/>
    <input type="hidden" name="date" value="<?= $model->date ?>"/>
    <input type="hidden" name="time" value="<?= $model->time ?>"/>
    <input type="hidden" name="page" value="<?= $model->page ?>"/>
    <div class="alignC">
      <span class="f28">
    <?= $model->areas->type_title . ' - ' . $model->areas->sport_title . ', ' . $model->areas->title ?>
    <br/><?= $date->date . ', ' . $date->weekday ?><br/>
    <?php foreach ($date->times as $time) { ?>
      <?= $time->title . ' '.lang('Clock').'<br />' ?>
    <?php } ?>
      </span>
      <div style="font-size: 17px;margin: 7px 0"><?= lang('Thank you for your booking. You hereby acknowledge that any non-member fees will be charged to the main playe', 'show_order')?>
      </div>
      <p>
        <input type="submit" name="go" value="<?= lang('Yes')?>" class="big-button" onMouseDown="selectArea(this, 'big-button-sel')" onMouseUp="selectArea(this, 'big-button')"/>
      </p>
    </div>
  </form>
</div>
