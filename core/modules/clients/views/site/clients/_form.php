<?php
/**
 * @var bool $change
 * @var array $fields
 * @var object $model
 * @var RegistrationView $this
 * @var  string $consent_text
 */

use AC\core\system\helpers\ReCaptchaHelper;

use AC\core\modules\config\models\ConfigModel;
use AC\core\modules\clients\views\RegistrationView;
prepareClass('client_insert');
?>
  <form method="post" action="<?= site_url('registration.php') ?>" <?= ((isset($change) && $change) ? ''
    : 'onsubmit="return checkUserRight()"') ?> autocomplete="nope">

    <input type="hidden" name="action" value="<?= ((isset($change) && $change) ? 'update' : 'create') ?>">
    <?= ReCaptchaHelper::getReCaptchaScript('registration') ?>
    <?php if ($change) { ?>
      <input type="hidden" name="client_id" value="<?= $model->client_id ?>"/>
    <?php } ?>
    <?php
    if (!$change && ($fields['encash_pp']->show == 1 || (isset($fields['encash_invoice']) && $fields['encash_invoice']->show == 1) || $fields['encash_cash']->show == 1)) { ?>
      <div class="row">
        <div class="col-md-2">
          <div class="label-wrapper">
            <span class="label"><?= $this->getMess('encash', 'parameter') ?> </span>
            <span class="label"> :</span>
          </div>
        </div>
        <?php if ($fields['encash_invoice']->show) { ?>
          <div class="col-md-3">
            <?= $this->getRegistrationField(
              $fields['encash_invoice'],
              (object)array(
                'checked' => !isset($model->encash) ? 'checked' : ($model->encash == 1 ? 'checked' : ''),
                'value'   => 1
              )
            ) ?>
          </div>
        <?php } ?>
        <?php if ($fields['encash_cash']->show) { ?>
          <div class="col-md-3">
            <?= $this->getRegistrationField(
              $fields['encash_cash'],
              (object)array(
                'checked' => !isset($model->encash) && !$fields['encash_invoice']->show ? 'checked' : ($model->encash === 0 ? 'checked' : ''),
                'value'   => 0
              )
            ) ?>
          </div>
        <?php } ?>
        <?php if ($fields['encash_pp']->show) { ?>
          <div class="col-md-4">
            <?= $this->getRegistrationField(
              $fields['encash_pp'],
              (object)array(
                'checked' => (!isset($model->encash) && !$fields['encash_invoice']->show && !$fields['encash_cash']->show && $fields['encash_pp']->show) || (isset($model->encash) && $model->encash == 2)
                  ? 'checked' : '',
                'value'   => 2
              )
            ) ?>
          </div>
        <?php } ?>
      </div>
    <?php } ?>
    <?php if (!$change && ($fields['nichtmitglied']->show == 1 || $fields['mitglied']->show == 1)) { ?>
      <div class="row">
        <div class="col-md-2">
        </div>
        <?php if ($fields['nichtmitglied']->show) { ?>
          <div class="col-md-3">
            <?= $this->getRegistrationField(
              $fields['nichtmitglied'],
              (object)array(
                'checked' => !isset($model->club_state) ? 'checked' : ($model->club_state == 1 ? 'checked' : ''),
                'value'   => 1
              )
            ) ?>
          </div>
        <?php } ?>
        <?php if ($fields['mitglied']->show) { ?>
          <div class="col-md-3">
            <?= $this->getRegistrationField(
              $fields['mitglied'],
              (object)array(
                'checked' => !isset($model->club_state) ? '' : ($model->club_state == 2 ? 'checked' : ''),
                'value'   => 2
              )
            ) ?>
          </div>
        <?php } ?>
        <div class="col-md-4"></div>
      </div>
    <?php } ?>
    <?php if ((isset($fields['student']) && $fields['student']->show == 1) || (isset($fields['student_number']) && $fields['student_number']->show == 1)) { ?>
      <div class="row">
        <div class="col-md-2">
        </div>
        <?php if ($fields['student']->show) { ?>
          <div class="col-md-4">
            <?= $this->getRegistrationField(
              $fields['student'],
              (object)array(
                'checked' => (($model->student == '1') ? 'checked' : ''),
                'value'   => 1,
              )
            ) ?>
          </div>

        <?php } ?>
        <?php if ($fields['student_number']->show) { ?>
          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['student_number'], $model->student_number) ?>
          </div>
        <?php } ?>
        <script>
          $('.label-wrapper', $('[name="student_number"]').closest('.col-md-6')).css('min-width', '190px')
          $('[name="student"]').on('change', function () {
            if ($(this).is(':checked')) {
              $('[name="student_number"]').prop('required', true)
              $('.label-wrapper .label', $('[name="student_number"]').closest('.col-md-6')).first().after($('<span>', {class: 'red', text: '*'}))

            } else {
              $('[name="student_number"]').prop('required', false)
              $('.label-wrapper .red', $('[name="student_number"]').closest('.col-md-6')).remove()
            }
          })
        </script>
      </div>
    <?php } ?>
    <?php if ($fields['login']->show) { ?>
      <div class="row">
        <div class="col-md-6">
          <?= $this->getRegistrationField($fields['login'], $model->login, $change,
            ((defined('LOGIN_AS_EMAIL') && LOGIN_AS_EMAIL) || USE_STEP_FORM_REGISTRATION) ? "_as_email" : '') ?>
        </div>
      </div>
    <?php } ?>

    <?php if ($fields['password']->show || $fields['password_confirmation']->show) { ?>
      <div class="row">
        <?php if ($fields['password']->show) { ?>
          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['password'], $model->password) ?>
          </div>
        <?php } ?>
        <?php if ($fields['password_confirmation']->show) { ?>
          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['password_confirmation'], $model->password_confirmation) ?>
          </div>
        <?php } ?>
      </div>
      <?php if ($fields['password']->show) { ?>
        <div class="row dashed-border">
          <div class="col-lg-2"></div>
          <div class="col-md-3">
            <?= $this->getMess('password_condition') ?>
          </div>
        </div>
      <?php } ?>
    <?php } ?>

    <?php if ($fields['name']->show || $fields['surname']->show) { ?>
      <div class="row">
        <?php if ($fields['name']->show) { ?>
          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['name'], $model->name) ?>
          </div>
        <?php } ?>
        <?php if ($fields['surname']->show) { ?>
          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['surname'], $model->surname) ?>
          </div>
        <?php } ?>
      </div>
    <?php } ?>
    <?php if ($fields['firm']->show) { ?>
      <div class="row">
        <?php if ($fields['firm']->show) { ?>
          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['firm'], $model->firm) ?>
          </div>
        <?php } ?>
      </div>
    <?php } ?>

    <?php if ($fields['birthday']->show) { ?>
      <div class="row dashed-border">
        <div class="col-md-8">
          <?= $this->getRegistrationField($fields['birthday'], $model->birthday) ?>
        </div>
      </div>
    <?php } ?>

    <?php if ($fields['phone']->show || $fields['fax']->show) { ?>
      <div class="row">
        <?php if ($fields['phone']->show) { ?>
          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['phone'], $model->phone) ?>
          </div>
        <?php } ?>
        <?php if ($fields['fax']->show) { ?>
          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['fax'], $model->fax) ?>
          </div>
        <?php } ?>
      </div>
    <?php } ?>

    <?php if ($fields['phone_mobile']->show) { ?>
      <div class="row dashed-border">
        <div class="col-md-6">
          <?= $this->getRegistrationField($fields['phone_mobile'], $model->phone_mobile) ?>
        </div>
      </div>
    <?php } ?>

    <?php if ($fields['post_code']->show || $fields['address']->show) { ?>
      <div class="row">
        <?php if ($fields['post_code']->show) { ?>
          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['post_code'], $model->post_code) ?>
          </div>
        <?php } ?>
        <?php if ($fields['address']->show) { ?>
          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['address'], $model->address) ?>
          </div>
        <?php } ?>
      </div>
    <?php } ?>

    <?php if ($fields['city']->show || isset($fields['country']) && $fields['country']->show) { ?>
      <div class="row dashed-border">
        <?php if ($fields['city']->show) { ?>
          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['city'], $model->city) ?>
            </div>
        <?php } ?>
        <?php if ($fields['country']->show) { ?>
          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['country'], $model->country) ?>
            </div>
        <?php } ?>
      </div>
    <?php } ?>

    <?php if ($fields['email']->show) { ?>
      <?php if ((defined('LOGIN_AS_EMAIL') && LOGIN_AS_EMAIL) || USE_STEP_FORM_REGISTRATION) { ?>
        <input type="hidden" name="email" value="<?= $model->email ?>"/>
      <?php } else { ?>
        <div class="row dashed-border">
          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['email'], $model->email) ?>
          </div>
        </div>
      <?php }
    } ?>

    <?php if ($fields['account_owner']->show || $fields['bank_name']->show) { ?>
      <div class="row dashed-border rechnung-checked">
        <?php if ($fields['account_owner']->show) { ?>
          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['account_owner'], $model->account_owner) ?>
          </div>
        <?php } ?>
        <?php if ($fields['bank_name']->show) { ?>
          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['bank_name'], $model->bank_name) ?>
          </div>
        <?php } ?>
      </div>
    <?php } ?>
    <div class="row">
      <input name="bank_account_number" value="" type="hidden">
      <input name="bank_identifier_code" value="" type="hidden">
      <?php if ($fields['bank_iban']->show || $fields['bank_bic']->show) { ?>
        <div class="col-md-12"><?= $this->getMess('sepa_data') ?></div>
      <?php } ?>
    </div>

    <?php if ($fields['bank_iban']->show || $fields['bank_bic']->show) { ?>
      <div class="row rechnung-checked">
        <?php if ($fields['bank_iban']->show) { ?>
          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['bank_iban'], $model->bank_iban) ?>
          </div>
        <?php } ?>
        <?php if ($fields['bank_bic']->show) { ?>

          <div class="col-md-6">
            <?= $this->getRegistrationField($fields['bank_bic'], $model->bank_bic) ?>
          </div>
        <?php } ?>
      </div>
    <?php } ?>

    <?php if ($fields['bank_sepa_mandat']->show) { ?>
      <div class="row dashed-border">
        <div class="col-md-8">
          <?= $this->getRegistrationField($fields['bank_sepa_mandat'], $model->bank_sepa_mandat) ?>
        </div>
        <input name="bank_sepa_referenz" value="" type="hidden">
      </div>
    <?php } ?>

    <?php if (!$change) { ?>
      <div class="row">
        <div class="col-md-12">
          <input name="user_right" id="user_right_button" type="checkbox" value="1" class="check">
          <span class="podlog2"></span>
          <?= $this->getMess('confirmation') ?>
        </div>
      </div>
    <?php } ?>
    <div class="row">
      <div class="col-md-3"><input value="<?= lang('button_send') ?>" class="button" type="submit"></div>
      <div class="col-md-3">
        <div class="label-wrapper">
          <?php if (!$change) { ?>
            <span class="label button-label">
              <span class="red">*</span>
              <?= $this->getMess('obligatory') ?>
            </span>
          <?php } ?>
        </div>
      </div>
    </div>
    <?= (!$change ? $consent_text : ''); ?>
  </form>
<?php if ((defined('LOGIN_AS_EMAIL') && LOGIN_AS_EMAIL) || USE_STEP_FORM_REGISTRATION) { ?>
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
      testForm('reg_bank_iban', 'testiban', '')
      Inputmask('<?= USE_TEST_AND_MASK_IBAN ? : 'AA 99 9999 9999 9999 9999 99' ?>').mask(document.getElementById('reg_bank_iban'));
      Inputmask({
        mask: '********[***]',
        definitions: {
          '*': {validator: '[A-Z0-9]', casing: 'upper'},
        },
        onBeforeMask: function (value, opts) {
          return value.toUpperCase();
        },
        greedy: false,
        showMaskOnHover: false
      }).mask(document.getElementById('reg_bank_bic'));
      $('#reg_bank_iban').bind('blur', function () {
        testForm('reg_bank_iban', 'testiban', '')
      })
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