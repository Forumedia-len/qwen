<?php
/**
 * @var bool             $change
 * @var array            $fields
 * @var object            $model
 * @var RegistrationView $this
 * @var string           $consent_text
 */

use AC\core\system\helpers\ReCaptchaHelper;
use AC\core\system\helpers\UrlHelper;
use AC\core\modules\config\models\ConfigModel;
use AC\core\modules\clients\views\RegistrationView;

?>
  <form method="post" action="<?=site_url( 'registration.php') ?>" <?= ((isset($change) && $change) ? '' : 'onsubmit="return checkUserRight()"') ?> >
    <div class="content">
      <?= ReCaptchaHelper::getReCaptchaScript('registration') ?>
      <input type="hidden" name="action" value="<?= ((isset($change) && $change) ? 'update' : 'create') ?>">
      <?php if ($change) { ?>
        <input type="hidden" name="client_id" value="<?= $model->client_id ?>"/>
      <?php } ?>
      <table width="900" border="0" cellspacing="2" cellpadding="0" class="no_border new_br">
        <?php
        if (!$change && ($fields['encash_pp']->show == 1 || $fields['encash_invoice']->show == 1 || $fields['encash_cash']->show == 1)) {
          $n = 3; ?>
          <tr>
            <td style="width:250px;">
              <span class="label"><?= $this->getMess('encash', 'parameter') ?> </span>
              <span class="label"> :</span>
            </td>

            <?php if ($fields['encash_invoice']->show) {
              $n -= 2; ?>
              <td colspan="1">
                <?= $this->getRegistrationField(
                  $fields['encash_invoice'],
                  (object)array(
                    'checked' => !isset($model->encash) ? 'checked' : ($model->encash == 1 ? 'checked' : ''),
                    'value'   => 1
                  )
                ) ?>
              </td>
            <?php } ?>
            <?php if ($fields['encash_cash']->show) {
              $n -= 2; ?>
              <td colspan="1">
                <?= $this->getRegistrationField(
                  $fields['encash_cash'],
                  (object)array(
                    'checked' => !isset($model->encash) && !$fields['encash_invoice']->show ? 'checked' : ($model->encash === 0 ? 'checked' : ''),
                    'value'   => 0
                  )
                ) ?>
              </td>
            <?php } ?>
            <?php if ($fields['encash_pp']->show) {
              $n--; ?>
              <td>
                <?= $this->getRegistrationField(
                  $fields['encash_pp'],
                  (object)array(
                    'checked' => (!isset($model->encash) && !$fields['encash_invoice']->show && !$fields['encash_cash']->show && $fields['encash_pp']->show) || (isset($model->encash) && $model->encash == 2)
                      ? 'checked' : '',
                    'value'   => 2
                  )
                ) ?>
              </td>
            <?php } ?>
            <?php if ($n > 0) { ?>
              <td colspan="<?= $n ?>">&nbsp;</td>
            <?php } ?>
          </tr>
          <tr>
            <td colspan="4">&nbsp;</td>
          </tr>
        <?php } ?>
        <?php if (!$change && ($fields['nichtmitglied']->show == 1 || $fields['mitglied']->show == 1)) { ?>
          <tr>
            <td style="width:250px;"></td>
            <?php if ($fields['nichtmitglied']->show) { ?>
              <td>
                <?= $this->getRegistrationField(
                  $fields['nichtmitglied'],
                  (object)array(
                    'checked' => !isset($model->club_state) ? 'checked' : ($model->club_state == 1 ? 'checked' : ''),
                    'value'   => 1
                  )
                ) ?>
              </td>
            <?php } ?>
            <?php if ($fields['mitglied']->show) { ?>
              <td class="col-md-3" colspan="2">
                <?= $this->getRegistrationField(
                  $fields['mitglied'],
                  (object)array(
                    'checked' => !isset($model->club_state) ? '' : ($model->club_state == 2 ? 'checked' : ''),
                    'value'   => 2
                  )
                ) ?>
              </td>
            <?php } ?>
            <?php if ($n > 0) { ?>
              <td colspan="<?= $n ?>">&nbsp;</td>
            <?php } ?>
          </tr>
          <tr>
            <td colspan="4">&nbsp;</td>
          </tr>
        <?php } ?>
        <?php if ($fields['student']->show == 1 || $fields['student_number']->show == 1) { ?>
          <tr>
            <td style="width:250px;"></td>
            <?php if ($fields['student']->show) { ?>
              <td>
                <?= $this->getRegistrationField(
                  $fields['student'],
                  (object)array(
                    'checked' => isset($model->student) ? 'checked' : '',
                    'value'   => 1,
                  )
                ) ?>
              </td>
            <?php } ?>
            <?php if ($fields['student_number']->show) { ?>
              <td colspan="2">
                <?= $this->getRegistrationField($fields['student_number'], $model->student_number) ?>
              </td>
            <?php } ?>
            <?php if ($n > 0) { ?>
              <td colspan="<?= $n ?>">&nbsp;</td>
            <?php } ?>
          </tr>
          <tr>
            <td colspan="4">&nbsp;</td>
          </tr>
        <?php } ?>
        <?php if ($fields['login']->show || $fields['password']->show || $fields['password_confirmation']->show) {
          $n = 4; ?>
          <tr>
            <?php if ($fields['login']->show) {
              $n--; ?>
              <td>
                <?= $this->getRegistrationField($fields['login'], $model->login, $change, (defined('LOGIN_AS_EMAIL') && LOGIN_AS_EMAIL) ? "_as_email" : '') ?>
              </td>
            <?php } ?>
            <?php if ($fields['password']->show) {
              $n--; ?>
              <td nowrap="nowrap">
                <?= $this->getRegistrationField($fields['password'], $model->password) ?>
              </td>
            <?php } ?>
            <?php if ($fields['password_confirmation']->show) {
              $n--; ?>
              <td>
                <?= $this->getRegistrationField($fields['password_confirmation'], $model->password_confirmation) ?>
              </td>
            <?php } ?>
            <?php if ($n > 0) { ?>
              <td colspan="<?= $n ?>">&nbsp;</td>
            <?php } ?>
          </tr>
        <?php } ?>
        <?php if ($fields['password']->show) { ?>
          <tr class="divider">
            <td class="divider">&nbsp;</td>
            <td class="divider">
              <?= $this->getMess('password_condition') ?>
            </td>
            <td class="divider">&nbsp;</td>
            <td class="divider"></td>
          </tr>
          <tr>
            <td colspan="4">&nbsp;</td>
          </tr>
        <?php } ?>
        <?php if ($fields['name']->show || $fields['surname']->show || $fields['birthday']->show) {
          $n = 4; ?>
          <tr>
            <?php if ($fields['name']->show) {
              $n--; ?>
              <td class="divider">
                <?= $this->getRegistrationField($fields['name'], $model->name) ?>
              </td>
            <?php } ?>
            <?php if ($fields['surname']->show) {
              $n--; ?>
              <td class="divider">
                <?= $this->getRegistrationField($fields['surname'], $model->surname) ?>
              </td>
            <?php } ?>
            <?php if ($fields['birthday']->show) {
              $n--; ?>
              <td class="divider">
                <?= $this->getRegistrationField($fields['birthday'], $model->birthday) ?>
              </td>
            <?php } ?>
            <?php if ($n > 0) { ?>
              <td colspan="<?= $n ?>" class="divider">&nbsp;</td>
            <?php } ?>
          </tr>


          <?php if ($fields['firm']->show) { ?>
            <tr>
              <td class="divider">
                <?= $this->getRegistrationField($fields['firm'], $model->firm) ?>
              </td>
              <td colspan="3">&nbsp;</td>
            </tr>
          <?php } ?>
          <tr>
            <td colspan="4">&nbsp;</td>
          </tr>
        <?php } ?>
        <?php if ($fields['phone']->show || $fields['fax']->show
          || $fields['phone_mobile']->show || $fields['email']->show) {
          $n = 4; ?>
          <tr>
            <?php if ($fields['phone']->show) {
              $n--; ?>
              <td class="divider">
                <?= $this->getRegistrationField($fields['phone'], $model->phone) ?>
              </td>
            <?php } ?>
            <?php if ($fields['fax']->show) {
              $n--; ?>
              <td class="divider">
                <?= $this->getRegistrationField($fields['fax'], $model->fax) ?>
              </td>
            <?php } ?>
            <?php if ($fields['phone_mobile']->show) {
              $n--; ?>
              <td class="divider">
                <?= $this->getRegistrationField($fields['phone_mobile'], $model->phone_mobile) ?>
              </td>
            <?php } ?>
            <?php if ($fields['email']->show) {
              $n--; ?>
              <td class="divider">
                <?php if (defined('LOGIN_AS_EMAIL') && LOGIN_AS_EMAIL) { ?>
                  <input type="hidden" name="email" value="<?= $model->email ?>"/>
                <?php } else { ?>
                  <?= $this->getRegistrationField($fields['email'], $model->email) ?>
                <?php } ?>
              </td>
            <?php } ?>
            <?php if ($n > 0) { ?>
              <td colspan="<?= $n ?>" class="divider">&nbsp;</td>
            <?php } ?>
          </tr>
          <tr>
            <td colspan="4">&nbsp;</td>
          </tr>
        <?php } ?>
        <?php if ($fields['post_code']->show || $fields['address']->show || $fields['city']->show) {
          $n = 4; ?>
          <tr>
            <?php if ($fields['post_code']->show) {
              $n--; ?>
              <td class="divider">
                <?= $this->getRegistrationField($fields['post_code'], $model->post_code) ?>
              </td>
            <?php } ?>
            <?php if ($fields['address']->show) {
              $n--; ?>
              <td class="divider">
                <?= $this->getRegistrationField($fields['address'], $model->address) ?>
              </td>
            <?php } ?>
            <?php if ($fields['city']->show) {
              $n--; ?>
              <td class="divider">
                <?= $this->getRegistrationField($fields['city'], $model->city) ?>
              </td>
            <?php } ?>
            <?php if ($n > 0) { ?>
              <td colspan="<?= $n ?>" class="divider">&nbsp;</td>
            <?php } ?>
          </tr>
          <tr>
            <td colspan="4">&nbsp;</td>
          </tr>
        <?php } ?>
        <?php if ($fields['account_owner']->show || $fields['bank_name']->show) {
          $n = 4; ?>
          <tr class="rechnung-checked">
            <?php if ($fields['account_owner']->show) {
              $n--; ?>
              <td class="divider">
                <?= $this->getRegistrationField($fields['account_owner'], $model->account_owner) ?>
              </td>
            <?php } ?>
            <?php if ($fields['bank_name']->show) {
              $n--; ?>
              <td class="divider">
                <?= $this->getRegistrationField($fields['bank_name'], $model->bank_name) ?>
              </td>
            <?php } ?>
            <?php if ($n > 0) { ?>
              <td colspan="<?= $n ?>" class="divider">&nbsp;</td>
            <?php } ?>
          </tr>
          <tr>
            <td colspan="4">&nbsp;</td>
          </tr>
        <?php } ?>
        <tr>
          <td colspan="4">
            <input name="bank_account_number" value="" type="hidden"/>
            <input name="bank_identifier_code" value="" type="hidden"/>
          </td>
        </tr>

        <?php if ($fields['bank_iban']->show || $fields['bank_bic']->show || $fields['bank_sepa_mandat']->show) {
          $n = 4; ?>
          <tr>
            <td colspan="4"><?= $this->getMess('sepa_data') ?></td>
          </tr>
          <tr>
            <td colspan="4">&nbsp;</td>
          </tr>
          <tr class="rechnung-checked">
            <?php if ($fields['bank_iban']->show) {
              $n--; ?>
              <td class="divider">
                <?= $this->getRegistrationField($fields['bank_iban'], $model->bank_iban) ?>
              </td>
            <?php } ?>
            <?php if ($fields['bank_bic']->show) {
              $n--; ?>
              <td class="divider">
                <?= $this->getRegistrationField($fields['bank_bic'], $model->bank_bic) ?>
              </td>
            <?php } ?>
            <?php if ($fields['bank_sepa_mandat']->show) {
              $n--; ?>
              <td class="divider">
                <?= $this->getRegistrationField($fields['bank_sepa_mandat'], $model->bank_sepa_mandat) ?>
              </td>
            <?php } ?>
            <?php if ($n > 0) { ?>
              <td colspan="<?= $n ?>" class="divider">&nbsp;</td>
            <?php } ?>
          </tr>
          <tr>
            <td colspan="4">&nbsp;</td>
          </tr>
        <?php } ?>
        <tr>
          <td colspan="4">
            <input name="bank_sepa_referenz" value="" type="hidden"/>
          </td>
        </tr>
        <?php if (!$change) { ?>
          <tr>
            <td colspan="4">&nbsp;</td>
          </tr>
          <tr>
            <td nowrap="nowrap" colspan="4">
              <input name="user_right" id="user_right_button" type="checkbox" value="1" class="check"/>
              <span class="podlog2"></span> <?= $this->getMess('confirmation') ?><br/><br/></td>
          </tr>
        <?php } ?>
      </table>
      <table style="width:400px">
        <tr>
          <td><input value="<?= lang('button_send') ?>" class="button" type="submit"/></td>
          <td style="vertical-align:middle;">
            <p>
              <b>
                <span class="red">*</span>
                <?= $this->getMess('obligatory') ?>
              </b>
            </p>
          </td>
        </tr>
      </table>
      <br/>
      <?= $consent_text; ?>
    </div>
  </form>
