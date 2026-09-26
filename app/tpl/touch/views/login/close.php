<?php

use AC\core\modules\config\models\ConfigModel;

?>
<time data-time="300000"></time>
<div id="loginForm">
  <div id="fon_tab"></div>
  <table style="width:100%">
    <tr>
      <td>
        <div class="header">
          <table style="width:100%; border-collapse: collapse; border:0px;" border="0" cellpadding="0"
                 cellspacing="0">
            <tr>
              <td class="top_border">
                <div class="<?= ((isset($_GET['error']) && $_GET['error'] != 'online1') ? 'tab' : 'tabSel') ?>"
                     onclick="return selectedTab(this)" id="tabBox_1">
                  <span class="tabTitle"> <?= lang('with_login_password', 'auth') ?> </span>
                </div>
              </td>
              <?php if (CODE_CARD) { ?>
                <td class="top_border">
                  <div class="<?= (isset($_GET['error']) ? ($_GET['error'] == 'online2') ? 'tabSel' : 'tab' : 'tab') ?>"
                       onclick="return selectedTab(this)" id="tabBox_2">
                    <span class="tabTitle"> <?= lang('with_code_card', 'auth') ?> </span>
                  </div>
                </td>
              <?php } ?>
              <?php if (GUEST && config('payment')->useOnlinePayment() && defined(
                  'HIDE_GUEST_MENU_TOUCH'
                ) && HIDE_GUEST_MENU_TOUCH != 'close') { ?>
                <td class="top_border">
                  <div class="<?= (isset($_GET['error']) ? ($_GET['error'] == 'online3') ? 'tabSel' : 'tab' : 'tab') ?>"
                       onclick="return selectedTab(this)" id="tabBox_3">
                    <span class="tabTitle"> <?= lang('Guest_player') ?> </span>
                  </div>
                </td>
              <?php } ?>
              <?php if (defined('GUEST_BAR_MENU_TOUCH_CLOSE') && GUEST_BAR_MENU_TOUCH_CLOSE
                && config('payment')->useCashPayment()) { ?>
                <td class="top_border">
                  <div class="<?= (isset($_GET['error']) ? ($_GET['error'] == 'online3') ? 'tabSel' : 'tab' : 'tab') ?>"
                       onclick="return selectedTab(this)" id="tabBox_3">
                    <span class="tabTitle"> <?= lang('cash_payer_new_customers', 'auth') ?> </span>
                  </div>
                </td>
              <?php } ?>
            </tr>
          </table>
        </div>
        <div class="content" style="text-align:center;">
          <?php if (CODE_CARD) { ?>
            <div id="tabForm_2"
                 style="display:<?= (isset($_GET['error']) ? ($_GET['error'] == 'online1' ? '' : 'none') : 'none') ?>">
              <h1><?= lang('customers_with_code_card', 'auth') ?></h1>
              <form action="<?= site_url() ?>login.php" method="post" id="form_auth">
                <input type="hidden" name="type" value="card"/>
                <table border="0" cellspacing="0" cellpadding="5" style="margin:0 auto 0 auto;">
                  <tr>
                    <td>
                      <div class="text">
                        <?= isset ($_GET['error']) && $_GET['error'] == 'online2'
                          ? '<span class="error">' . lang('username_or_password_invalid', 'message_error') . '</span>'
                          : lang(
                            'please_hold_the_card_up_to_the_reader',
                            'message_error'
                          ) ?>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td><input type="text" name="codecard" value="" title="<?= lang('code_card') ?>" id="codekarteField"
                               class="input loadKeyboard" placeholder="<?= lang('code') ?>"/></td>
                  </tr>
                </table>
              </form>
            </div>
          <?php } ?>
          <div id="tabForm_1"
               style="display:<?= ((isset($_GET['error']) && $_GET['error'] == 'online2') ? '' : '') ?>">
            <h1><?= lang('customers_with_login', 'auth') ?></h1>
            <p class="text"><? if (isset ($_GET['error']) && $_GET['error'] == 'online1') { ?>
                <span class="error"><?= lang('username_or_password_invalid', 'message_error') ?></span>
              <? } else { ?><?= lang('enter_your_username_and_password', 'message_notify') ?><? } ?></p>
            <form action="<?= site_url() ?>login.php" method="post" id="form_auth2"
                  onsubmit="return checkOnlineForm(this)">
              <input type="hidden" name="type" value="login"/>
              <table border="0" cellspacing="0" cellpadding="5" style="margin:0 auto 0 auto;">
                <tr>
                  <td><input type="text" name="login" value=""
                             title="<?= (defined('LOGIN_AS_EMAIL') && LOGIN_AS_EMAIL ? lang(
                               'parameter_registration_field_login_input_email',
                               'registration_fields'
                             ) : lang('parameter_registration_field_login_input_title', 'registration_fields')) ?>"
                             class="input loadKeyboard"
                             placeholder="<?= (defined('LOGIN_AS_EMAIL') && LOGIN_AS_EMAIL ? lang(
                               'parameter_registration_field_login_input_email',
                               'registration_fields'
                             ) : lang('parameter_registration_field_login_input_title', 'registration_fields')) ?>"/></td>
                </tr>
                <tr>
                  <td><input type="password" name="password" value="" title="<?= lang('password', 'registration_fields') ?>"
                             class="input loadKeyboard"
                             placeholder="<?= lang('password', 'registration_fields') ?>"/></td>
                </tr>
                <tr>
                  <td align="center"><input type="submit" value="<?= lang('log in') ?>" class="buttonBig2"/></td>
                </tr>
              </table>
            </form>
          </div>
          <?php if (GUEST && config('payment')->useOnlinePayment()) { ?>
            <div id="tabForm_3"
                 style="display:<?= ((isset($_GET['error']) && $_GET['error'] == 'offline') ? '' : 'none') ?>">
              <p class="title"><?= lang('for_cash_payers_new_customers_without_login', 'auth') ?></p>
              <form action="<?= site_url() ?>login.php" method="post" id="form_auth3"
                    onsubmit="return checkOfflineForm(this)">
                <input type="hidden" name="type" value="guest"/>
                <table border="0" cellspacing="0" cellpadding="5" style="margin:0 auto 0 auto;">
                  <tr>
                    <td><input type="text" name="name" value=""
                               title="<?= lang('parameter_registration_field_name_input_title', 'registration_fields') ?>"
                               class="input loadKeyboard"
                               id="loginField" placeholder="<?= lang('parameter_registration_field_name_input_title', 'registration_fields') ?>"/>
                    </td>
                  </tr>
                  <tr>
                    <td><input type="text" name="surname" value=""
                               title="<?= lang('parameter_registration_field_surname_input_title', 'registration_fields') ?>"
                               class="input loadKeyboard"
                               placeholder="<?= lang('parameter_registration_field_surname_input_title', 'registration_fields') ?>"/>
                    </td>
                  </tr>
                  <tr>
                    <td><input type="text" name="email" value=""
                               title="<?= lang('parameter_registration_field_email_input_title', 'registration_fields') ?>"
                               class="input loadKeyboard"
                               placeholder="<?= lang('parameter_registration_field_email_input_title', 'registration_fields') ?>"/>
                    </td>
                  </tr>
                  <tr>
                    <td align="center"><input type="submit" value="<?= lang('log in') ?>" class="buttonBig2"/></td>
                  </tr>
                </table>
              </form>
            </div>
          <?php }
          ?>
          <?php if (defined('GUEST_BAR_MENU_TOUCH_CLOSE') && GUEST_BAR_MENU_TOUCH_CLOSE
            && config('payment')->useCashPayment()) { ?>
            <div id="tabForm_3" style="display:<?= ((isset($_GET['error']) && $_GET['error'] == 'offline') ? '' : 'none') ?>">
              <p class="title"><?= lang('for_cash_payers_new_customers_without_login', 'auth') ?></p>
              <form action="<?= site_url() ?>login.php" method="post" id="form_auth3"
                    onsubmit="return checkOfflineForm(this)">
                <input type="hidden" name="type" value="guest_bar"/>
                <table border="0" cellspacing="0" cellpadding="5" style="margin:0 auto 0 auto;">
                  <tr>
                    <td><input type="text" name="name" value=""
                               title="<?= lang('parameter_registration_field_name_input_title', 'registration_fields') ?>"
                               class="input loadKeyboard"
                               id="loginField" placeholder="<?= lang('parameter_registration_field_name_input_title', 'registration_fields') ?>"/>
                    </td>
                  </tr>
                  <tr>
                    <td><input type="text" name="surname" value=""
                               title="<?= lang('parameter_registration_field_surname_input_title', 'registration_fields') ?>"
                               class="input loadKeyboard"
                               placeholder="<?= lang('parameter_registration_field_surname_input_title', 'registration_fields') ?>"/>
                    </td>
                  </tr>
                  <tr>
                    <td><input type="text" name="email" value=""
                               title="<?= lang('parameter_registration_field_email_input_title', 'registration_fields') ?>"
                               class="input loadKeyboard"
                               placeholder="<?= lang('parameter_registration_field_email_input_title', 'registration_fields') ?>"/>
                    </td>
                  </tr>
                  <tr>
                    <td align="center"><input type="submit" value="<?= lang('log in') ?>" class="buttonBig2"/></td>
                  </tr>
                </table>
              </form>
            </div>
          <?php } ?>
        </div>
      </td>
    </tr>
  </table>
  <?php if (isset($_COOKIE["one_court_type"]) && !$_COOKIE["one_court_type"]) { ?>
    <a class="back_button_button" href="<?= site_url() . $page->_back_href ?>"><?= lang('back_to_the_selection_screen') ?></a>
  <?php } ?>
</div>

