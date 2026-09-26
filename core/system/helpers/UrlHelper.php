<?php

namespace AC\core\system\helpers;



use Service;
use const BASE_HREF;

/**
 * @todo  переделать на новый лад испорользуя Url
 */

class UrlHelper
{
  /** Возвращает адрес страницы текущего устройства с параметрами запроса. */
  public static function to($action = null, $options = array())
  {
    switch (\Service::url()->getTemplatePath()) {
      case 'admin' :
        $href = 'at/';
        break;
      case 'touch' :
        $href = 'touchscreen/';
        break;
      case 'widget' :
        $href = 'widget/';
        break;
      case 'site' :
      default :
        $href = '';
        break;
    }
    $params = self::renderOptionsAsString($options);

    return BASE_HREF . $href . (!empty($action) ? $action . '.php' : '') . (!empty($params) ? '?' . $params : '');
  }

  public static function renderOptionsAsString($options)
  {
    $_options = array();
    foreach ($options as $key => $value) {
      $_options[] = $key . '=' . $value;
    }

    return !empty($_options) ? implode('&', $_options) : '';
  }


  /**
   * Проверяет, начинается ли текущий путь с указанного URL.
   *
   * @param string $url
   */
  public static function checkCurrentUrl($url): bool
  {
    $path = explode('?', (string)$url, 2)[0];

    return str_starts_with(Service::url()->getPath(false), $path);
  }
}
