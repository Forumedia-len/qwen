<?php

namespace AC\core\modules\clients\controllers;

use AC\app\config\CountryConfig;
use AC\core\modules\clients\models\ClientsModel;
use AC\core\modules\clients\models\ClientsRegistrationModel;
use AC\core\modules\mailing\entities\enums\ModeTemplate;
use AC\core\system\controller\BaseController;
use PHPMailer\PHPMailerException;
use Service;


class ClientsController extends BaseController
{
  protected $base_model       = 'ClientsModel';
  public    $default_action   = 'showFormRegistration';
  public    $default_template = 'clients';
  public    $baseView         = 'RegistrationView';

  /**
   *  Основной метод работы с регистрацией клиентов
   *
   * @param bool $new
   *
   * @return mixed
   */
  public function showFormRegistration($new = true)
  {
    $show_form = true;
    $id        = $new ? null : Service::request()->_('client_id', null);
    if (!$new && $this->engine->clients->checkAuthorization()) {
      $id = 'current';
    }
    $fields_model = new ClientsRegistrationModel();
    $fields       = $fields_model->getRegistrationFields();
    /** @var CountryConfig $countryConfig */
    $countryConfig = config('country');
    if ($countryConfig->detectTable() && Service::query()::getDB()->checkField('country', 'clients') && isset($fields['country'])) {
      $fields['country']->values  = $countryConfig->getCountries();
      $fields['country']->default = $countryConfig->getDefaultCode();
    }
    if (!$new) {
      foreach ($fields as $field) {
        $field->required = 0;
      }
    }

    $back  = '';
    $model = new ClientsModel($id);
    if (Service::request()->isPost() && $model->load(Service::request()->all())) {
      $model->bank_iban = str_replace(' ', '', $model->bank_iban ?? '');
      if (!empty($model->bank_iban)) {
        $model->bank_sepa_referenz = $model->bank_iban;
      }
      $model->renderDateType(true);

      if ($model->save()) {
        $show_form = false;
        if ($new) {
          $this->sendMailByInsert($model);
          if (($model->encash !== null && (int)$model->encash == 2)
            && (Service::configDB('registration', 'user_activation'))) {
            $this->view->addMessage(lang('info_text_after_registration_cash_payer_aut_active', 'messages_common'));
          }
          if (($model->encash !== null && (int)$model->encash == 2)
            && !(Service::configDB('registration', 'user_activation'))) {
            $this->view->addMessage(lang('info_text_after_registration_cash_payer_man_active', 'messages_common'));
          }
          if (($model->encash !== null && (int)$model->encash == 1)
            && (Service::configDB('registration', 'user_activation'))) {
            $this->view->addMessage(lang('info_text_after_registration_invoice_aut_active', 'messages_common'));
          }
          if (($model->encash !== null && (int)$model->encash == 1)
            && !(Service::configDB('registration', 'user_activation'))) {
            $this->view->addMessage(lang('info_text_after_registration_invoice_man_active', 'messages_common'));
          }
        } else {
          $this->sendMailAfterChangeDataClient($model);
          $this->view->addMessage(lang('Your personal data has been changed!', 'registration_fields'));
          $back = '<p><a href="registration.php?action=view" class="back">' . lang('back') . '</a></p>';
        }
      }
    }

    if ($model->isErrors()) {
      $this->view->addMessages($model->getErrors(), 'error');
    }
    $model->renderDateType(); // переводим дату в массив

    return $this->render(
      'index',
      [
        'title'    => !$new
          ? lang('text_registration_field_title_change', 'registration_fields')
          : lang(
            'info_text_registration_mask_top',
            'messages_common'
          ),
        'form'     => $show_form ? $this->render(
          '_form' . ((USE_STEP_FORM_REGISTRATION && !$new) || !USE_STEP_FORM_REGISTRATION ? '' : '_step'),
          [
            'fields'       => $fields,
            'model'        => $model,
            'change'       => !$new,
            'consent_text' => lang('info_text_registration_mask_bottom', 'messages_common'),
          ]
        ) : $back,
        'messages' => $this->view->issetMessages('error')
          ? '<p class="error">' . lang('text_error') . ':' . $this->view->getMessagesTypeError() . '</p>'
          : $this->view->getMessagesTypeIsNotError(),
      ]
    );
  }

