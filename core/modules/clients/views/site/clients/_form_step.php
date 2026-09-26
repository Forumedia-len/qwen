<?php
/**
 * @var bool $change
 * @var array $fields
 * @var object $model
 * @var RegistrationView $this
 * @var  string $consent_text
 */
use AC\core\system\helpers\ReCaptchaHelper;
use AC\core\modules\clients\views\RegistrationView;

prepareClass('client_insert');

$strelka = '<svg version="1.2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 53 14" width="53" height="14"><defs><image  width="53" height="14" id="img1" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADUAAAAOCAMAAAC4haQsAAAAAXNSR0IB2cksfwAAAGBQTFRFzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzM7vTfPwAAACB0Uk5TABk49nNt/v+bBVP0wAoz5NwPGszoG+YCzuMMyAemA3SaiMKIAAAAXUlEQVR4nJ3Sxw6AMAwD0DDNbNmlzP//S4qExNnxOU+KEoswiajpL3GSKlSWoyh5VtVoWp4Zi67n2TBimkXAZ3EatWo29BuLjMd+sChc/rxY9H7ZsSg06qaN/O19AIu+BX3UZ0PCAAAAAElFTkSuQmCC"/></defs><style></style><use  href="#img1" x="0" y="0"/></svg>';
?>
<form method="post" id="form_reg_step" action="<?= site_url('registration.php') ?>" <?= ((isset($change) && $change) ? ''
  : 'onsubmit="return checkUserRight()"') ?> autocomplete="nope">

  <input type="hidden" name="action" value="<?= ((isset($change) && $change) ? 'update' : 'create') ?>">
  <?= ReCaptchaHelper::getReCaptchaScript('registration') ?>
  <?php if ((UNAVAILABLE_SPORTS) && (!$change)) {
    ?><input type="hidden" name="unavailable_sports" value="<?= UNAVAILABLE_SPORTS ?>" />
  <?php } ?>
  <?php if ($change) { ?>
    <input type="hidden" name="client_id" value="<?= $model->client_id ?>"/>
  <?php } ?>

  <div class="reg-step reg-step-1 ">
    <div class="title"><span class="active"><?= lang('reg_step1') ?></span><?= $strelka ?><span
        data-class="reg-step-2"><?= lang('reg_step2') ?></span><?= $strelka ?><span data-class="reg-step-3"><?= lang('reg_step3') ?></span></div>
    <div class="row">
      <div class="col-lg-6 col-md-12">
        <?= $this->getRegistrationField($fields['login'], $model->login, $change, "_step_reg") ?>
      </div>
    </div>
    <?php if ($fields['password']->show || $fields['password_confirmation']->show) { ?>
      <div class="row">
        <?php if ($fields['password']->show) { ?>
          <div class="col-lg-6 col-md-12">
            <?= $this->getRegistrationField($fields['password'], $model->password, false, '_step_reg') ?>
            <div class="param-dop"><?= $this->getMess('password_dop_step_reg', 'parameter') ?></div>
          </div>
        <?php } ?>
      </div>
      <div class="row">
        <?php if ($fields['password_confirmation']->show) { ?>
          <div class="col-lg-6 col-md-12">
            <?= $this->getRegistrationField($fields['password_confirmation'], $model->password_confirmation, false, '_step_reg') ?>
          </div>
        <?php } ?>
      </div>
    <?php } ?>

    <input type="hidden" name="email" value="<?= $model->email ?>"/>

    <script>
      $(document).ready(function () {
        $('.content').on('input', '[name=login]', function () {
          $('[name=email]').val($(this).val())
          // console.log($('[name=email]').val())
        })
      })
    </script>

    <div class="row">
      <div class="col-lg-6 col-md-12">
        <div class="button weiter"><?= lang('button_weiter') ?></div>
      </div>
    </div>

  </div>


  <div class="reg-step reg-step-2 width-0">
    <div class="title"><span data-class="reg-step-1"><?= lang('reg_step1') ?></span><?= $strelka ?><span
        class="active"><?= lang('reg_step2') ?></span><?= $strelka ?><span data-class="reg-step-3"><?= lang('reg_step3') ?></span></div>
    <?php if ($fields['name']->show || $fields['surname']->show) { ?>
      <div class="row">
        <?php if ($fields['name']->show) { ?>
          <div class="col-lg-6 col-md-12">
            <?= $this->getRegistrationField($fields['name'], $model->name, false, '_step_reg') ?>
          </div>
        <?php } ?>
        <?php if ($fields['surname']->show) { ?>
          <div class="col-lg-6 col-md-12">
            <?= $this->getRegistrationField($fields['surname'], $model->surname, false, '_step_reg') ?>
          </div>
        <?php } ?>
      </div>
    <?php } ?>
    <?php if ($fields['firm']->show) { ?>
      <div class="row">
        <?php if ($fields['firm']->show) { ?>
          <div class="col-lg-6 col-md-12">
            <?= $this->getRegistrationField($fields['firm'], $model->firm, false, '_step_reg') ?>
          </div>
        <?php } ?>
      </div>
    <?php } ?>
    <?php if ($fields['address']->show) { ?>
      <div class="row">
        <?php if ($fields['address']->show) { ?>
          <div class="col-lg-6 col-md-12">
            <?= $this->getRegistrationField($fields['address'], $model->address, false, '_step_reg') ?>
          </div>
        <?php } ?>
      </div>
    <?php } ?>

    <?php if (($fields['post_code']->show) || ($fields['city']->show)) { ?>
      <div class="row">
        <?php if ($fields['post_code']->show) { ?>
          <div class="col-lg-6 col-md-12">
            <?= $this->getRegistrationField($fields['post_code'], $model->post_code, false, '_step_reg') ?>
          </div>
        <?php } ?>
        <?php if ($fields['city']->show) { ?>
          <div class="col-lg-6 col-md-12">
            <?= $this->getRegistrationField($fields['city'], $model->city, false, '_step_reg') ?>
          </div>
        <?php } ?>
      </div>
    <?php } ?>
    <?php if ($fields['phone']->show) { ?>
      <div class="row">
        <?php if ($fields['phone']->show) { ?>
          <div class="col-lg-6 col-md-12">
            <?= $this->getRegistrationField($fields['phone'], $model->phone, false, '_step_reg') ?>
          </div>
        <?php } ?>
      </div>
    <?php } ?>
    <div class="row rewerse-mob">
      <div class="col-lg-6 col-md-12">
        <div class="button zuruck"><?= lang('button_back') ?></div>
        <div class="button weiter"><?= lang('button_weiter') ?></div>
      </div>
    </div>
  </div>

  <div class="reg-step reg-step-3 width-0">
    <div class="title"><span data-class="reg-step-1"><?= lang('reg_step1') ?></span><?= $strelka ?><span
        data-class="reg-step-2"><?= lang('reg_step2') ?></span><?= $strelka ?><span class="active"><?= lang('reg_step3') ?></span></div><?php
    if (!$change && ($fields['encash_pp']->show == 1 || (isset($fields['encash_invoice']) && $fields['encash_invoice']->show == 1) || $fields['encash_cash']->show == 1)) { ?>
      <div class="row">
        <div class="col-md-3 col-sm-12 oplata-title">
          <div class="label-wrapper">
            <span class="label"><?= $this->getMess('encash_step_reg', 'parameter') ?> </span>
          </div>
        </div>
        <?php if ($fields['encash_invoice']->show) { ?>
          <div class="col-md-3 col-sm-3 oplata">
            <?= $this->getRegistrationField(
              $fields['encash_invoice'],
              (object)array(
                'checked' => !isset($model->encash) ? 'checked' : ($model->encash == 1 ? 'checked' : ''),
                'value'   => 1
              ), false, '_step_reg'
            ) ?>
          </div>
        <?php } ?>
        <?php if ($fields['encash_cash']->show) { ?>
          <div class="col-md-3 col-sm-3">
            <?= $this->getRegistrationField(
              $fields['encash_cash'],
              (object)array(
                'checked' => !isset($model->encash) && !$fields['encash_invoice']->show ? 'checked' : ($model->encash === 0 ? 'checked' : ''),
                'value'   => 0
              ), false, '_step_reg'
            ) ?>
          </div>
        <?php } ?>
        <?php if ($fields['encash_pp']->show) { ?>
          <div class="col-md-3 col-sm-3">
            <?= $this->getRegistrationField(
              $fields['encash_pp'],
              (object)array(
                'checked' => (!isset($model->encash) && !$fields['encash_invoice']->show && !$fields['encash_cash']->show && $fields['encash_pp']->show) || (isset($model->encash) && $model->encash == 2)
                  ? 'checked' : '',
                'value'   => 2
              ), false, '_step_reg'
            ) ?>
          </div>
        <?php } ?>
      </div>
    <?php } ?>

    <?php if ($fields['account_owner']->show) { ?>
      <div class="row rechnung-checked">
        <?php if ($fields['account_owner']->show) { ?>
          <div class="col-lg-6 col-md-12">
            <?= $this->getRegistrationField($fields['account_owner'], $model->account_owner, false, '_step_reg') ?>
          </div>
        <?php } ?>
      </div>
    <?php } ?>

    <?php if ($fields['bank_iban']->show) { ?>
      <input name="bank_account_number" value="" type="hidden">
      <input name="bank_identifier_code" value="" type="hidden">
      <div class="row rechnung-checked">
        <?php if ($fields['bank_iban']->show) { ?>
          <div class="col-lg-6 col-md-12">
            <?= $this->getRegistrationField($fields['bank_iban'], $model->bank_iban, false, '_step_reg') ?>

          </div>
        <?php } ?>
      </div>
    <?php } ?>

    <?php if ($fields['bank_bic']->show) { ?>
      <div class="row rechnung-checked">
        <?php if ($fields['bank_bic']->show) { ?>
          <div class="col-lg-6 col-md-12">
            <?= $this->getRegistrationField($fields['bank_bic'], $model->bank_bic, false, '_step_reg') ?>
            <div class="param-dop"> <?= $this->getMess('bank_bic_dop_step_reg', 'parameter') ?></div>
          </div>
        <?php } ?>
      </div>
    <?php } ?>

    <div class="row">
      <div class="col-md-12 mess-underline">
        <?= $this->getMess('message_bottom_step_reg', 'parameter') ?>
      </div>
    </div>

    <?php if (!$change) { ?>
      <div class="row">
        <div class="col-md-12 user_right-div">
          <input name="user_right" id="user_right_button" type="checkbox" value="1" class="check">
          <span class="podlog2"></span>
          <?= $this->getMess('confirmation' . '_step_reg') ?>
        </div>
      </div>
    <?php } ?>

    <div class="row rewerse-mob">
      <div class="col-md-12">
        <div class="button zuruck"><?= lang('button_back') ?></div>
        <input value="<?= lang('button_send_step_reg') ?>" class="button" type="submit"></div>

    </div>
    <?php if ($fields['bank_sepa_mandat']->show) { ?>
      <input name="bank_sepa_mandat_day" value="<?= date('d'); ?>" type="hidden"/>
      <input name="bank_sepa_mandat_month" value="<?= date('m'); ?>" type="hidden"/>
      <input name="bank_sepa_mandat_year" value="<?= date('Y'); ?>" type="hidden"/>
    <?php } ?>
    <?php if (!$fields['bank_sepa_mandat']->show) { ?>
      <input name="bank_sepa_mandat" value="<?= date('Y-m-d'); ?>" type="hidden"/>
    <?php } ?>
  </div>
