<?php

namespace AC\core\modules\config\models;

use AC\core\engines\ConfigEngine;
use AC\core\modules\areas\entities\dto\AreaTypeDto;
use AC\core\modules\config\tables\ConfigTable;
use AC\core\system\helpers\ObjectHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use stdClass;

class ConfigModel extends ConfigTable
{
  private array $_data                  = [];
  protected     $baseGetFunction        = 'getConfigDataById';
  protected     $baseGetFunctionAllData = 'getDataConfig';
  protected     $primary_key            = 'id';
  protected     $baseEngine             = 'ConfigEngine';
  private       $_changes               = [];
  /**
   * @var ConfigEngine
   */
  protected $engine;

  public $door;
  public $common;
  public $email;
  public $paypal;
  public $open;
  public $close;
  public $registration;
  public $account;
  public $ticket;
  public $personal_account;

  public function getConfig($type = null)
  {
    $this->engine->getDataConfig($data, true);
    foreach ($this->getTypes() as $typeConfig) {
      if (!isset($data[$typeConfig])) {
        $data[$typeConfig] = ObjectHelper::createEntity();
      }
    }

    return $type && isset($data[$type]) ? $data[$type] : $data;
  }

  public function save()
  {
    $config = $this->getConfig();
    foreach ($this->_data as $type => $data_value) {
      foreach ($data_value as $alias => $value) {
        if ($config[$type]->{$alias} != $value) {
          if (!$this->engine->saveItem($alias, $value, $type)) {
            return false;
          }
          if (!isset($this->_changes[$type]) || !is_object($this->_changes[$type])) {
            $this->_changes[$type] = new stdClass();
          }
          $this->_changes[$type]->$alias = $value;
        }
      }
    }

    return true;
  }

  public function rules()
  {
    $ruleBool = [
      'paypal_use',
      'order_notify',
      'user_can_remove_abo',
      'refund_when_cancel_ticket',
      'refund_for_cancellation_reservation_paid_by_paypal',
      'use_season_in_choosing_type_sport',
      'refund_method_full_price',
      'use_2FA_admin',
    ];
    /** @var AreaTypeDto $type */
    foreach (ModCommHelper::get('areas', 'areasType/selectActiveTypes', [], 'data') as $type) {
      $ruleBool[] = config('letterTemplates')?->getAliasDisableSendMailClientReservation($type->getCurrentAlias());
    }
    return [
      [
        $ruleBool,
        'bool',
      ],
      //      array('api_signature', 'integer'),
    ];
  }

  /** Получить актуальные типы конфига из таблицы плюс возможные типы для наследуемых моделей
   *
   * @return string[]
   */
  public function getTypes(): array
  {
    return array_merge($this->engine->getTypesConfig(), $this->getCurrentTypes());
  }

  public static function getByType($type)
  {
    return getEngine('config', false)->getConfigByType($type);
  }

  public function load($data, $validate = true)
  {
    if (!empty($data)) {
      $config = $this->getConfig();
      $types  = array_unique(array_merge(array_keys($config), $this->getTypes()));
      foreach ($types as $type) {
        if (!isset($data->{$type})) {
          continue;
        }
        $data_type = $config[$type] ?? new stdClass();
        $_type     = ObjectHelper::createObject(array_keys(array_merge((array)$data_type, (array)$data->{$type})));
        $_type->loadParams($data->{$type});
        $this->_data[$type] = ObjectHelper::createObject();
        foreach ($_type as $attribute => $value) {
          if ($validate) {
            $this->validateModelAttribute($_type, $attribute, $this->getAttributeLabel($attribute));
          }
          if ($_type->$attribute !== null) {
            $this->_data[$type]->addProperty($attribute, $_type->$attribute);
          }
        }

        if ($_type->hasErrors()) {
          $this->addErrors($_type->getErrors());
        }
      }
    }

    return !$this->hasErrors();
  }

  public function loadByAlias($alias, $value, $type, $validate = true)
  {
    if ($validate) {
      $this->validate($alias, $this->getAttributeLabel($alias));
    }
    if ($value !== null) {
      if (empty($this->_data[$type])) {
        $this->_data[$type] = ObjectHelper::createObject();
      }

      $this->_data[$type]->$alias = $value;
    }

    return !$this->hasErrors();
  }

  /**
   * Получить значение конфига по типу и алиасу.
   *
   * @param       $type
   * @param       $alias
   * @param array $params
   *
   * @return mixed
   */
  public static function _($type, $alias, array $params = [])
  {
    $byType = self::getByType($type);
    if (!is_object($byType)) {
      return null;
    }

    return $byType->{$alias} ?? null;
  }

  public function attributeLabel(?string $locale = null)
  {
    $label = [];
    foreach ($this->getConfig() as $type => $attributes) {
      foreach (array_keys((array)$attributes) as $attribute) {
        $label[$attribute] = lang('label_parameter_' . $attribute, 'config', [], null, $locale);
      }
    }

    return $label;
  }

  /** Получить какие данные были изменены и сохранены
   *
   * @param $type
   * @param $alias
   *
   * @return string|null
   */
  public function getChanges($type, $alias)
  {
    return $this->isChange($type, $alias) ? $this->_changes[$type]->$alias : null;
  }

  public function isChange($type, $alias)
  {
    return isset($this->_changes[$type]->$alias);
  }

  public function getEngine(): ConfigEngine
  {
    return parent::getEngine();
  }

  /** Получить используемые типы
   *  помогает добавлять элементы конфига несуществующие в таблице
   *
   * @return string[]
   */
  protected function getCurrentTypes(): array
  {
    $types = ['reservation'];
    if (function_exists('config')) {
      $clientRestriction = config('clientRestriction');
      if (is_object($clientRestriction) && method_exists($clientRestriction, 'getAdminConfigTypes')) {
        $types = array_merge($types, $clientRestriction->getAdminConfigTypes());
      }
    }

    return array_values(array_unique($types));
  }

  public function getByPrefixType(string $prefixType): array
  {
    return array_filter($this->getConfig(), function ($type) use ($prefixType) {
      return str_starts_with($type, $prefixType);
    }, ARRAY_FILTER_USE_KEY);
  }
}