<?php

namespace AC\core\system\model;

use AC\core\engines\TranslateEngine;
use AC\core\system\db\Query;
use AC\core\system\helpers\FileHelper;
use AC\core\system\helpers\StringHelper;

use AC\core\system\helpers\ValidatorHelper;
use AC\core\system\object\BaseObject;
use AC\core\system\validators\Validator;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Class BaseModel
 */
class BaseModel extends BaseObject implements BaseTable
{
  private   $_data;
  protected $primary_key            = 'id';
  protected $baseGetFunction        = 'getDataById';
  protected $baseGetFunctionAllData = 'getData';
  protected $baseInsertFunction     = 'insert';
  protected $baseUpdateFunction     = 'update';
  protected $baseDeleteFunction     = 'delete';
  protected $checkError;
  protected $error;
  protected $tableName;
  protected $_name                  = null;
  private   $_oldAttribute;
  protected $useBaseEngine          = false;

  protected bool $useLanguageOptions = false;

  protected bool $checkFieldsValueChange = false;


  public function __construct($id = null, $necessarilySetDataById = false)
  {
    if (self::className() !== 'BaseModel') {
      $this->_name = explode('Model', FileHelper::getBasename(self::className()));
      if (!$this->useBaseEngine && $this->baseEngine == 'Engines') {
        $this->baseEngine = $this->_name[0] . 'Engine';
      }
    }
    parent::__construct();

    if (!empty($id) || $necessarilySetDataById) {
      $this->setDataById($id);
    }
    if ($this->useLanguageOptions) {
      $this->setValueParameterUseLanguageOptions();
    }
  }

  public function get_Name($i = 0): string
  {
    return StringHelper::underscoreToCamelCase($this->_name[$i] ?? '');
  }

  public function insert(&$error_code = false)
  {
    if ($this->checkInsert()) {
      return true;
    }

    return false;
  }

  public function unsetDataBaseParams($data)
  {
    unset($data->baseGetFunction);
    unset($data->primary_key);
    unset($data->baseEngine);
    unset($data->engine);

    return $data;
  }

  /** Сделать какие-то операции перед тем как внести вставить
   * @return bool
   */
  public function beforeInsert()
  {
    return true;
  }

  /** Проверка данных перед вставкой
   *
   * @return bool
   */
  public function checkInsert()
  {
    if ($this->beforeInsert()) {
      return true;
    }

    return false;
  }

  /**
   *  Произвести некоторые действия после вставки в базу данных
   * @return bool
   */
  public function afterInsert()
  {
    return true;
  }

  /** Перебор своиств и их назначение
   *
   * @param array|object $data
   *
   * @param bool         $validate проводить валидацию или нет
   *
   * @return bool
   */
  public function load($data, $validate = true)
  {
    if (!empty($data)) {
      if (!is_object($data)) {
        $this->_data = $this->unsetDataBaseParams((object)$data);
      } else {
        $this->_data = $this->unsetDataBaseParams($data);
      }
      foreach ($this->getAllPublicProperties() as $key => $value) {
        $this->setOldAttribute($key, $value);
        if (isset($this->_data->{$key})) {
          $this->{$key} = $this->_data->{$key};
        }
      }
      if (!$validate || ($this->validate())) {
        return true;
      }
    }

    return false;
  }

  protected function getAllPublicProperties()
  {
    $properties = [];
    $class      = new ReflectionClass($this);
    foreach ($this as $key => $value) {
      try {
        if (!in_array($key, ['_data', '_oldAttribute', 'engine'])) {
          $property = $class->getProperty($key);
          if ($property->isPublic() && !$property->isStatic()) {
            $properties[$key] = $value;
          }
        }
      } catch (ReflectionException $e) {
      }
    }
    foreach ($this->attributes() as $key) {
      if (!isset($properties[$key])) {
        $properties[$key] = $this->{$key};
      }
    }

    return $properties;
  }


  public function getOldAttribute()
  {
    return $this->_oldAttribute === null ? [] : $this->_oldAttribute;
  }

  public function getOldAttributeValue($attribute)
  {
    return isset($this->_oldAttribute[$attribute]) ? $this->_oldAttribute[$attribute] : null;
  }

  public function setOldAttribute($attribute, $value = null)
  {
    if (in_array($attribute, $this->attributes())) {
      $this->_oldAttribute[$attribute] = $value;
    }
  }

  public function getChangeAttributeValues(?string $locale = null)
  {
    $change = [];

    foreach ($this->attributes() as $attribute) {
      if ($attribute != $this->getPrimaryKey() && $this->$attribute != $this->getOldAttributeValue($attribute)) {
        $change[$attribute] = [
          'old'   => $this->getOldAttributeValue($attribute),
          'new'   => $this->$attribute,
          'title' => $this->getAttributeLabel($attribute, null, $locale),
        ];
      }
    }

    return $change;
  }

