<?php

namespace AC\app\helpers;

use AC\core\system\helpers\ColorsHelper;
use AC\core\system\helpers\LayoutHelper as LayoutHelperSystem;
use AC\core\system\helpers\StringHelper;
use Service;

class LayoutHelper extends LayoutHelperSystem
{
  /** Возвращает строковое меню для списка площадок
   *  по типу спорту.
   *
   * @param array $areas
   * @param int|null $area_id
   * @param int $mode
   * @param string $defaultUrl
   * @param string $indentBySport
   * @return string
   */
  public static function areasByRowMenu(
    array $areas = [],
    ?int $area_id = null,
    int $mode = 1,
    string $defaultUrl = '',
    string $indentBySport = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - '
  ): string {
    $out           = '';
    $last_type_id  = null;
    $last_sport_id = null;
    $sportItems    = [];
    /** @var array{
     *   type_id: int,
     *   sport_id: int,
     *   area_id: int,
     *   type_title: string,
     *   sport_title: string,
     *   area_title: string,
     *   cnt: int} $area
     */
    foreach ($areas as $area) {
      if ($last_type_id != $area['type_id']) {
        if (!empty($sportItems['items'])) {
          $out        .= useLayout()->render('menu/list_links_by_row', ['row' => $sportItems]);
          $sportItems = [];
        }
        $last_sport_id = null;
        $out           .= useLayout()->render('menu/list_links_by_row', ['row' => ['title' => $area['type_title']]]);
      }
      if ($last_sport_id != $area['sport_id']) {
        if (!empty($sportItems['items'])) {
          $out .= useLayout()->render('menu/list_links_by_row', ['row' => $sportItems]);
        }
        $sportItems['title'] = $indentBySport . $area['sport_title'] . ': ';
        $sportItems['items'] = [];
      }
      
      $sportItems['items'][] = [
        'value'  => $area['area_title'] . " (" . $area['cnt'] . ")",
        'link'   => $defaultUrl . '/areaId/' . $area['area_id'],
        'active' => $mode == 0 && $area['area_id'] == $area_id
      ];
      $last_type_id          = $area['type_id'];
      $last_sport_id         = $area['sport_id'];
    }
    
    if (!empty($sportItems['items'])) {
      $out .= useLayout()->render('menu/list_links_by_row', ['row' => $sportItems]);
    }
    return $out;
  }
  
  //ссылка на страницу информации клиента
  public static function renderClientInfoHref($name, $surname, $client_id): string
  {
    return '<a href="' . Service::structure()->getPageHrefByKey('clients_personal_data') . '?client_id=' . $client_id . '" onclick="return showUserInfo (this)">' . StringHelper::cropStr($surname . ' ' . $name) . '</a>';
  }
  
  public static function renderAreaTypeSquare($color): string
  {
    return '<span style="text-align:center;background:#' . $color . ';width:8px;height:8px;display:inline-block"></span>';
  }
  
  public static function renderSquareByTypeSport(int $type, int $sport): string
  {
    return self::renderAreaTypeSquare(ColorsHelper::hexByTypeSport($type, $sport));
  }
  
  public static function attributeElementHtml($name, $value): string
  {
    return !empty($name) && (!empty($value) || $value === 0 || $value === '0') ? $name . '="' . $value . '"' : '';
  }
  
  public static function renderListClientsBySelect($name = 'client_id', $clientsData = [], $current = null): string
  {
    $clients = [];
    foreach ($clientsData as $client_data) {
      $tmp = $client_data['surname'] . ' ' . $client_data['name'];
      if (strlen($tmp) > 75) {
        $tmp = substr($tmp, 0, 75) . '...';
      }
      $clients[$client_data['client_id']] = $tmp;
    }
    
    return useLayout()->render(
      'select',
      [
        'name'     => $name,
        'values'   => $clients,
        'current'  => $current ?: 0,
        'multiple' => false,
        'id'       => null
      ],
      'common'
    );
  }
}