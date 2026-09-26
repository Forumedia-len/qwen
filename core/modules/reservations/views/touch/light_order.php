<?php
/**
 * @var AreasModel      $area_data
 * @var LightModelOrder $model
 * @var BaseObject      $date
 */

use AC\core\modules\areas\models\AreasModel;
use AC\core\modules\reservations\models\LightModelOrder;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\object\BaseObject;

?>
<form name="order" action="reservations.php" method="post">
  <h2 style="padding:0 0 10px 0;"><?= $area_data->type_title . ' - ' . $area_data->sport_title . ', ' . $area_data->title ?>
    <br/><?= $date->date . ', ' . $date->weekday ?><br/>
    <?php foreach ($date->times as $time) { ?>
      <?= $time->title . ' '.lang('Clock').'<br />' ?>
    <?php } ?>
  </h2>
  <?php if ($model->ticket_id !== null) { ?>
    <input type="hidden" name="ticket_id" value="<?= (int)$model->ticket_id ?>"/>
  <?php } ?>
  <?php // выводим опции света, тепла или сети?>
  <?php foreach ($model->stateLHN as $key => $state) {
    if ($state->state == 1) {
      if ($state->hidden == 1) { ?>
        <input name="<?= $key ?>_state[<?= $model->time ?>]" value="1" type="hidden"/>
      <?php } else { ?>
        <p>
          <input name="<?= $key ?>_state[<?= $model->time ?>]" value="1" type="checkbox" class="check"/>
          <span class="podlog2"></span>
          <label>
            <strong>&nbsp;<?= lang('Do you need state', 'show_order', ['state' => $state->title, 'price' => NumberHelper::format($area_data->{$key . '_price'}), 'currency' => CURR_VALUTE, 'period' =>$area_data->period])?></strong>
          </label>
        </p>
      <?php }
    }
  }
  ?>
  <input type="hidden" name="action" value="lightOrder"/>
  <input type="hidden" name="type_id" value="<?= $model->type_id ?>"/>
  <input type="hidden" name="sport_id" value="<?= $model->sport_id ?>"/>
  <input type="hidden" name="area_id" value="<?= $model->area_id ?>"/>
  <input type="hidden" name="date" value="<?= $model->date ?>"/>
  <input type="hidden" name="time" value="<?= $model->time ?>"/>
  <input type="hidden" name="page" value="<?= $model->page ?>"/>
  <p>
    <input value="<?= lang('confirm')?>" class="button" type="submit" style="width:80px;"/>
  </p>
</form>
