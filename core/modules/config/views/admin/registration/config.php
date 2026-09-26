<?php
/**
 * @var ConfigModel $models
 * @var View        $this
 */


use AC\core\system\view\View;
use AC\core\modules\config\models\ConfigModel;

?>
<form action="config.php?mode=registration&action=saveConfig" method="post" onsubmit="return ifConfirm ()">
  <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">
    <tr>
      <th><?= lang('title_parameter', 'config') ?></th>
      <th><?= lang('title_value', 'config') ?></th>
    </tr>
    <tr>
      <td class="dark" align="right"><?= lang('parameter_user_activation', 'config') ?></td>
      <td class="light">
        <input type="radio" name="registration[user_activation]" value="0"
               id="user_activation_manual" <?= ($models->registration->user_activation == 0 ?
          ' checked' : '') ?>>
        <label for="user_activation_manual"><?= lang('text_user_activation_manual', 'config') ?></label>
        <input type="radio" name="registration[user_activation]" value="1"
               id="user_activation_automatic" <?= ($models->registration->user_activation == 1 ?
          ' checked' : '') ?>>
        <label for="user_activation_automatic"><?= lang('text_user_activation_automatic', 'config') ?></label>
      </td>
    </tr>
    <tr>
      <td class="dark" align="right"><?= lang('parameter_user_can_remove_abo', 'config') ?></td>
      <td class="light">
        <input type="checkbox" name="registration[user_can_remove_abo]" value="1"
               id="user_can_remove_abo" <?= (isset($models->registration->user_can_remove_abo) && $models->registration->user_can_remove_abo == 1 ?
          ' checked' : '') ?>>
      </td>
    </tr>
    <tr>
      <td class="dark" align="right"><?= lang('parameter_refund_when_cancel_ticket', 'config') ?></td>
      <td class="light">
        <input type="checkbox" class="input" name="personal_account[refund_when_cancel_ticket]" value="1" id="refund_when_cancel_ticket"
          <?= (isset($models->personal_account->refund_when_cancel_ticket) && $models->personal_account->refund_when_cancel_ticket == 1 ?
            ' checked' : '') ?> >
      </td>
    </tr>
    <?php if (config('payment')->useOnlinePayment()) { ?>
      <tr>
        <td class="dark" align="right"><?= lang('parameter_refund_for_cancellation_reservation_paid_by_paypal', 'config') ?></td>
        <td class="light">
          <input type="checkbox" class="input" name="personal_account[refund_for_cancellation_reservation_paid_by_paypal]" value="1"
                 id="refund_for_cancellation_reservation_paid_by_paypal"
            <?= (isset($models->personal_account->refund_for_cancellation_reservation_paid_by_paypal) && $models->personal_account->refund_for_cancellation_reservation_paid_by_paypal == 1
              ?
              ' checked' : '') ?> >
        </td>
      </tr>
    <?php } else { ?>
      <input type="hidden" class="input" name="personal_account[refund_for_cancellation_reservation_paid_by_paypal]"
             value="<?= (isset($models->personal_account->refund_for_cancellation_reservation_paid_by_paypal) && $models->personal_account->refund_for_cancellation_reservation_paid_by_paypal == 1
               ?
               1 : 0) ?>" id="refund_for_cancellation_reservation_paid_by_paypal">
    <?php } ?>
    <?php if (Service::auth()->checkRights(0)) { ?>
      <tr>
        <td class="dark" align="right"><?= lang('parameter_use_season_in_choosing_type_sport', 'config') ?></td>
        <td class="light">
          <input type="checkbox" class="input" name="registration[use_season_in_choosing_type_sport]" value="1" id="use_season_in_choosing_type_sport"
            <?= (isset($models->registration->use_season_in_choosing_type_sport) && $models->registration->use_season_in_choosing_type_sport == 1 ?
              ' checked' : '') ?> >
        </td>
      </tr>
    <?php } ?>
    <tr>
      <th colspan="2"><input type="submit" value="<?= lang('button_save') ?>" class="button"></th>
    </tr>
  </table>
</form>