<?php if (defined('LOGIN_AS_EMAIL') && LOGIN_AS_EMAIL) { ?>
  <script>
    $(document).ready(function () {
      $('.content').on('input', '[name=login]', function () {
        $('[name=email]').val($(this).val())
        // console.log($('[name=email]').val())
      })
    })
  </script>
<?php } ?>
<?php if ($fields['bank_iban']->show || $fields['bank_bic']->show) { ?>
  <script>
    $(document).ready(function () {
      <?php if(!defined('USE_TEST_AND_MASK_IBAN') || USE_TEST_AND_MASK_IBAN) { ?>
        testForm('reg_bank_iban', 'testiban', '../')
        $('#reg_bank_iban').mask('<?= USE_TEST_AND_MASK_IBAN ? : 'AA 99 9999 9999 9999 9999 99' ?>', {completed: function () {testForm('iban-field', 'testiban', '../')}})
        $('#reg_bank_bic').mask('###########')
        $('#reg_bank_iban').bind('blur', function () {testForm('reg_bank_iban', 'testiban', '../')})
      <?php } ?>
      let encash = document.getElementsByName('encash')
      let rechnung = $('.rechnung-checked')
      $(encash).each(function () {
        if ($(this).prop('checked')) {
          rechnungChecked(this, rechnung)
        }
      })
      $(encash).bind('change click', function () {
        rechnungChecked(this, rechnung)
      })
    })

  </script>
<?php } ?>