<?php

namespace AC\core\system\validators;


use AC\core\system\model\BaseModel;

/**
 * Class Validator
 *
 * Базовый метод для валидации данных
 */
class Validator
{
  /** @var bool Строгий режим для нового API проверки HTTP-ввода. */
  public bool $strict = false;

  /** @var bool Разрешить массив только валидатору, который умеет проверять его структуру. */
  public bool $allowArray = false;

  private   $_attribute;
  private   $_type;
  private   $_param = [];
  protected $messages;
  private   $_label;
  
  
  public function __construct($attribute, $type = '', $param = [])
  {
    $this->_attribute = $attribute;
    $this->_type      = $type;
    $this->_param     = (array)$param;
    $this->renderParams();
  }
  
  /**
   *  Инициализировать сообщения
   *
   * @param BaseModel|Validation $model
   */
  public function initMessages($model)
  {
    $mess = lang(
      'error_attribute_type_' . $this->_type . '_' . strtolower(str_replace([' ', '/'], '', $this->getLabel())),
      'message_error',
      ['attribute' => ('<b>' . $this->getLabel() . '</b>')]
    );
    
    if (strtolower(str_replace(' ', '_', $mess)) == 'error_attribute_type_' . $this->_type . '_' . strtolower(str_replace([' ', '/'], '',
        $this->getLabel()))) {
      $mess = lang(
        'error_attribute_type_' . $this->_type,
        'message_error',
        ['attribute' => ('<b>' . $this->getLabel() . '</b>')]
      );
    }
    $this->addMessage($mess, 'type');
    $this->initMessageToParams($model);
  }
  
  public function setLabel($label)
  {
    $this->_label = $label;
  }
  
  public function getLabel()
  {
    return isset($this->_label) ? $this->_label : null;
  }
  
  /**
   * Если есть параметры для них можно инициализировать сообщение об ошибках по этим параметрам
   *
   * @param BaseModel|Validation $model
   */
  public function initMessageToParams($model)
  {
  }
  
  /**
   *  Добавить сообщение возможное сообщение об ошибке
   *
   * @param $message
   * @param $alias
   */
  public function addMessage($message, $alias)
  {
    $this->messages[$alias] = $message;
  }
  
  /**
   * @param BaseModel|Validation $model
   * @param null| string $attributeLabel
   */
  
  public function validateAttribute($model, $attributeLabel = null)
  {
    if ($attributeLabel === null) {
      $attributeLabel = $model->getAttributeLabel($this->_attribute);
    }
    $this->setLabel($attributeLabel);
    $this->initMessages($model);
    if ($this->strict) {
      $value = $model->{$this->getAttribute()};
      if ((is_array($value) && !$this->allowArray) || is_object($value) || is_resource($value)) {
        $this->addError($model);
        return;
      }
      if ($this->isNullable($model)) {
        return;
      }
    }
    if (!$this->isNullable($model) && !$this->validateType($model)) {
      $this->addError($model);
      if ($this->strict) {
        return;
      }
    }
    
    $this->validateParams($model);
  }
  
  /**
   *  Получить аттрибут по которому проходит валидация
   * @return string - возвращает аттрибут
   */
  public function getAttribute()
  {
    /** Исключение из правила */
    if ($this->_attribute == 'encash_pp') {
      $this->_attribute = 'encash';
    }
    
    return $this->_attribute;
  }
  
  /**
   * Проверка на возможность нулевого значения
   * @param $model
   * @return bool
   */
  public function isNullable($model): bool
  {
    $attribute = $this->getAttribute();
    return $this->_type !== 'default' && $model->{$attribute} === null
      && method_exists($model, 'getValidateRulesByType')
      && !in_array($attribute, $model->getValidateRulesByType('required'), true);
  }
  
  /**
   *  Получить параметры
   * @return array
   */
  public function getParams()
  {
    return $this->_param;
  }
  
  /**
   * Распределить параметры по свойствам
   */
  public function renderParams()
  {
    foreach ($this->getParams() as $key => $value) {
      $this->{$key} = $value;
    }
  }
  
  /**
   * Валидация по параметрам если они есть
   *
   * @param BaseModel|Validation $model
   */
  public function validateParams($model)
  {
  }
  
  /**
   *  Проверка валидации по типу
   *
   * @param BaseModel|Validation $model
   *
   * @return bool
   */
  public function validateType($model)
  {
    return true;
  }
  
  /**
   *  Добавить в модель ошибку по аттрибуту
   *
   * @param BaseModel|Validation $model
   * @param $alias
   * @param $message
   */
  public function addError($model, $alias = 'type', $message = null)
  {
    if ($message === null) {
      $message = isset($this->messages[$alias]) ? $this->messages[$alias] : null;
    }
    if ($message) {
      $model->addError($message/*, $model->getAttributeLabel($this->_attribute)*/);
    }
  }
  
  /**
   * Проверяет пустой атрибут или нет
   *
   * @param $value
   *
   * @return bool
   */
  public function isEmptyValue($value)
  {
    if ($value === '' || $value === [] || $value === null) {
      return true;
    }
    
    return false;
  }
  
  public function changeMessage($alias)
  {
  }
  
  public function getType()
  {
    return $this->_type;
  }
}