  /**
   *  Произвести какие-то действия с данными перед валидацией
   * @return bool по умолчанию возвращает истину
   */
  public function beforeValidate()
  {
    if ($this->useLanguageOptions) {
      $this->getValueParameterUseLanguageOptions();
    }

    return true;
  }

  /**
   * Произвести какие-то действия после валидации
   */
  public function afterValidate()
  {
  }


  /** Проверить данные
   *
   * @param object     $model
   * @param null|array $attributeNames
   * @param null|array $labelAttribute
   * @param bool       $clearErrors
   *
   * @return bool
   */
  public function validate($attributeNames = null, $labelAttribute = null, $clearErrors = true, $model = null)
  {
    if ($clearErrors) {
      $this->clearErrors($attributeNames);
    }
    if (!$this->beforeValidate()) {
      return false;
    }

    /** @var $validator Validator */
    foreach ($this->getValidators() as $validator) {
      if ($model == null) {
        $model = $this;
      }
      $validator->validateAttribute($model);
    }

    $this->afterValidate();

    return !$this->hasErrors();
  }

  public function getValidateRulesByType($type): array
  {
    $rules = [];
    foreach ($this->rules() as $rule) {
      if ($rule[1] === $type) {
        $rules[] = $rule[0];
      }
    }

    return array_merge(...$rules);
  }

  public function getRulesForField($field): array
  {
    $rules = [];
    foreach ($this->rules() as $rule) {
      foreach ($rule[0] as $ruleField) {
        if ($ruleField === $field) {
          $rules[] = [$rule[1] => $rule[2]];
        }
      }
    }

    return array_merge(...$rules);
  }


  /**
   * @param $model
   * @param $attributeName
   * @param $attributeLabel
   *
   * @return void
   *
   *             todo переработать вызов валидатора, сейчас правила и валидаторы вызываются каждый раз для каждого поля - куча лишних вызовов
   */
  public function validateModelAttribute($model, $attributeName, $attributeLabel = null)
  {
    /** @var $validator Validator */
    foreach ($this->getValidators() as $validator) {
      if ($attributeName == $validator->getAttribute()) {
        $validator->validateAttribute($model, $attributeLabel);
      }
    }
  }

  /** Возвращает правила валидации для атрибутов
   *
   */
  public function rules()
  {
    return [];
  }

  /** Получить данные по id
   *
   * @param $id
   */
  public function setDataById($id)
  {
    $data = [];
    if ($this->engine->{$this->baseGetFunction}($id, $data)) {
      $this->setData($data);
    } else {
      $this->addError(lang('message_not_element_in_base', 'message_error'));
      $this->checkError = true;
    }
  }

  /**
   * Returns the list of attribute names.
   * By default, this method returns all public non-static properties of the class.
   * You may override this method to change the default behavior.
   * @return array list of attribute names.
   */
  public function attributes($reflection = false): array
  {
    static $names;
    if (!isset($names[static::class])) {
      if (!$reflection && $this->tableName) {
        $names[static::class] = Query::getDB()->getFieldsTable($this->tableName());
      }
      if (empty($names[static::class])) {
        $names[static::class] = $this->attributesByParsingModel();
      }
    }

    return $names[static::class] ? : [];
  }

  /** Получить Атрибуты путем Reflection разбора свойств модели
   * @return array
   */
  public function attributesByParsingModel(): array
  {
    static $names;
    if (!isset($names[static::class])) {
      $class = new ReflectionClass($this);
      foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
        if (!$property->isStatic()) {
          $names[static::class][] = $property->getName();
        }
      }
    }

