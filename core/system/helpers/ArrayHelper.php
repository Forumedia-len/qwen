<?php

namespace AC\core\system\helpers;

class ArrayHelper
{
  public static function makeArray($val, $searchField = null)
  {
    $val = is_array($val) ? $val : [$val];
    if ($searchField !== null && isset($val[$searchField])) {
      $val = [$val];
    }
    
    return $val;
  }
  
  public static function unsetByValue(&$array, $value)
  {
    if (($delete_key = array_search($value, $array)) !== false) {
      unset($array[$delete_key]);
    }
  }
  
  public static function keyFirstArray($arr)
  {
    foreach ($arr as $key => $unused) {
      return $key;
    }
    
    return null;
  }
  
  public static function extractKey($key, &$arr)
  {
    return array_splice($arr, array_search($key, array_keys($arr), true), 1)[$key] ?? null;
  }
  
  /**
   * Searches an array through dot syntax. Supports
   * wildcard searches, like foo.*.bar
   *
   * @return mixed
   */
  public static function dotArraySearch($index, array $array)
  {
    $segments = preg_split('/(?<!\\\)\./', rtrim($index, '* '), 0, PREG_SPLIT_NO_EMPTY);
    
    $segments = array_map(static function ($key) {
      return str_replace('\.', '.', $key);
    }, $segments);
    
    return self::arraySearchDot($segments, $array);
  }
  
  
  /**
   * Used by `dot_array_search` to recursively search the
   * array with wildcards.
   *
   * @return mixed
   * @internal This should not be used on its own.
   *
   */
  public static function arraySearchDot(array $indexes, array $array)
  {
    // Grab the current index
    $currentIndex = $indexes ? array_shift($indexes) : null;
    
    if ((empty($currentIndex) && (int)$currentIndex !== 0) || (!isset($array[$currentIndex]) && $currentIndex !== '*')) {
      return null;
    }
    
    // Handle Wildcard (*)
    if ($currentIndex === '*') {
      $answer = [];
      
      foreach ($array as $value) {
        $answer[] = self::arraySearchDot($indexes, $value);
      }
      
      $answer = array_filter($answer, static function ($value) {
        return $value !== null;
      });
      
      if ($answer !== []) {
        if (count($answer) === 1) {
          // If array only has one element, we return that element for BC.
          return current($answer);
        }
        
        return $answer;
      }
      
      return null;
    }
    
    // If this is the last index, make sure to return it now,
    // and not try to recurse through things.
    if (empty($indexes)) {
      return $array[$currentIndex];
    }
    
    // Do we need to recursively search this value?
    if (is_array($array[$currentIndex]) && $array[$currentIndex] !== []) {
      return self::arraySearchDot($indexes, $array[$currentIndex]);
    }
    
    // Otherwise we've found our match!
    return $array[$currentIndex];
  }
  
  /**
   * Returns the value of an element at a key in an array of uncertain depth.
   *
   * @param mixed $key
   *
   * @return mixed|null
   */
  public static function arrayDeepSearch($key, array $array)
  {
    if (isset($array[$key])) {
      return $array[$key];
    }
    
    foreach ($array as $value) {
      if (is_array($value) && ($result = self::arrayDeepSearch($key, $value))) {
        return $result;
      }
    }
    
    return null;
  }
  
  public static function greedySortingByValue(array $items = [], int $countColumn = 2): array
  {
    arsort($items); // сортируем по значениям по убыванию
    
    $columns = array_fill(0, $countColumn, []);
    $sums    = array_fill(0, $countColumn, 0);
    
    foreach ($items as $key => $value) {
      // Находим индекс колонки с минимальной суммой
      $minIndex = array_search(min($sums), $sums);
      
      // Добавляем элемент
      $columns[$minIndex][$key] = $value;
      $sums[$minIndex]          += $value;
    }
    foreach ($columns as &$column) {
      ksort($column);
    }
    
    return $columns;
  }
}