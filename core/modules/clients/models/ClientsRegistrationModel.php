<?php

namespace AC\core\modules\clients\models;

use AC\core\modules\clients\tables\RegistrationFieldTable;
use AC\core\system\model\BaseModel;
use Service;

class ClientsRegistrationModel extends BaseModel
{
  /**
   * @var RegistrationFieldTable
   */
  private $registration_fields;

  public function getRegistrationFields($key = 'name')
  {
    if ($this->registration_fields == null) {
      $this->registration_fields = $this->engine->clients->getRegistrationFields($key, true);
    }

    return $this->registration_fields;
  }

  public function loadFieldsParams($data)
  {
    foreach ($this->getRegistrationFields() as $field) {
      $field->show                             = (int)isset($data[$field->name]['show']);
      $field->required                         = (int)isset($data[$field->name]['required']);
      $this->registration_fields[$field->name] = $field;
    }

    return true;
  }

  public function saveRegistrationFields()
  {
    if ($this->registration_fields != null) {
      $error = false;
      foreach ($this->registration_fields as $field) {
        if (!$this->engine->clients->updateRegistrationFields($field)) {
          $error = true;
        }
      }
      if (!$error) {
        return true;
      }
    }

    return false;
  }

  public function getFieldsRequiredAsArray()
  {
    $required = array();
    /** @var RegistrationFieldTable $field */
    foreach ($this->getRegistrationFields() as $field) {
      if ($field->show && $field->required) {
        if (!(Service::request()->_('encash') == 2 &&
          (($field->name == 'bank_bic') ||
            ($field->name == 'bank_iban') ||
            ($field->name == 'bank_name') ||
            ($field->name == 'account_owner')))) {
          $required[] = $field->name;
        }
      }
    }

    return $required;
  }

  /** Получить недоступные площадки из доступных для игры
   * @param $_availableSports
   *
   * @return array
   */
  public function getUnavailableSportByTypeBySeason($_availableSports = array()): array
  {
    $result = array();
    //Берем данные о кортах и спорте
    foreach (array_keys(module('areas')->useModel()->getSportByTypeBySeason(true)) as $key) {
      if (!in_array($key, $_availableSports)) {
        $result[] = $key;
      }
    }

    return $result;
  }

  /** Получить не доступные площадки в виде строки для дефолтных значений при регистрации
   * @return string
   */
  public function getDefaultUnavailableSportByTypeBySeason(): string
  {
    $result             = array();
    foreach (module('clients')->useModel()->getEngine()->availableSportByTypeBySeason() as $key => $item) {
      if (!$item['active']) {
        $result[] = $key;
      }
    }

    return implode(';', $result);
  }
}