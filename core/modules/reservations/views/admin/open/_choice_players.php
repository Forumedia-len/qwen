<?php
/**
 * @var array $clients
 * @var int $type_id
 * @var int $sport_id
 * @var int $area_id
 */

use AC\core\modules\clients\models\ClientsModel;

?>
<?php if (config('DoubleGame')->doubleGameEnabled($type_id, $sport_id, $area_id)) { ?>
  <?php if (!config('DoubleGame')->onlyDoubleGame($type_id, $sport_id, $area_id)) { ?>
    <tr>
      <td class="light"><?= lang('text title single or double') ?> :</td>
      <td class="light">
        <div class="type-reservation">
          <div class="type-reservation-item">
            <input type="radio" name="type_reservation" value="1" checked/>
            <label><?= lang('Single_title') ?></label>
          </div>
          <div class="type-reservation-item">
            <input type="radio" name="type_reservation" value="2"/>
            <label><?= lang('Double_title') ?></label>
          </div>
        </div>
      </td>
    </tr>
  <?php } else { ?>
    <input type="radio" name="type_reservation" value="2" checked style="display: none"/>
  <?php } ?>
<?php } ?>
<?php for ($i = 2; $i <= config('DoubleGame')->numberPlayers($type_id, $sport_id, $area_id); $i++) {
  /**if($i > 2 && !$use_double) { ?>
   * <tr class="reservation-clients-item reservation-clients-item-hidden client-hidden" >
   * <td class="error-select" colspan="2" style="padding: 5px;color: red">
   * <?= lang('message_not_count_time_period', 'message_error') ?>
   * </td>
   * </tr>
   * <script>
   * $('body').on('change', '[name=type_reservation]', function () {
   * if ($(this).val() == '2') {
   * $('[type=submit]').prop("disabled", true);
   * } else {
   * $('[type=submit]').removeAttr('disabled');
   * }
   * })
   * </script>
   * <?php break; } **/ ?>
  <tr class="reservation-clients-item <?= ($i !== 2 && !config('DoubleGame')->onlyDoubleGame($type_id, $sport_id, $area_id)  ? 'reservation-clients-item-hidden client-hidden' : '') ?>">
    <td class="<?= $i % 2 == 0 ? 'dark' : 'light' ?>"><?= lang('Players', 'show_order') ?> <?= $i ?> :</td>
    <td class="<?= $i % 2 == 0 ? 'dark' : 'light' ?>">
      <select name="friends[<?= $i ?>]" id="friend_<?= $i ?>" class="select2-find">
        <option value="0"><?= lang('please choose', 'show_order') ?></option>
        <option value="guest"><?= lang('GUEST', 'show_order') ?></option>
        <?php if (defined('PRICE_FOR_GUEST_CHILD') && PRICE_FOR_GUEST_CHILD) { ?>
          <option value="guest-child"><?= lang('Guest 2 (child)', 'show_order') ?></option>
        <?php } ?>
        <?php
        /** @var $client ClientsModel */
        foreach ($clients as $client) { ?>
          <option value="<?= $client->client_id ?>"><?= $client->surname . ' ' . $client->name ?></option>
        <?php } ?>
      </select>
      <div style="padding:8px 0 0 24px; display:none" id="secondPlayerBox_<?= $i ?>">
        <div class="guest-item">
          <label for="name-box<?= $i ?>" class="input" style="margin-right: 20px"><?= lang('First name') ?>:</label>
          <input type="text" name="guest_name[<?= $i ?>]" value="" id="name-box<?= $i ?>" class="input"
                 style="width:184px"/>
        </div>
        <div class="guest-item">
          <label for="s-name-box<?= $i ?>" class="input"><?= lang('Family name') ?>:</label>
          <input type="text" name="guest_surname[<?= $i ?>]" value="" id="s-name-box<?= $i ?>" class="input"
                 style="width:184px"/>
        </div>
      </div>
    </td>
  </tr>
<?php } ?>
<!--<script>-->
<!--  $(document).ready(function () {-->
<!--    $('.select2-find-frends').select2({-->
<!--      theme: 'classic',-->
<!--      width: '160px'-->
<!--    })-->
<!--  })-->
<!--</script>-->
