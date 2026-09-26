<?php
/**
 * @var string $login_type
 *  todo переделать использовать перебор массива
 */



$login_type = Service::request()->_('type', (!CODE_CARD && TYPE_LOGIN_VIEW_TAB == 'codecard' ? 'login' : TYPE_LOGIN_VIEW_TAB));
?>
<script>
  todo.onload(function () {
    selectTab(document.getElementById('box_tab_<?=$login_type?>'), 1, 'content_box_tab_<?=$login_type?>')
  })
</script>
<div id="loginBlock">
  <time data-time="300000"/>
  <?php // табы?>
  <div>
    <div class="active-tab" id="box_tab_login" onMouseDown="selectTab(this, 0, false)"
         onMouseUp="selectTab(this, 1, 'content_box_tab_login')"><?= lang('with_login_password', 'auth')?>
    </div>
    <?php if (GUEST
      && (config('payment')->useOnlinePayment()
        || (config('payment')->useCashPayment() && in_array(THE_GUEST_CAN_PLAY_IN_CASH, ['touch', 'all'], true)))
      && defined('HIDE_GUEST_MENU_TOUCH') && HIDE_GUEST_MENU_TOUCH != 'open') { ?>
      <div class="tab" id="box_tab_guest" onMouseDown="selectTab(this, 0, false)"
           onMouseUp="selectTab(this, 1, 'content_box_tab_guest')"> <?=lang('Guest_player')?>
      </div>
    <?php } ?>
    <?php if (CODE_CARD) { ?>
      <div class="tab" id="box_tab_codecard" onMouseDown="selectTab(this, 0, false)"
           onMouseUp="selectTab(this, 1, 'content_box_tab_codecard')"><?= lang('with_code_card', 'auth')?>
      </div>
    <?php } ?>

    <?php if (USE_REGISTRATION_TOUCH) { ?>
      <div class="tab" id="box_tab_card" onclick="link('registration.php')"><?= lang('registration_guests', 'auth')?></div>
    <?php } ?>
  </div>
  <?php // табы?>

  <div class="content">
    <div class="logo-box">
      <?php if(file_exists(ROOT_PATH .paths()->getAssetsDir('images/logo.png'))) { ?>
        <img src="<?= base_url(paths()->getAssetsDir('images/logo.png')) ?>" style="margin: auto" alt="logo"/>
      <?php } else { ?>
        <img src="<?= base_url(paths()->getAssetsDir('images/logo_sm.png', 'common')) ?>" style="margin: auto" alt="logo"/>
      <?php }?>
    </div>
    <div class="content-box" id="content_box_tab_login" style="display:none">
      <br/><br/><br/><br/>
      <div class="alignR"><?= lang('active_board')?> - © <?= lang('copyright_name')?></div>

      <?php if (isset($error_str)) { ?>
        <br/><span class="error"><?= $error_str ?></span>
      <?php } ?>
      <form method="post" action="<?= site_url() ?>login.php">
        <?php if ($reservation_id) { ?>
          <input type="hidden" name="reservation_id" value="<?= $reservation_id ?>"/>
        <?php } ?>
        <?php if ($area_id) { ?>
          <input type="hidden" name="area_id" value="<?= $area_id ?>"/>
        <?php } ?>
        <?php if ($date) { ?>
          <input type="hidden" name="date" value="<?= $date ?>"/>
        <?php } ?>
        <?php if ($time) { ?>
          <input type="hidden" name="time" value="<?= $time ?>"/>
        <?php } ?>
        <input type="hidden" name="type" value="login"/>
        <label for="login-box"><?= (defined('LOGIN_AS_EMAIL')&&LOGIN_AS_EMAIL)? lang('parameter_registration_field_login_input_email', 'registration_fields') :lang('parameter_registration_field_login_input_title', 'registration_fields')?>:</label>
        <input type="text" name="login" value="" id="login-box" class="loadKeyboard"/><br/><br/>
        <label for="password-box"><?= lang('password', 'registration_fields')?>:</label>
        <input type="password" name="password" value="" id="password-box" class="loadKeyboard"/><br/><br/><br/>
        <input type="submit" name="go" value="<?= lang('log in')?>" class="button"/>
      </form>
    </div>
    <?php if (CODE_CARD) { ?>
      <div class="content-box" id="content_box_tab_codecard" style="display:none">
        <br/><br/><br/><br/>
        <div class="alignR"><?= lang('active_board')?> - © <?= lang('copyright_name')?></div>
        <?php if (isset($error_str)) { ?>
          <br/><span class="error"><?= $error_str ?></span>
        <?php } ?>
        <form method="post" action="<?= site_url() ?>login.php">
          <?php if ($reservation_id) { ?>
            <input type="hidden" name="reservation_id" value="<?= $reservation_id ?>"/>
          <?php } ?>
          <?php if ($area_id) { ?>
            <input type="hidden" name="area_id" value="<?= $area_id ?>"/>
          <?php } ?>
          <?php if ($date) { ?>
            <input type="hidden" name="date" value="<?= $date ?>"/>
          <?php } ?>
          <?php if ($time) { ?>
            <input type="hidden" name="time" value="<?= $time ?>"/>
          <?php } ?>
          <input type="hidden" name="type" value="card"/>
          <label for="code-box"><?= lang('code') ?>:</label>
          <input type="text" name="codecard" value="" id="code-box" class="loadKeyboard"/><br/><br/><br/>
          <input type="submit" name="go" value="<?= lang('log in')?>" class="button"/>
        </form>
      </div>
    <?php } ?>
    <?php /* Форма для гостя */ ?>
    <?php if (GUEST
      && (config('payment')->useOnlinePayment()
        || (config('payment')->useCashPayment() && in_array(THE_GUEST_CAN_PLAY_IN_CASH, ['touch', 'all'], true)))) { ?>
      <div class="content-box" id="content_box_tab_guest" style="display:none">
        <br/><br/><br/><br/>
        <div class="alignR"><?= lang('active_board')?> - © <?= lang('copyright_name')?></div>
        <?php if (isset($error_str)) { ?>
          <br/><span class="error"><?= $error_str ?></span>
        <?php } ?>
        <form method="post" action="<?= site_url() ?>login.php">
          <?php if ($reservation_id) { ?>
            <input type="hidden" name="reservation_id" value="<?= $reservation_id ?>"/>
          <?php } ?>
          <?php if ($area_id) { ?>
            <input type="hidden" name="area_id" value="<?= $area_id ?>"/>
          <?php } ?>
          <?php if ($date) { ?>
            <input type="hidden" name="date" value="<?= $date ?>"/>
          <?php } ?>
          <?php if ($time) { ?>
            <input type="hidden" name="time" value="<?= $time ?>"/>
          <?php } ?>
          <input type="hidden" name="type" value="guest"/>
          <label for="name"><?= lang('parameter_registration_field_name_input_title', 'registration_fields')?>:</label>
          <input type="text" name="name" value="" id="name" class="loadKeyboard"/><br/><br/>
          <label for="surname"><?= lang('parameter_registration_field_surname_input_title', 'registration_fields')?>:</label>
          <input type="text" name="surname" value="" id="surname" class="loadKeyboard"/><br/><br/>
          <label for="surname"><?= lang('parameter_registration_field_email_input_title', 'registration_fields')?>:</label>
          <input type="text" name="email" value="" id="surname" class="loadKeyboard"/><br/><br/>
          <input type="submit" name="go" value="<?= lang('log in')?>" class="button"/><br>
        </form>
        <br>
        <br>
        <br>
      </div>
    <?php } ?>
  </div>
</div>
