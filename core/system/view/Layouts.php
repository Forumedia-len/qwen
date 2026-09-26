<?php

namespace AC\core\system\view;

// todo Разобраться  с подключение файлов !!!

use AC\app\locators\Service;
use AC\core\system\App;


/** Рендеринг повторно используемых элементов интерфейса. */
class Layouts
{
  private static $namePath = 'layouts';

  /** Рендерит элемент с учётом родительского интерфейса устройства. */
  public static function render($layoutName, $params = array(), $device = false, $type_file = 'php')
  {
    $device = $device ?: Service::url()->getTemplatePath();
    $file = Service::templates()->findFile(static fn(string $template): array => [
      self::getLocalPath($template) . $layoutName,
      'core/' . self::$namePath . '/' . $template . '/' . $layoutName,
    ], $device, $type_file);
    ob_start();
    ob_implicit_flush(false);
    extract($params, EXTR_OVERWRITE);
    require $file;

    return trim(ob_get_clean());
  }

  private static function getLocalPath($device)
  {
    $baseUrl = App::getBaseTemplate($device);

    return ltrim($baseUrl .  self::$namePath . '/', '/');
  }

  public function svg($layoutName, $params = [], $device = 'common', $type_file = 'php')
  {
    return self::render('svg/' . $layoutName, $params, $device, $type_file);
  }

}
