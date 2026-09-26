<?php

namespace AC\core\system\helpers;

class DataHelper
{
  /**
   * @param array $items
   * @param array $params
   * @return array
   */
  public static function getDataAs(array $items, array $params = []): array
  {
    $result = [];
    $key = $params['key'] ?? null;
    foreach ($items as $item) {
      $item = (array)$item;
      $value = self::getAs($item, $params['as'] ?? 'array', $params);
      if (!empty($key) && isset($item[$key])) {
        $result[$item[$key]] = $value;
      } else {
        $result[] = $value;
      }
    }
    
    return $result;
  }
  
  /**
   * @param array  $item
   * @param string $as
   * @param array  $params
   * @return array|object
   */
  public static function getAs(array $item, string $as = 'array', array $params = []): array|object
  {
    return match ($as) {
      'dto'      => $params['dtoClass'] ? $params['dtoClass']::fromArray($item) : (object)$item,
      'stdClass' => (object)$item,
      default    => $item,
    };
  }
}