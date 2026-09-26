<?php

namespace AC\app\config;

use AC\core\system\config\BaseConfig;
use Service;

/**
 * Класс CountryConfig
 *
 * Класс конфигурации для работы со странами.
 * Предоставляет методы для получения списка стран, настроек страны по умолчанию
 * и генерации HTML-элементов выбора страны.
 */
class CountryConfig extends BaseConfig
{
  /**
   * Получить все доступные страны в виде ассоциативного массива.
   *
   * @return array Ассоциативный массив, где ключи - коды стран, а значения - названия стран
   */
  public function getCountries(): array
  {
    $result = [];
    $q      = 'select * from ' . Service::query()::tableName($this->getTableName()) . ' order by title';
    if ($this->detectTable() && ($rows = Service::query()::sqlQuery($q)) && !empty($rows)) {
      foreach ($rows as $row) {
        $result[$row['code']] = $row['title'];
      }
      return $result;
    }
    return [];
  }

  /**
   * Проверить, существует ли таблица стран в базе данных.
   *
   * Использует статическое кэширование для предотвращения множественных запросов к базе данных
   * во время одного запроса.
   *
   * @return bool Истина, если таблица стран существует, иначе ложь
   */
  public function detectTable(): bool
  {
    static $detectTable;
    if ($detectTable === null) {
      $detectTable = Service::query()::getDB()->checkTable($this->getTableName());
    }

    return $detectTable;
  }


  /**
   * Получить настройку страны по умолчанию.
   *
   * Возвращает страну по умолчанию из базы данных, если таблица существует и установлена
   * настройка по умолчанию, в противном случае возвращает Германию (DE) как резервный вариант.
   * Использует статическое кэширование для предотвращения множественных запросов к базе данных
   * во время одного запроса.
   *
   * @return array Ассоциативный массив, где ключ - код страны, а значение - название страны
   */
  public function getDefault(): array
  {
    static $default;
    if ($default === null) {
      $default = $this->defaultCountryAsConst();
      if ($this->detectTable()) {
        if (($row = Service::query()::sqlQuery('select * from ' . Service::query()::tableName($this->getTableName()) . ' where `default` = ?', ['1'],
          true,
          ['onlyOne' => true]))) {
          $default = [$row['code'] => $row['title']];
        }
      }
    }

    return $default;
  }

  private function defaultCountryAsConst(): array
  {
    return defined('DEFAULT_COUNTRY') ? DEFAULT_COUNTRY : ['DE' => 'Deutschland'];
  }

  /**
   * Получить код страны по умолчанию.
   *
   * @return string Код страны (например, 'DE') страны по умолчанию
   */
  public function getDefaultCode(): string
  {
    return strtoupper(key($this->getDefault()));
  }

  /**
   * Сгенерировать HTML-элемент выбора страны.
   *
   * @return string Отрендеренный HTML-элемент выбора страны
   */
  public function asHtml(): string
  {
    return useLayout()->render('select', [
      'name'    => 'country',
      'values'  => $this->getCountries(),
      'current' => $this->getDefault(),
    ], 'common');
  }

  public function getTableName(): string
  {
    return 'countries';
  }

  public function normalize(mixed $country): string
  {
    return $country ? strtoupper($country) : $this->getDefaultCode();
  }
}