  /**
   * @param $model ClientsModel
   */
  public function sendMailByInsert($model)
  {
    $tpl_data['LOGIN']                = $model->login;
    $tpl_data['NAME']                 = $model->name;
    $tpl_data['SURNAME']              = $model->surname;
    $tpl_data['BIRTHDAY']             = $model->birthday;
    $tpl_data['PHONE']                = $model->phone;
    $tpl_data['PHONE_MOBILE']         = $model->phone_mobile;
    $tpl_data['FAX']                  = $model->fax;
    $tpl_data['POST_CODE']            = $model->post_code;
    $tpl_data['CITY']                 = $model->city;
    $tpl_data['ADDRESS']              = $model->address;
    $tpl_data['EMAIL']                = $model->email;
    $tpl_data['BANK_ACCOUNT_HOLDER']  = $model->account_owner;
    $tpl_data['BANK_ACCOUNT_NUMBER']  = $model->account_number;
    $tpl_data['BANK_IDENTIFIER_CODE'] = $model->bank_index;
    $tpl_data['BANK_NAME']            = $model->bank_name;
    $tpl_data['FIRMA']                = $model->firm;

    $tpl_data['STATE_STUD'] = match ((int)$model->student) {
      1       => lang('Yes'),
      default => lang('Not'),
    };
    $tpl_data['CLUB_STATE'] = match ((int)$model->club_state) {
      2       => lang('Yes'),
      1       => lang('Not'),
      default => '',
    };

    $mailer = Service::mailer();
    if ((bool)Service::configDB('email', 'order_notify')) {
      //формирование информационного письма о новом клиенте
      //отправка письма
      $mailer->dispatch(explode(',', Service::configDB('email', 'notify_email')), ModeTemplate::ADMIN->value, 'registration', $tpl_data);
    }
    if ((bool)Service::configDB('registration', 'user_activation')) {
      //отправка письма
      $mailer->dispatch($model->email, ModeTemplate::USER->value, 'activate', $tpl_data, $model->lang ?? config('lang')->getDefault());
    }
  }

  public function create()
  {
    return $this->update(true);
  }

  public function update($new = false)
  {
    return $this->showFormRegistration($new);
  }

  public function view()
  {
    return $this->update();
  }

  public function registration()
  {
    return 1;
  }

  /**
   * @param ClientsModel $model
   *
   * @return bool
   * @throws PHPMailerException
   */
  protected function sendMailAfterChangeDataClient($model): bool
  {
    $locale = config('lang')->getDefault();
    if (config('mailing')->useSendMailAdminAfterChangeDataClient()
      && (bool)Service::configDB('email', 'order_notify')
      && ($changeAttributes = $model->getChangeAttributeValues($locale))
      && count($changeAttributes) > 0) {
      if (isset($changeAttributes['password_md5'])) {
        $changeAttributes['password_md5'] = [
          'old'   => lang('The password has been changed', 'recover_password', [], null, $locale),
          'new'   => '',
          'title' => lang('parameter_registration_field_password', 'registration_fields', [], null, $locale),
        ];
      }
      $subject = lang('The client\'s data has been changed', 'mailing.send_after_change_client',
        ['client' => $model->name . ' ' . $model->surname . ' (' . $model->login . ')'], null, $locale);
      $mailer  = Service::mailer();
      $mailer->isHTML();
      $mailer->msgHTML($this->render('mailAfterChange', ['change' => $changeAttributes, 'title' => $subject, 'locale' => $locale]));
      $mailer->Subject = $subject;
      $to              = explode(',', Service::configDB('email', 'notify_email'));
      $mailer->clearAddresses();
      foreach ($to as $to1) {
        $mailer->addAddress($to1);
      }

      return $mailer->_mail($to);
    }

    return false;
  }
}