</form>

<script src="<?= cdn_url(paths()->getAssetsDir('js/jquery.validate.min.js')) ?>"></script>

<script>
  $(document).ready(function () {
    $('.content').on('input', '[name=login]', function () {
      $('[name=email]').val($(this).val())
    })
  })
</script>

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
<script>
  <?php if($_REQUEST['action'] == 'create'):?>
  $(document).ready(function () {
    let class_tab = getCookie('class_tab');
    if (!$.isEmptyObject(class_tab)) {
      $('.reg-step').addClass('width-0');
      $('.' + class_tab).removeClass('width-0');
    }
  })
  <?php endif;?>
  //валидатор на спецсимволы
  jQuery.validator.addMethod("specsimbol", function (value, element, params) {
    var format = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/;
    if (format.test(value)) {
      return true;
    } else {
      return false;
    }
  }, "<?=$this->getMess("query_password_specsimbol")?>");
  //валидатор на существование клиента
  jQuery.validator.addMethod("exist", function (val, element, params) {
    var res = false;
    $.ajax({
        method: "POST",
        url: "<?=base_url()?>mapi/api.php",
        data: [{name: 'action', value: 'check_login'}, {name: 'login', value: val}],
        async: false,
        success: function (response) {
          res = response;
        }
      }
    );
    return res;
  }, "<?=lang('error_attribute_unique_login', 'message_error', array(
    'attribute' => ('<b>' . $param_name = lang('parameter_registration_field_login_step_reg', 'registration_fields') . '</b>')
  ));?>");

  var validationManager;
  //первый шаг
  var createValid1 = function () {
    if (validationManager != undefined) validationManager.destroy();
    validationManager = $('#form_reg_step').validate({
      rules: {
        login: {
          required: Boolean(<?= $fields['login']->required ?>),
          email: Boolean(<?= $fields['email']->required ?>),
          minlength: 6,
          exist: true
        },
        password: {
          required: Boolean(<?= $fields['password']->required ?>),
          minlength: 8,
          specsimbol: true
        },
        password_confirmation: {
          equalTo: '#reg_password'
        }
      },
      messages: {
        password: {
          required: "<?=$this->getMess("query_password_require")?>",
          minlength: "<?=$this->getMess("query_password_min_8")?>"
        },
        login: {
          required: "<?=lang('error_attribute_type_required', 'message_error', ['attribute' => $this->getMess('login_step_reg', 'parameter')])?>",
          email: "<?=lang('error_attribute_type_email', 'message_error', ['attribute' => $this->getMess('login_step_reg', 'parameter')])?>",
          minlength: "<?=lang('error_attribute_min_value', 'message_error',
            ['attribute' => $this->getMess('login_step_reg', 'parameter'), 'value' => '6'])?>"
        },
        password_confirmation: {
          equalTo: "<?=lang('error_attribute_incorrect', 'message_error',
            ['attribute' => str_replace("<br/>", " ", $this->getMess('password_confirmation_step_reg', 'parameter'))])?>"
        }
      }
    });
  }
  //второй шаг
  var createValid2 = function () {
    if (validationManager != undefined) validationManager.destroy();
    validationManager = $('#form_reg_step').validate({
      rules: {
        name: {
          required: Boolean(<?= $fields['name']->required ?>),
          minlength: 3
        },
        surname: {
          required: Boolean(<?= $fields['surname']->required ?>),
          minlength: 3
        },
        address: {
          required: Boolean(<?= $fields['address']->required ?>),
          minlength: 3
        },
        post_code: {
          required: Boolean(<?= $fields['post_code']->required ?>)
        },
        city: {
          required: Boolean(<?= $fields['city']->required ?>)
        },
        phone: {
          required: Boolean(<?= $fields['phone']->required ?>)
        }
      },
      messages: {
        name: {
          required: "<?=lang('error_attribute_type_required', 'message_error', ['attribute' => $this->getMess('name_step_reg', 'parameter')])?>",
          minlength: "<?=lang('error_attribute_min_value', 'message_error',
            ['attribute' => $this->getMess('name_step_reg', 'parameter'), 'value' => '3'])?>"
        },
        surname: {
          required: "<?=lang('error_attribute_type_required_surname', 'message_error',
            ['attribute' => $this->getMess('surname_step_reg', 'parameter')])?>",
          minlength: "<?=lang('error_attribute_min_value', 'message_error',
            ['attribute' => $this->getMess('surname_step_reg', 'parameter'), 'value' => '3'])?>"
        },
        address: {
          required: "<?=lang('error_attribute_type_required', 'message_error', ['attribute' => $this->getMess('address_step_reg', 'parameter')])?>",
          minlength: "<?=lang('error_attribute_min_value', 'message_error',
            ['attribute' => $this->getMess('address_step_reg', 'parameter'), 'value' => '3'])?>"
        },
        post_code: {
          required: "<?=lang('error_attribute_type_required', 'message_error', ['attribute' => $this->getMess('post_code_step_reg', 'parameter')])?>"
        },
        city: {
          required: "<?=lang('error_attribute_type_required_city', 'message_error', ['attribute' => $this->getMess('city_step_reg', 'parameter')])?>"
        },
        phone: {
          required: "<?=lang('error_attribute_type_required_phone', 'message_error',
            ['attribute' => $this->getMess('phone_step_reg', 'parameter')])?>"
        }
      }
    });
  }


  createValid1();


  var get_valid = function (obj) {
    return validationManager.form();
  }


  $('.reg-step .button.weiter').on('click', function () {
    if (!get_valid(this)) return;

    $(this).closest('.reg-step').addClass('width-0');
    if ($(this).closest('.reg-step').hasClass('reg-step-1')) {
      $('.reg-step-2').removeClass('width-0');
      setCookie('class_tab', 'reg-step-2');
      createValid2();
    }
    if ($(this).closest('.reg-step').hasClass('reg-step-2')) {
      $('.reg-step-3').removeClass('width-0');
      setCookie('class_tab', 'reg-step-3');
    }
  })

  $('.reg-step .button.zuruck').on('click', function () {
    $(this).closest('.reg-step').addClass('width-0');
    if ($(this).closest('.reg-step').hasClass('reg-step-2')) {
      $('.reg-step-1').removeClass('width-0');
      setCookie('class_tab', 'reg-step-1');
      createValid1();
    }
    if ($(this).closest('.reg-step').hasClass('reg-step-3')) {
      $('.reg-step-2').removeClass('width-0');
      setCookie('class_tab', 'reg-step-2');
      createValid2();
    }
  })

  $('[data-class]').on('click', function () {
    if (!get_valid(this) &&
      (($(this).closest('.reg-step').hasClass('reg-step-2') && $(this).data('class') == 'reg-step-3') ||
        ($(this).closest('.reg-step').hasClass('reg-step-1') && $(this).data('class') == 'reg-step-2') ||
        ($(this).closest('.reg-step').hasClass('reg-step-1') && $(this).data('class') == 'reg-step-3')) ||
      ($(this).closest('.reg-step').hasClass('reg-step-1') && $(this).data('class') == 'reg-step-3')
    ) return;

    $('.reg-step').addClass('width-0');
    $('.' + $(this).data('class')).removeClass('width-0');
    setCookie('class_tab', $(this).data('class'));
    if ($(this).data('class') == 'reg-step-2') {
      createValid2();
    }
    if ($(this).data('class') == 'reg-step-1') {
      createValid1();
    }
  })

  $('.reg-step [name="name"],.reg-step [name="surname"],.reg-step [name="firm"]').on('change', function () {
    let name = ($('.reg-step [name="firm"]').length == 0 || $('.reg-step [name="firm"]').val().trim() == '') ? ($('.reg-step [name="name"]').val() + ' ' + $('.reg-step [name="surname"]').val()) : $('.reg-step [name="firm"]').val();
    $('.reg-step [name="account_owner"]').val(name);
  })

  $('.reg-step [name="encash"]').on('change', function () {
    if ($(this).val() != '1') {
      $('.rechnung-checked').css('display', 'none');

    } else {
      $('.rechnung-checked').css('display', 'block');
    }
  })
</script>