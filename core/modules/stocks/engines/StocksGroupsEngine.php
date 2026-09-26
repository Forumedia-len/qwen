<?php

namespace AC\core\modules\stocks\engines;

use AC\core\system\db\CompareDB;
use AC\core\system\db\DB;
use AC\core\system\db\GenerateQueryDB;
use AC\core\system\db\Query;
use AC\core\system\engine\BaseEngine;
use AC\core\system\helpers\ObjectHelper;
use AC\core\system\model\BaseModel;

/**
 * Класс StocksGroupsEngine управляет работой с таблицей резервных групп товаров.
 * Реализует методы для взаимодействия с БД: получение данных, удаление записей, обновление связанных таблиц.
 */
class StocksGroupsEngine extends BaseEngine
{

  /**
   * Устанавливает имя таблицы для работы.
   *
   * @param string|null $table_name Имя таблицы, если null — используется значение по умолчанию.
   * @return void
   */
  public function setTableName($table_name = null)
  {
    $table_name = $table_name ?? 'reservations_stocks_groups';

    parent::setTableName($table_name);
  }

  /**
   * Возвращает имя модели, связанной с таблицей.
   *
   * @param string|null $tableModelName Не используется, так как имя модели определяется динамически.
   * @return string Имя класса модели.
   */
  public function tableModelName($tableModelName = null)
  {
    return parent::tableModelName(get_class(ObjectHelper::createEntity('stocksGroup')));
  }

  /**
   * Получает данные из таблицы и сохраняет их в переданный массив.
   *
   * @param array $data Массив для хранения результатов (передается по ссылке).
   * @param array $conditions Условия фильтрации данных.
   * @param array $_order Параметры сортировки.
   * @param array $params Дополнительные параметры запроса.
   * @return bool Возвращает true, если данные успешно получены, иначе false.
   */
  public function getData(&$data = [], $conditions = array(), $_order = array(), $params = []): bool
  {
    if (parent::getData($dataResult, $conditions, $_order, $params)) {
      foreach ($dataResult as $datum) {
        $data[$datum->id] = $datum;
      }
      return true;
    }

    return false;
  }

  /**
   * Удаляет запись из таблицы после проверки её существования.
   *
   * @param BaseModel $model Модель, которую необходимо удалить.
   * @return bool Возвращает true, если удаление прошло успешно, иначе false.
   */
  public function delete(BaseModel $model): bool
  {
    if ((bool)Query::sqlQuery('select count(id) from ' . Query::tableName($this->tableName()) . ' where id= ' . $model->id, [], true,
      ['onlyOne' => true])) {
      return parent::delete($model);
    }

    return false;
  }
  
  /** Получить типы групп опций.
   * Получаем типы из файлов, через подключение классов.
   * Прописываем типы в ручную.
   *
   * @return array
   */
  public function getTypes(): array
  {
    return [
      'MWB' => ObjectHelper::createEntity('groupTypes\MessageWhenBooking', null, 'MWB',
        [
          'description' => lang('Adds a message when booking', 'stocks_groups'),
          'title'       => lang('Additional text after booking', 'type_stock_group'),
          'typeUse'     => 'html',
          'useAlways'  => false,
        ]),
//      'LRB' => ObjectHelper::createEntity('groupTypes\LikeRadioButtons', null, 'LRB',
//        [
//          'description' => lang('Only one value from the list can be used.', 'stocks_groups'),
//          'title'       => lang('Use a single value', 'type_stock_group'),
//          'typeUse'     => 'bool',
//          'useAlways'  => true,
//        ])
    ];
  }

  /**
   * Генерирует SQL-запросы для обновления связанных таблиц.
   *
   * @param array &$queries Массив для хранения сгенерированных запросов (передается по ссылке).
   * @return void
   */
  protected function updateLinkedTables(&$queries): void
  {
    $siteDB    = DB::instance();
    $gQDBS     = new GenerateQueryDB($siteDB);
    $compare   = CompareDB::compareTwoTables($siteDB->getStructureTable('reservations_stocks'),
      DB::instance('base')->getStructureTable('reservations_stocks'), []);
    $queries[] = $gQDBS->generateUpdateTable('reservations_stocks', $compare);
  }
}
