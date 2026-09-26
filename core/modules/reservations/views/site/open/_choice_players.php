<?php
/**
 * @var array                      $clients
 * @var OrdersModelOpenReservation $model - данные площадки
 * @var object                     $guest - форма гостя
 * @var bool                     $use_double - возможна двойная игра или нет
 */


use AC\core\modules\clients\models\ClientsModel;
use AC\core\modules\reservations\models\OrdersModelOpenReservation;
use AC\core\system\helpers\StringHelper;

?>
<?php if (config('DoubleGame')->doubleFriendsEnabled($model->type_id, $model->sport_id, $model->area_id)) { ?>
  <div class="orderItemBox">
    <?php if (config('DoubleGame')->doubleGameEnabled($model->type_id, $model->sport_id, $model->area_id)) { ?>
      <?php if (!config('DoubleGame')->onlyDoubleGame($model->type_id, $model->sport_id, $model->area_id)) { ?>
        <div class="type-reservation">
          <div style="float: left;width: 100%;padding-bottom: 10px""><?= lang('text title single or double') ?> :</div>
          <div>
            <div class="type-reservation-item">
              <input type="radio" name="type_reservation" value="1" class="radio" checked/>
              <span class="podlog"></span><label><?= lang('Single_title') ?></label>
            </div>
            <div class="type-reservation-item">
              <input type="radio" name="type_reservation" value="2" class="radio"/>
              <span class="podlog"></span><label><?= lang('Double_title') ?></label>
            </div>
          </div>
        </div>
      <?php } else { ?>
        <input type="radio" name="type_reservation" value="2" checked style="display: none"/>
      <?php } ?>
    <?php } ?>
    <div class="reservation-clients" style="margin: 10px 0">
      <div class="reservation-clients-item">
        <div class="reservation-clients-item-title"><?= lang('main_player', 'show_order') ?> :</div>
        <div class="reservation-clients-item-content">
          <?= StringHelper::shield($model->current_client->surname . ' ' . $model->current_client->name, doubleEncode: false) ?>
        </div>
      </div>
      <?php for ($i = 2; $i <= config('DoubleGame')->numberPlayers($model->type_id, $model->sport_id, $model->area_id); $i++) {
        if ($i > 2 && (!config('DoubleGame')->onlyDoubleGame($model->type_id, $model->sport_id, $model->area_id) && !$use_double)) { ?>
        <div class="reservation-clients-item second-item reservation-clients-item-hidden client-hidden">
          <div class="error-select">
            <?= lang('message_not_count_time_period', 'message_error') ?>
          </div>
        </div>
        <script>
          $('body').on('change', '[name=type_reservation]', function () {
            if ($(this).val() == '2') {
              $('[type=submit]').prop('disabled', true)
            } else {
              $('[type=submit]').removeAttr('disabled')
            }
          })
        </script>
      <?php break;
      } ?>
        <div class="reservation-clients-item second-item <?= ($i !== 2  ? 'reservation-clients-item-hidden ' . (!config('DoubleGame')->onlyDoubleGame($model->type_id, $model->sport_idl, $model->area_id)  ? 'client-hidden' : '') : '') ?>"
             id="block_friend_<?= $i ?>">
          <div class="error-select"></div>
          <div class="reservation-clients-item-title reservation-clients-item-title-select"><label for="friend_<?= $i ?>"><?= lang('Players',
                'show_order') ?> <?= $i ?> :</label></div>
          <div class="reservation-clients-item-content">
            <select name="friends[<?= $i ?>]" id="friend_<?= $i ?>" class="select2-find">
              <option value="0"><?= lang('please choose', 'show_order') ?></option>
              <?php if ($guest->visible) { ?>
                <option value="guest" data-club-state="0"><?= lang('GUEST', 'show_order') ?></option>
              <?php } ?>
              <?php if ($guest->visible && defined('PRICE_FOR_GUEST_CHILD') && PRICE_FOR_GUEST_CHILD) { ?>
                <option value="guest-child" data-club-state="0"><?= lang('Guest 2 (child)', 'show_order') ?></option>
              <?php } ?>
              <?php
              /** @var $client ClientsModel */
              foreach ($clients as $client) {
                ?>
                <option value="<?= $client->client_id ?>"
                        data-club-state="<?= (int)$client->club_state ?>"><?= StringHelper::shield($client->surname . ' ' . $client->name,
                    doubleEncode: false) ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="reservation-clients-item-info">
            <?php if ($i == 2) { ?>
              <div><?= lang('Only players who are still bookable and have not already booked further bookings in the system appear here. (Booking limit)',
                  'show_order') ?></div>
            <?php } else { ?>
              <div></div>
            <?php } ?>
          </div>
          <?php if ($guest->visible) { ?>
            <div style="padding:8px 0 0; display:none" id="secondPlayerBox_<?= $i ?>">
              <div class="guest-item">
                <label for="name-box_<?= $i ?>" class="input" style="width: 116px;display: inline-block"><?= lang('First name') ?> :</label>
                <input type="text" name="guest_name[<?= $i ?>]" value="" id="name-box_<?= $i ?>" class="input"
                       style="width:184px"/>
              </div>
              <div class="guest-item">
                <label for="s-name-box_<?= $i ?>" class="input" style="width: 116px;display: inline-block"><?= lang('Family name') ?> :</label>
                <input type="text" name="guest_surname[<?= $i ?>]" value="" id="s-name-box_<?= $i ?>" class="input"
                       style="width:184px"/>
              </div>
            </div>
          <?php } ?>
        </div>
      <?php } ?>
      <!--      <p style="font-style: italic;font-weight: bold; padding: 8px">* Falls kein Mitglied als Mitspieler gewählt wird, wird eine Gastgebühr berechnet</p>-->
    </div>
  </div>
<?php } else { ?>
  <?php if ($guest->visible) { ?>
    <div class="error-select" style="display: none"></div>
    <div class="orderItemBox  display-block" onclick="viewHideBlock('secondPlayerBox')">
      <input type="checkbox" name="second_player" value="guest" id="second_player_value_id" class="check"/>
      <span class="podlog2"></span>
      <label><?= lang('Guest_player') ?> (+ <?= number_format($model->sum_price_array[$model->times[1]]['sum_price'], 2, ',', '') ?>
        <?= CURR_VALUTE ?>)</label>
    </div>
    <div style="padding:20px 0; display:none" id="secondPlayerBox">
      <div style="display: flex;justify-content: space-between;min-width: 250px;max-width: 320px;align-items: center;flex-wrap: wrap">
        <label for="name-box" class="input" style="display:inline-block;width: 108px"><?= lang('First name') ?>:</label>
        <input type="text" name="guest_name" value="" id="name-box" class="input" style="width:184px"/></div>
      <br/><br/>
      <div style="display: flex;justify-content: space-between;min-width: 250px;max-width: 320px;align-items: center;flex-wrap: wrap">
        <label for="s-name-box" class="input"><?= lang('Family name') ?>:</label>
        <input type="text" name="guest_surname" value="" id="s-name-box" class="input" style="width:184px"/>
      </div>
      <br/>
    </div>
  <?php } ?>
<?php } ?>

<?php //запрещаем выбирать одинаковых игроков?>
<script>
$('body').on('change','.reservation-clients-item-content select',function(){
    var val_option=$(this).val();
    var name=$(this).attr('id');
    $('.reservation-clients-item-content').not($(this).closest('.reservation-clients-item-content')).each(function(){
      $('option.item_'+name+':disabled',this).prop('disabled',false).removeClass('item_'+name);
      if(val_option!='0'&&val_option!='guest'){
      $('option[value='+val_option+']',this).prop('disabled',true);
      $('option[value='+val_option+']',this).addClass('item_'+name);
    }
    })

})
</script>
