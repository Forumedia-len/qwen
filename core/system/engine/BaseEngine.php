<?php

namespace AC\core\system\engine;

use AC\core\system\db\Query;
use AC\core\system\model\BaseModel;
use AC\core\system\object\BaseObject;
use PDO;

class BaseEngine
{
  protected $query;
  private   $tableName;
  /**
   * @var BaseModel
   */
  private $_model;

  public function __construct($model = null)
  {
    if ($model) {
      $this->setModel($model);
    }

    $this->setTableName();
    $this->query = new Query($this->tableName());
    $this->checkAndInstallTables([$this->tableName]);
  }

  use InstallTableIfNotExist;

  protected function setModel($model)
  {
    $this->_model = $model;
  }

  public function getModel(): BaseModel
  {
    return $this->_model;
  }

  /** Название таблицы
   * @return string
   */
  public function tableName()
  {
    return isset($this->_model) ? $this->_model->tableName() : $this->tableName;
  }

  /**
   * Возвращает базовое имя модели для присвоения
   *
   * @param bool|string $modelName
   *
   * @return string
   */
  public function modelName($modelName = false)
  {
    return isset($this->_model) ? get_class($this->_model) : ($modelName ? $modelName : 'stdClass');
  }

  public function tableModelName($tableModelName = null)
  {
    $modelName = $this->modelName();
    return $tableModelName ?? (!in_array(basename(get_parent_class($modelName)), ['stdClass', 'BaseModel', ''])
      ? get_parent_class($modelName)
      : $modelName);
  }

  /**
   * Задать название таблицы
   *
   * @param null|string $table_name
   */
  public function setTableName($table_name = null)
  {
    if (!$table_name) {
      $table_name = $this->tableName();
    }
    $this->tableName = $table_name;
  }

  /**
   *  Получить все данные в виде Массива моделей по условиям
   *
   * @param array $conditions
   *
   * @param array $_order
   * @param array $params - параметры
   *
   * @return array
   */
  public function all($conditions = [], $_order = [], $params = [])
  {
    $order = '';
    if ($_order) {
      $order = implode(',', $_order);
    }
    $query  = 'SELECT ' . (!empty($params['select']) && is_string($params['select']) ? $params['select'] : '*') . ' FROM ' . $this->query->getTableName() . $this->query->generateQueryCondition($conditions) . ($order ? ' ORDER BY ' . $order
        : '');
    $result = [];
    $config = [
      'style' => $params['style'] ?? PDO::FETCH_CLASS
    ];
    if ($config['style'] === PDO::FETCH_CLASS) {
      $config = array_merge($config, [
        'argument'        => $params['argument'] ?? $this->tableModelName(),
        'constructorArgs' => $params['constructorArgs'] ?? null
      ]);
    }

    /** @var BaseModel $item */
    $data = Query::sqlQuery(
      $query,
      [],
      true,
      $config
    );

    foreach (($data ?? []) as $item) {
      if (is_object($item) && method_exists($item, 'getPrimaryKey') && isset($item->{$item->getPrimaryKey()})) {
        $result[$item->{$item->getPrimaryKey()}] = $item;
      } else {
        $result[] = $item;
      }
    }

    return $result;
  }

  /**
   *  Получить один элемент из базы
   *
   * @param       $id
   * @param array $params
   * @return
   */
  public function one($id, array $params = [])
  {
    $query  = 'select * from ' . $this->query->getTableName() . ' where ' . ($this->_model ? $this->_model->getPrimaryKey() : 'id') . ' = :id';
    $config = [
      'style'   => $params['style'] ?? PDO::FETCH_CLASS,
      'onlyOne' => true
    ];
    if ($config['style'] === PDO::FETCH_CLASS) {
      $config = array_merge($config, [
        'argument'        => $params['argument'] ?? $this->tableModelName(),
        'constructorArgs' => $params['constructorArgs'] ?? null,
      ]);
    }
    
    return Query::sqlQuery(
      $query,
      [':id' => $id],
      true,
      $config
    );
  }

  public function getData(&$data = [], $conditions = [], $_order = [], $params = [])
  {
    $data = $this->all($conditions, $_order, $params);

    return !empty($data);
  }


  /** Базовая функция получения одного элемента из базы по его id
   *
   * @param int        $id
   * @param BaseObject $result
   *
   * @return bool
   */
  public function getDataById($id, &$result = [], $params = [])
  {
    if ($result = $this->one($id, $params)) {
      return true;
    }

    return false;
  }

  /** Вставить новую строку в базу данных
   *
   * @param $model BaseModel
   *
   * @return bool
   */
  public function insert($model)
  {

    return Query::sqlQuery('insert into ' . $this->query->getTableName() . ' set ' . $this->generateQuerySet($model), [],
      false) ? (int)Query::getLastId() : false;
  }

  /** Обновить элемент базы
   *
   * @param $model BaseModel
   *
   * @return bool
   */
  public function update(BaseModel $model): bool
  {
    return Query::sqlQuery(
      'update ' . $this->query->getTableName() . ' set ' . $this->generateQuerySet($model) . ' where ' . $model->getPrimaryKey() . ' = "' . $model->{$model->getPrimaryKey()} . '"',
      [],
      false
    );
  }

  /**
   *  Удалить строку из базы данных
   *
   * @param $model BaseModel
   *
   * @return bool
   */
  public function delete(BaseModel $model): bool
  {
    return Query::sqlQuery(
      'delete from ' . $this->query->getTableName() . ' where ' . $model->getPrimaryKey() . ' = "' . $model->{$model->getPrimaryKey()} . '"',
      [],
      false
    );
  }

  /**
   * @param BaseModel $model
   * @return bool
   */
  public function active(BaseModel $model): bool
  {
    return Query::sqlQuery(
      'update ' . $this->query->getTableName() . ' set active=' . $model->active . ' where ' . $model->getPrimaryKey() . ' = "' . $model->{$model->getPrimaryKey()} . '"',
      [],
      false
    );
  }

  /**
   *  Перебор атрибутов для создания запроса в базу данных
   *
   * @param $model BaseModel
   *
   * @return string
   */
  public function generateQuerySet(BaseModel $model): string
  {
    $query_set = '';
    $i         = 0;
    foreach ($model->attributes() as $attribute) {

      if (!in_array($attribute, [$model->getPrimaryKey(), 'preferences'], true)) {
        if ($model->$attribute || !in_array($attribute, $model->optionalAttributesInsert(), true)) {
          $query_set .= ($i != 0 ? ', ' : '') . $attribute . '="' . $model->$attribute . '"';
          $i++;
        }
      }
    }

    return $query_set;
  }

  public function getDbo()
  {
    return $this->query->getDB();
  }
  
  public function getAs(array $item, $as = 'array', $params = []): array|object
  {
    return match ($as) {
      'dto'      => $params['dtoClass'] ? $params['dtoClass']::fromArray($item) : (object)$item,
      'stdClass' => (object)$item,
      default    => $item,
    };
  }
  
  public function getDataAs($items, $params): array
  {
    $result = [];
    $key = $params['key'] ?? null;
    foreach ($items as $item) {
      $value = $this->getAs($item, $params['as'] ?? 'array', $params);
      if (!empty($key) && isset($item[$key])) {
        $result[$item[$key]] = $value;
      } else {
        $result[] = $value;
      }
    }
    
    return $result;
  }
}