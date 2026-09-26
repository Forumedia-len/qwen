<?php

namespace AC\core\system\structure;

use AC\core\modules\users\controllers\admin\UsersAuthController;
use AC\core\system\db\Query;
use AC\core\system\helpers\UrlHelper;
use Service;


/**
 * структура сайта
 */
class Structure
{
  private        $tree = [];
  private        $rootParentKey;
  private        $alias;
  private static $currentPageKey;
  
  public function __construct($alias = null)
  {
    $this->alias = $alias ?: Service::url()->getTemplatePath();
    $this->reloadTree();
  }
  
  public function reloadTree()
  {
    $this->tree = $this->loadStructure($this->alias);
    //проставляем поле "ключ" для каждого элемента
    foreach ($this->tree as $key => $item) {
      if (isset($item['parent_key']) && $item['parent_key'] === false) {
        $this->rootParentKey = $key;
      }
      if (isset($item['key']) && $key !== $item['key']) {
        unset($this->tree[$key]);
        $key              = $item['key'];
        $this->tree[$key] = $item;
      } else {
        $this->tree[$key]['key'] = $key;
      }
    }
    
  }
  
  //полное дерево
  public function getTree()
  {
    return $this->tree;
  }
  
  /** Загружает структуру устройства поверх структуры родительского интерфейса. */
  public function loadStructure($alias)
  {
    $tree = [];
    foreach (Service::templates()->getMergeOrder($alias) as $device) {
      foreach (Service::locator()->search('structure\structure.' . $device) as $path) {
        $structure = Service::autoloader()->loadFile($path, 'require', false);
        if ($structure && is_array($structure)) {
          $tree[] = $structure;
        }
      }
    }
    
    return !empty($tree) ? array_replace_recursive(...$tree) : [];
  }
  
  //массив данных о страницах по массиву ключей
  public function getPageDataByKeys($keys_needle)
  {
    foreach ($this->tree as $key => $data) {
      if (in_array($key, $keys_needle)) {
        $out[$key] = $data;
      }
    }
    
    return $out;
  }
  
  //даннеы страницы по ключу
  public function getPageDataByKey($page_key)
  {
    //в перспективе надо всю эту хуйню брать из DB
    //передает содержимое блоков, показывать страницу в карте или нет (типа странице с ошибкой и.п.п.)
    //$pages['index']['visible'] = 0;
    return $this->tree[$page_key];
  }
  
  //ссылка страницы по ключу
  public function getPageHrefByKey($page_key)
  {
    return $this->tree[$page_key]['href'];
  }
  
  //структура по дочернему ключу
  public function getStructureByParentKey($parent_key, $sort = null)
  {
    $parent_key = $parent_key ?? $this->rootParentKey;
    
    /** @var UsersAuthController $auth */
    $auth         = Service::auth();
    $out          = [];
    $content_menu = [];
    foreach ($this->tree as $key => $data) {
      if (isset($data['parent_key']) && $data['parent_key'] == $parent_key
        && ((isset($data['visible']) && $data['visible'] !== false) || (!isset($data['visible']) && true))) {
        if (!isset($data['access']) || $auth->checkRights($data['access'])) {
          if (isset($data['content_menu']) && $data['content_menu']) {
            $content_menu[$key] = $data[$sort] ?? $key;
          }
          $out[$key] = $data;
        }
      }
    }
    if ($sort && isset(current($out)[$sort])) {
      uasort($out, static fn($a, $b) => $a[$sort] <=> $b[$sort]);
    }
    if (!empty($content_menu) && count($content_menu) > 1) {
      uasort($content_menu, static fn($a, $b) => $b <=> $a);
      $active = false;
      foreach (array_keys($content_menu) as $key) {
        if (!$active && UrlHelper::checkCurrentUrl($out[$key]['href'])) {
          $out[$key]['active'] = true;
          $active              = true;
        } else {
          $out[$key]['active'] = false;
        }
      }
    }
    
    return $out;
  }
  
  //является ли страница [АРГ1] предком страницы [АРГ2]?
  public function pageIsParent($child_key, $parent_key)
  {
    //он сам
    if ($parent_key == $child_key) {
      return true;
    }
    //предок 1-го уровня
    if ($this->tree[$child_key]['parent_key'] == $parent_key) {
      return true;
    }
    
    //увы
    return false;
  }
  
  //получить данные страницу "по умолчанию"
  function getDefaultPageData($alias = null)
  {
    foreach ($this->tree as $key => $data) {
      if (isset ($data['default']) && $data['default'] === true) {
        $data['key'] = $key;
        
        return $alias ? $data[$alias] : $data;
      }
    }
    
    return null;
  }
  
  //получить массив элементов "выше"
  public function getParentsTreeByPageKey($parent_key)
  {
    $out[] = $this->tree[$parent_key];
    if ($this->tree[$parent_key]['parent_key'] != false) {
      $out = array_merge($out, $this->getParentsTreeByPageKey($this->tree[$parent_key]['parent_key']));
    }
    
    return $out;
  }
  
  public function isPageKey($key)
  {
    return isset($this->tree[$key]);
  }
  
  public function getTypes()
  {
    // todo убрать прямой запрос к базе данных
    $q    = 'SELECT * FROM ' . Query::tableName('areas_types');
    $temp = Query::sqlQuery($q);
    foreach ($temp as $row) {
      if ($row['active'] == 1) {
        $data[] = $row;
      }
    }
    
    return $data;
  }
  
  public function getStructureTabsForPageKey($pageKey): array
  {
    $data = [];
    foreach ($this->getStructureByParentKey($this->getRootParent($pageKey), 'sort') as $item) {
      if (isset($item['tab'])) {
        $data[$item['key']] = $item;
      }
    }

    return $data;
  }
  
  public function getStructureContentMenu($pageKey, $content_key = 0)
  {
    $data = [];
    foreach ($this->getStructureByParentKey($pageKey, 'sort') as $item) {
      if (isset($item['content_menu'])) {
        $data[$item['content_key'] ?? $content_key][$item['key']] = $item;
      }
    }
    
    return $data;
  }
  
  public function getRootParent($pageKey)
  {
    $parentKey = $this->getPageDataByKey($pageKey)['parent_key'];
    
    return $parentKey == $this->rootParentKey ? $pageKey : $this->getRootParent($parentKey);
  }
  
  /**
   * @return mixed
   */
  public function getCurrentPageKey()
  {
    return self::$currentPageKey;
  }
  
  /**
   * @param mixed $currentPageKey
   */
  public function setCurrentPageKey($currentPageKey): void
  {
    self::$currentPageKey = $currentPageKey;
  }
  
  public function getPageHrefForCurrentPageKey(): string
  {
    return $this->getPageHrefByKey($this->getCurrentPageKey());
  }
  
  public function getAllKeysByPattern($pattern): array
  {
    $out = [];
    foreach (array_keys($this->tree) as $key) {
      if (str_starts_with($key, $pattern)) {
        $out[] = $key;
      }
    }
    
    return $out;
  }
  
  public function getMenu()
  {
    $out = [];
    foreach ($this->getTree() as $item) {
      if (isset($item['menu']) && $item['menu']) {
        $out[$item['sort']] = $item['key'];
      }
    }
    ksort($out);
    
    return array_keys(array_flip($out));
  }
}