    return $names[static::class] ? : [];
  }

  /**
   * Названия Атрибутов
   * @return array возвращает массив
   */
  public function attributeLabel(?string $locale = null)
  {
    $groupName = StringHelper::camelCaseToUnderscore(str_replace('Model', '', self::className(false)));
    $label     = [];
    foreach ($this->attributes() as $attribute) {
      $title             = lang('label_' . $attribute, $groupName, [], null, $locale);
      $label[$attribute] = $title ? : ucfirst($attribute);
    }

    return $label;
  }

  /**
   *  Получить заголовок атрибута, или значение по умолчание , или название самого атрибута
   *
   * @param             $attribute
   * @param null        $default
   * @param string|null $locale
   *
   * @return mixed|string|null
   */
  public function getAttributeLabel($attribute, $default = null, ?string $locale = null)
  {
    $attributeLabels = $this->attributeLabel($locale);

    return isset($attributeLabels[$attribute]) ? $attributeLabels[$attribute] : ($default ? : ucfirst($attribute));
  }

  /** Присвоить свойствам Модели значения из базы или другого объекта
   *
   * @param null $data
   *
   * @return bool
   */
  public function setData($data)
  {
    if ($data !== null) {
      if ($this->load($data, false)) {
        $this->checkError = false;

        return true;
      } else {
        $this->checkError = true;

        return false;
      }
    } else {
      $this->checkError = true;

      return false;
    }
  }

  public function loadData($data)
  {
    $this->setData($data);

    return $this;
  }

  /**
   * Получить имя главного ключа таблицы
   * @return string
   */
  public function getPrimaryKey()
  {
    return $this->primary_key;
  }

  /**
   *  Получить данные
   * @return mixed
   */
  public function getData()
  {
    return $this->_data;
  }

  /**
   * Базовая функция получения всех данных из базы
   *
   * @param       $data
   * @param array $conditions
   * @param array $_order
   * @param array $params
   *
   * @return mixed
   */
  public function getListData(&$data, $conditions = [], $_order = [], $params = [])
  {
    return $this->engine->{$this->baseGetFunctionAllData}($data, $conditions, $_order, $params);
  }

  /**
   * Сохранить элемент в базе,
   * если есть примари ключ то обновляет,
   * если нет то вставляем новую запись
   * @return mixed
   */
  public function save()
  {
    return $this->{($this->{$this->primary_key} === null || empty($this->{$this->primary_key}) ? 'create' : 'update')}();
  }

  /** Создать элемент
   *
   */
  protected function create()
  {
    return $this->engine->{$this->baseInsertFunction}($this);
  }

  /** Обновить элемент
   *
   */
  protected function update()
  {
    return $this->engine->{$this->baseUpdateFunction}($this);
  }

  /** Удалить элемент
   *
   */
  public function remove()
  {
    return $this->engine->{$this->baseDeleteFunction}($this);
  }

  /**
   * Получить массив объектов Валидаторов
   * @return array
   */
  public function getValidators()
  {
    $validators = [];
    foreach ($this->rules() as $rule) {
      if (isset($rule[1])) {
        $attributes = (array)$rule[0];
        foreach ($attributes as $attribute) {
          if ($this->{$this->getPrimaryKey()} == null || (!$this->checkFieldsValueChange || $this->{$attribute} != $this->getOldAttributeValue($attribute))) {
            if ($validator = ValidatorHelper::getValidator($rule[1], $attribute, $rule[2] ?? [])) {
              $validators[] = $validator;
            }
          }
        }
      }
    }

    return $validators;
  }


  /**
   *
   * @param array $conditions
   *
   * @param array $order
   *
   * @return mixed
   */
  static public function findAll($conditions = [], $order = [])
  {
    $name  = self::className();
    $model = new $name();

    return $model->engine->all($conditions, $order);
  }

  public function tableName()
  {
    return isset($this->tableName) ? $this->tableName : '';
  }

  /** Если вызван статический метод которого нет, пробуем вызвать метод из Engine
   *
   * @param $method
   * @param $arguments
   *
   * @return mixed|null
   */
  public static function __callStatic($method, $arguments)
  {
    $name  = self::className();
    $model = new $name();
    if (method_exists($model->engine, $method)) {
      return $model->engine->$method(...$arguments);
    }

    return null;
  }

  /** Если вызван метод которого нет, пробуем вызвать метод из Engine
   *
   * @param $method
   * @param $arguments
   *
   * @return mixed|null
   */
  public function __call($method, $arguments)
  {
    if (method_exists($this->engine, $method)) {
      return $this->engine->$method(...$arguments);
    }

    return null;
  }

  public function isLoad($key)
  {
    return isset($this->_data->{$key});
  }

  /**
   * Возвращает массив с переменными которые используют языковую транскрипцию
   *
   * @return array
   */
  protected function parameterUseLanguageOptions(): array
  {
    return [];
  }

  /** Создает в заданных свойствах массив с языковыми значениями свойства
   * @return void
   */
  protected function setValueParameterUseLanguageOptions()
  {
    $typeBlock    = StringHelper::camelCaseToUnderscore(is_array($this->_name) && isset($this->_name[0]) ? $this->_name[0] : $this->_name);
    $translations = $this->{$this->getPrimaryKey()} ? (new TranslateEngine())->getTranslate($typeBlock, $this->{$this->getPrimaryKey()}) : [];

    foreach ($this->parameterUseLanguageOptions() as $parameter) {
      $oldParameter       = $this->{$parameter};
      $this->{$parameter} = [];
      foreach (config('lang')->getActiveLanguages() as $lang) {
        $this->{$parameter}[$lang] = $lang == config('lang')->getCurrentLang() ? ($oldParameter[$lang] ?? $oldParameter)
          : ($translations[$parameter][$lang] ?? '');
      }
    }
  }

  /** Создает из массива языковых значений свойства с языковой приставкой
   * @return void
   */
  protected function getValueParameterUseLanguageOptions()
  {
    foreach ($this->parameterUseLanguageOptions() as $parameter) {
      $values = $this->{$parameter};
      foreach ($values as $lang => $value) {
        $this->{$parameter . ($lang != config('lang')->getCurrentLang() ? '_' . $lang : '')} = $value;
      }
    }
  }

  public function optionalAttributesInsert(): array
  {
    return [];
  }

  public function toArray(): array
  {
    return $this->getAllPublicProperties();
  }
}