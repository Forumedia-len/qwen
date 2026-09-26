<?php

use AC\core\modules\mailing\entities\enums\ModeTemplate;
use AC\core\system\helpers\PasswordHelper;
use AC\core\system\helpers\ReCaptchaHelper;
use AC\core\system\helpers\StringHelper;

$_page['key']   = 'recover_password';
$_page['title'] = lang('title', 'recover_password');
$show_form      = true;

if (isset ($_POST['login'])) {
  if (!empty($_POST['login'])) {
    $r = Service::engines();
    if ($r->clients->getClientDataByLogin(addslashes($_POST['login']), $client)) {
      if (ReCaptchaHelper::checkReCaptcha('g-recaptcha-response', 0.3)) {
        $password = PasswordHelper::generatePassword(12, false);
        //меняем пароли для всех пользователей с таким логином
        $tpl_data['CLIENT_NAME']    = $client['name'];
        $tpl_data['CLIENT_SURNAME'] = $client['surname'];
        $tpl_data['LOGIN']          = $client['login'];
        $tpl_data['PASSWORD']       = '<pre><span style="font-weight: bold;color: green;font-size: 30px">' . $password . '</span></pre>';

        if ($r->clients->changeClientPassword($client['client_id'], $password) &&
          Service::mailer()->dispatch($client['email'], ModeTemplate::USER->value, 'recover_password', $tpl_data, $client['lang'] ?? config('lang')->getDefault())) {
          $show_form = false;
        } else {
          $error = lang('error_please_repeat_the_process', 'recover_password');
          $row   = $_POST;
        }
      } else {
        $error = lang('error_the_reCaptcha_test_has_not_been_passed', 'recover_password');
      }
    } else {
      $error = lang('error_no_user_with_such_data_entered', 'recover_password');
      $row   = $_POST;
    }
  } else {
    $error = lang('error_please_enter_your_user_name', 'recover_password');
  }
}

ob_start();
?>
  <div class="content recover-password">
    <p><?= lang('you_have_forgotten_your_password', 'recover_password') ?></p>
    <?php

    if ($show_form == true) {
      if (isset($error)) {
        echo '<p class="error">' . $error . '</p>';
      }
      ?>
      <form method="post" action="<?= site_url('recover_password.php') ?>">
        <?= ReCaptchaHelper::getReCaptchaScript('recover_password') ?>
        <div class="row">
          <div class="col-md-6">
            <div class="label-wrapper">
              <span class="label"><?= lang('your_username', 'recover_password') ?> :</span>
            </div>
            <input name="login" class="registration" type="text"
                   value="<?= (isset($row['login']) ? StringHelper::shield($row['login']) : '') ?>"
                   tooltip="<?= lang('your_username', 'recover_password') ?>">
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <input value="<?= lang('button_send') ?>" class="button" type="submit"/>
          </div>
        </div>
      </form>
      <?
    } else { ?>
      <p class="success"><?= lang('your_request_has_been_successfully_sent', 'recover_password') ?></p>
    <?php } ?>
  </div>
<?php
$_page['content'][1] = ob_get_contents();
ob_end_clean();