<?php

namespace AC\core\modules\clients\views;

use AC\core\system\App;
use AC\core\system\helpers\ObjectHelper;

use AC\core\system\object\StdObject;

use AC\core\system\view\Layouts;
use AC\core\system\view\View;
use Service;

class RegistrationView extends View
{
  /**
   * @var StdObject
   */
  public $mess_params;
  public $key = 'registration';

  public function __construct()
  {
    parent::__construct();
    $this->mess_params = ObjectHelper::createObject(
      array('group' => 'registration_fields', 'text' => 'text_registration_field_', 'parameter' => 'parameter_registration_field_'),
      true
    );
    if (Service::url()->getTemplatePath() == 'touch') {
      $this->addJsFile('masked_input_touchscreen', 'touch', false, 'cdn');
    }
    if (Service::engines()->getTypeAlias() == 'open') {
      $this->content['header'] = $this->getMess('title');
    }
  }

  function getRegistrationField($field, $value = null, $change = false, $suffix_mess = '')
  {
    $device                 = Service::url()->getTemplatePath();
    $field->title           = $this->getMess($field->name.$suffix_mess, 'parameter');
    $field->input_title     = $this->getMess($field->name . '_input_title'.$suffix_mess, 'parameter');
    $field->input_attribute = ' id="reg_'.$field->name.'"';
    $field->additional      = '';
    switch ($field->name) {
      case 'bank_iban':
        $field->input_attribute .= ' maxlength="34" style="font-size:13px;"';
        if (!defined('USE_TEST_AND_MASK_IBAN') || USE_TEST_AND_MASK_IBAN) {
          $field->additional = '<span id="test-iban-field"></span>';
        }
        break;
      case 'bank_bic':
        $field->input_attribute .= ' maxlength="11"';
        break;
      case 'login':
        $field->type = $change ? 'string' : $field->type;
        break;
    }

    if (!$field->input_title) {
      $field->input_title = $field->title;
    }

    switch ($field->type) {
      case 'date':
        $type = 'date';
        break;
      case 'radio':
        $type = 'radio';
        break;
      case 'checkbox':
        $type = 'checkbox';
        break;
      case 'select':
        $type = 'select';
        break;
      case 'string':
        $type = 'string';
        break;
      default:
        $type  = 'default';
        $value = $value !== null ? htmlspecialchars($value, ENT_QUOTES) : '';
        break;
    }
    $field->value = $value;

    return Layouts::render('registration_fields/' . $type, array('field' => $field), $device);
  }

  public function getMess($alias, $type = 'text')
  {
    return lang($this->mess_params->{$type} . $alias, $this->mess_params->group);
  }

}