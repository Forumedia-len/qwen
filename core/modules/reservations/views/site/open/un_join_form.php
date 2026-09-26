<?php
/**
 * @var OrdersModelOpenReservation $model
 */

use AC\core\modules\reservations\models\OrdersModelOpenReservation;

?>
<div class="inner-window-content">
  <form action="reservations.php" method="post">
    <div class="alignC">
      <input type="hidden" name="action" value="unJoin"/>
      <input type="hidden" name="area_id" value="<?= $model->area_id ?>"/>
      <input type="hidden" name="type_id" value="<?= $model->type_id ?>"/>
      <input type="hidden" name="sport_id" value="<?= $model->sport_id ?>"/>
      <input type="hidden" name="page" value="<?= $model->page ?>"/>
      <input type="hidden" name="reservation_id" value="<?= $model->main_reservation_id ?>"/>
      <input type="hidden" name="date" value="<?= $model->date ?>"/>
      <input type="hidden" name="time" value="<?= $model->time ?>"/>
      <div class="alignC"><br/><strong class="f28 red"><?= lang('are you sure')?>?</strong></div>
      <br/><br/><br/>
      <input type="submit" name="go" value="<?= lang('Yes')?>" class="big-button"/>
    </div>
  </form>
</div>
