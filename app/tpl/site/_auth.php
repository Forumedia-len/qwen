<?php
use AC\core\system\helpers\StringHelper;

// Страница ошибки не переносит отклонённые параметры в форму входа.
$authTypeId = $authDate = $authTab = null;
if (empty($_page['http_error']) && ($_page['key'] ?? null) !== 'error') {
  $authTypeId = Service::request()->validated('type_id', 'get', [['integer', ['min' => 0]]]);
  $authDate = Service::request()->validated('date', 'get', [['date', ['allowDottedFormat' => true, 'normalize' => true]]]);
  $authTab = Service::request()->validated('tab', 'request', [['integer', ['min' => 0, 'max' => 1]]]);
}
// Имена гостей в старых сессиях уже могут содержать HTML-сущности.
$escapeAuth = static fn($value): string => StringHelper::shield((string)$value, doubleEncode: false);
?>
<div id="auth-field" class="authorization-field" data-auth-tab="<?= $escapeAuth($authTab) ?>" style="display: none">
  <div id="reg-shadow" class="shadow"></div>
  <div class="authorization-container">
    <div class="button-close"></div>
    <div class="auth-logo"></div>
    <div class="auth-form-wrapper">
      <form id="login-form" class="auth-form" name="login" method="post" action="login.php"
            onSubmit="return submitLoginForm()">
        <?php

        if (isset ($_page['login_error'])) {
          if (isset($_page['login_error_type'])) {
            switch ($_page['login_error_type']) {
              case 0:
                print '<p class="error_authorization">' . lang('login_or_password_invalid', 'message_error') . '</p>';
                break;
              case 1:
                print '<p class="error_authorization">' . lang('first_name_or_mail_invalid', 'message_error') . '</p>';
                break;
              default:
                print '<p class="error_authorization">' . lang('login_or_password_invalid', 'message_error') . '</p>';
                break;
            }
          } else {
            print '<p class="error_authorization">' . lang('login_or_password_invalid', 'message_error') . '</p>';
          }

          ?>
          <?= (isset($_SESSION['error']) ? '<p class="error_authorization">' . $escapeAuth($_SESSION['error']) . '</p>' : '') ?>
          <?php unset($_SESSION['error']); ?>
          <script type="text/javascript">
            $(document).ready(function () {
              AuthWindow(true)
            })
          </script>
        <?php } ?>

        <input name="action" type="hidden" value="logIn"/>

        <?php if (GUEST == true && config('payment')->useOnlinePayment()) { ?>
          <div id="auth-tabs">
            <span id="tab-0" class="sel" onclick="setAuthTab(this, 0)" style="padding-top: 10px;"><?= lang('with_login_password', 'auth') ?> </span>
            <span id="tab-1" onclick="setAuthTab(this, 1)">
              <img src="<?= cdn_url(paths()->getAssetsDir('images/' . (config('payment')->usePayonePayment() ? 'po' : 'pp') . '.png', 'common')) ?>" alt=""
                   style="height: 20px; width: 20px;">&nbsp;<?= lang('without_login', 'auth') ?></span>
          </div>
        <?php } ?>
        <?php if ($authTypeId !== null) { ?>
          <input name="type_id" type="hidden" value="<?= $escapeAuth($authTypeId) ?>"/>
        <?php } ?>
        <?php if ($authDate !== null) { ?>
          <input name="date" type="hidden" value="<?= $escapeAuth($authDate) ?>"/>
        <?php } ?>

        <div class="tab-content" id="tab-content-0">
          <input class="auth-login" type="text" name="username" id="login"
                 placeholder="<?= lang((((defined('LOGIN_AS_EMAIL') && LOGIN_AS_EMAIL) || USE_STEP_FORM_REGISTRATION) ? 'email_' : '') . 'login_name') ?>"/>
          <input class="auth-password" type="password" name="password" id="password"
                 placeholder="<?= lang('password') ?>"/>
          <input type="radio" hidden name="check_tab" value="0" checked>
        </div>
        <?php if (GUEST == true) { ?>
          <div class="tab-content" id="tab-content-1" style="display:none">
            <input type="text" name="name" id="name" placeholder="<?= lang('parameter_registration_field_name_input_title', 'registration_fields') ?>"
                   value="<?= $escapeAuth($_SESSION['name'] ?? '') ?>"/>
            <input type="text" name="surname" id="surname"
                   placeholder="<?= lang('parameter_registration_field_surname_input_title', 'registration_fields') ?>"
                   value="<?= $escapeAuth($_SESSION['surname'] ?? '') ?>"/>
            <input type="text" name="email" id="email"
                   placeholder="<?= lang('parameter_registration_field_email_input_title', 'registration_fields') ?>"
                   value="<?= $escapeAuth($_SESSION['email'] ?? '') ?>"/>
            <input type="radio" hidden name="check_tab" value="1">
          </div>
        <?php } ?>
        <input id="reg-submit" class="button-reverse" type="submit" value="<?= lang('log in') ?>"/>
        <div class="password-restore-wrapper">
          <a href="<?= site_url('recover_password.php') ?>"><?= lang('forgot_password') ?></a>
        </div>
      </form>
    </div>
    <div class="registration-field">

      <?= (USE_REGISTRATION ? lang('new_account', 'common', ['base_href' => site_url()]) : '') ?>
      <!--            <span>Noch keine Zugangsdaten?</span><br/>-->
      <!--            <span><a href="">KLICKEN SIE HIER!</a></span>-->
    </div>
  </div>
</div>
