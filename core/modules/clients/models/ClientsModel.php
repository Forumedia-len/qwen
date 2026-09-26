<?php

namespace AC\core\modules\clients\models;

use AC\core\engines\ClientsEngine;
use AC\core\modules\clients\tables\ClientsTable;
use AC\core\modules\clients\tables\RegistrationFieldTable;
use AC\core\modules\config\models\ConfigClubStateModel;
use AC\core\modules\config\models\ConfigNdsModel;
use AC\core\modules\discounts\models\DiscountsModel;
use AC\core\system\helpers\DateHelper;
use AC\core\system\helpers\ObjectHelper;
use Service;

/**
 * Class ClientsModel
 *
 * todo переработать Модель Клиента так чтобы все принадлежащие ему надбавки , все наценки, extra, скидки подключались к клиенту и хранились у него
 */
class ClientsModel extends ClientsTable
{
  protected $baseGetFunction = 'getClientData';
  protected $primary_key     = 'client_id';
  protected $baseEngine      = 'ClientsEngine';
  public    $password;
  public    $password_confirmation;
//  /**
//   * @var array[ClientStockDto]
//   */
//  public $stock      = [];
//  public $spec_price = [];
  public $nds_rate;
  public $discount_retail;
  public $discount_ticket;
  /**
   * @var DiscountsModel
   */
  public $discounts;
  public $extra;
  
  /**
   * @var ClientsEngine
   */
  public $engine;
  
  private $new = false;
  
  protected bool $checkFieldsValueChange = true;
  
  public function __construct($client_id = 'current', $necessarilySetDataById = false)
  {
    if (!$client_id) {
      $this->new = true;
    }
    
    if ($client_id === 'guest') {
      $necessarilySetDataById = true;
      $client_id              = null;
    }
    
    parent::__construct($client_id, $necessarilySetDataById);
  }
  
  public function rules()
  {
    $fields_model = new ClientsRegistrationModel();
    $fields       = $fields_model->getRegistrationFields();
    $required     = $shield = $rules = [];
    
    if (USE_STEP_FORM_REGISTRATION && !empty(Service::request()->_('client_id', null))) {
      if ($fields['password']->show) {
        $rules[] = [
          'password',
          'password',
          [
            'min'               => $this->{$this->getPrimaryKey()} == null || $this->password ? (USE_STEP_FORM_REGISTRATION ? 8 : 6) : null,
            'confirmation'      => $fields['password_confirmation']->show && $fields['password_confirmation']->required ? true : false,
            'fieldConfirmation' => 'password_confirmation'
          ]
        ];
      }
      if ($fields['post_code']->show) {
        $rules[] = ['post_code', 'string', ['is_numeric' => true]];
      }
      
      return $rules;
    }
    
    if ($this->new) {
      foreach ($fields_model->getFieldsRequiredAsArray() as $field) {
        if ($fields[$field]->type == 'text') {
          $shield[] = $field;
        }
        if ($this->{$this->getPrimaryKey()} !== null && ($field == 'password' || $field == 'password_confirmation')) {
          continue;
        }
        // если выбрана оплата по paypal, то банковские данные не обязательны
        if ($this->encash == 2 && $fields[$field]->group == 'bank_data') {
          continue;
        }
        $required[] = $field;
      }
      $rules = [[$required, 'required']];
      if (USE_RECAPTCHA_V4) {
        $rules[] = ['', 'captchaV4', ['scope' => RECAPTCHA_SCORE]];
      }
      $rules[] = [
        'unavailable_sports',
        'default',
        [
          'value' => module('clients')->useModel('clientsRegistration')->getDefaultUnavailableSportByTypeBySeason()
        ]
      ];
    }
    if ($fields['login']->show) {
      
      if ((defined('LOGIN_AS_EMAIL') && LOGIN_AS_EMAIL) || USE_STEP_FORM_REGISTRATION) {
        $rules[] = ['login', 'email', ['min' => 6]];
        if (USE_STEP_FORM_REGISTRATION) {
          $rules[] = ['login', 'login', ['min' => 6, 'max' => 40]];
        }
      } else {
        $rules[] = ['login', 'login', ['min' => 6, 'max' => 20]];
      }
      
    }
    if ($fields['password']->show) {
      $rules[] = [
        'password',
        'password',
        [
          'min'               => $this->{$this->getPrimaryKey()} == null || $this->password ? (USE_STEP_FORM_REGISTRATION ? 8 : 6) : null,
          'confirmation'      => $fields['password_confirmation']->show && $fields['password_confirmation']->required ? true : false,
          'fieldConfirmation' => 'password_confirmation'
        ]
      ];
    }
    if ($fields['name']->show) {
      $arr_check['min'] = 3;
      if (USE_CHECK_FOR_NAME_SURNAME) {
        $arr_check['max_up_case'] = USE_CHECK_FOR_NAME_SURNAME;
      }
      $rules[] = ['name', 'string', $arr_check];
    }
    
    if ($fields['post_code']->show) {
      $rules[] = ['post_code', 'string', ['is_numeric' => true]];
    }
    
    if ($fields['surname']->show) {
      $arr_check['min'] = 3;
      if (USE_CHECK_FOR_NAME_SURNAME) {
        $arr_check['max_up_case'] = USE_CHECK_FOR_NAME_SURNAME;
      }
      $rules[] = ['surname', 'string', $arr_check];
    }
    if ($fields['email']->show) {
      $rules[] = ['email', 'email'];
    }
    $def_club_state = $this->setClubStateByStudent();
    if ($this->new) {
      $rules[] = ['mode', 'default', ['value' => 1]];
      $rules[] = [
        ['system_mode', 'super', 'area_type', 'limit_day', 'reservations_removed'],
        'default',
        ['value' => 0]
      ];
      
      $rules[] = ['area_type', 'default', ['value' => DEFAULT_CLIENT_AREA_TYPE]];
      $rules[] = ['club_state', 'default', ['value' => $def_club_state]];
      $rules[] = ['active', 'default', ['value' => Service::configDB('registration', 'user_activation')]];
      $rules[] = ['nds', 'default', ['value' => $this->setDefaultNds()]];
      $rules[] = ['registered', 'default', ['value' => date('Y-m-d H:i:s')]];
      $rules[] = ['abo_delete', 'default', ['value' => Service::configDB('registration', 'user_can_remove_abo')]];
      $rules[] = ['refund_for_ticket', 'default', ['value' => Service::configDB('personal_account', 'refund_when_cancel_ticket')]];
      $rules[] = [
        'refund_for_paypal',
        'default',
        ['value' => Service::configDB('personal_account', 'refund_for_cancellation_reservation_paid_by_paypal')]
      ];
      if (in_array('bank_iban', $fields_model->getFieldsRequiredAsArray())) {
        $rules[] = ['bank_iban', 'iban'];
      }
    }
    
    if ($fields['student']->show) {
      $this->setStudent();
    }
    $rules[] = ['student', 'bool'];
    $rules[] = ['student', 'default', ['value' => 0]];
    if (!empty($shield)) {
      $rules[] = [$shield, 'string', ['useShielding' => true]];
    }
    
    return $rules;
  }
  
  public function attributeLabel(?string $locale = null)
  {
    $attribute_label = [];
    $fields          = new ClientsRegistrationModel();
    /** @var RegistrationFieldTable $field */
    foreach ($fields->getRegistrationFields() as $field) {
      $attribute_label[$field->name] = lang(
        'parameter_registration_field_' . $field->name . '_input_title',
        'registration_fields',
        [],
        null,
        $locale
      );
    }
    
    return $attribute_label;
  }
  
  public function setDataById($client_id)
  {
    if ($client_id === 'current' && $this->engine->checkAuthorization() == true) {
      $this->setData($this->engine->current_client_data);
      $this->checkError = false;
    } else {
      parent::setDataById($client_id);
    }
    $this->discounts = $this->getDiscounts();
  }
  
  /**
   * @param bool $back
   */
  public function renderDateType($back = false)
  {
    $fields = new ClientsRegistrationModel();
    /** @var RegistrationFieldTable $field */
    foreach ($fields->getRegistrationFields() as $field) {
      if ($field->show && $field->type == 'date') {
        $date = ObjectHelper::createObject(
          [
            'day'   => Service::request()->_($field->name . '_day'),
            'month' => Service::request()->_($field->name . '_month'),
            'year'  => Service::request()->_($field->name . '_year'),
          ],
          true
        );
        if (!$back) {
          if ($this->{$field->name} == null && $date->day && $date->month && $date->year) {
            $this->{$field->name} = DateHelper::renderDateObjectAsString($date);
          }
          $this->{$field->name} = DateHelper::renderDateStringAsObject(
            $this->{$field->name},
            $this->getDefaultDate($field->name)
          );
        } else {
          $this->{$field->name} = DateHelper::renderDateObjectAsString($date);
        }
      }
    }
  }
  
  public function getDefaultDate($field)
  {
    switch ($field) {
      case 'birthday':
        return [
          'day'   => '01',
          'month' => '01',
          'year'  => '1950',
        ];
        break;
      default :
        return [
          'day'   => date('d'),
          'month' => date('m'),
          'year'  => date('Y'),
        ];
    }
  }
  
  public static function changeParameterForAll($parameter, $value, $clients = [])
  {
    $engine = new ClientsEngine();
    $engine->changeParameterForAll($parameter, $value);
  }
  
  private function getDiscounts()
  {
    if ($this->discount !== null && $this->discount != 0) {
      return new DiscountsModel($this->discount);
    }
    
    return null;
  }
  
  /**
   *  если чувак член , то у него ндс 7% если ничлен то 19%
   */
  protected function setDefaultNds()
  {
    if (defined('NDS_DEFAULT_REGISTRATION') && NDS_DEFAULT_REGISTRATION) {
      foreach (explode('|', NDS_DEFAULT_REGISTRATION) as $item) {
        [$club_state, $nds_id] = explode(':', $item);
        if ($club_state == $this->club_state) {
          return $nds_id;
        }
      }
    }
    
    return ConfigNdsModel::getDefaultId();
  }
  
  /**
   * Используется на tennishalle-konstanz.de
   * @return mixed
   */
  protected function setClubStateByStudent()
  {
    if (REGISTER_STUDENT_AS_V2 && (Service::request()->_('student', 0) != 0)) {
      $club_state_v     = ConfigClubStateModel::getMark('club_rate2')->id;
      $this->club_state = $club_state_v;
      $this->nds        = ConfigNdsModel::getDefaultId();
    } else {
      $club_state_v = ConfigClubStateModel::getMark('no_club_rate')->id;
    }
    return $club_state_v;
  }
  
  /**
   * Используется на tennishalle-konstanz.de
   */
  protected function setStudent()
  {
    if (REGISTER_STUDENT_AS_V2 && $this->student != Service::request()->_('student', 0) && (Service::request()->_('student', 0) == 0)) {
      $this->student    = 0;
      $this->club_state = ConfigClubStateModel::getMark('no_club_rate')->id;
      $this->nds        = $this->setDefaultNds();
    }
    if (REGISTER_STUDENT_AS_V2 && (Service::request()->_('student', 0) == 1)) {
      $this->student    = 1;
      $this->club_state = ConfigClubStateModel::getMark('club_rate2')->id;
      $this->nds        = ConfigNdsModel::getDefaultId();
    }
  }
}