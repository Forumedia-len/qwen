<?php

namespace AC\core\system\object\entity\format;

use AC\core\system\service\Services;

class Json extends BaseFormat
{
  public function get(array $params = [])
  {
    return Services::cast('json')::set(parent::get());
  }

  public function set($value, array $params = []): BaseFormat
  {
    return parent::set(Services::cast('json')::get($value, ['array']));
  }

//  public function getItem($key)
//  {
//    $preferences = $this->getPreferences($id);
//
//    return $preferences[$key] ?? null;
//  }
//
//  public static function setItem($key, $value)
//  {
//    $preferences       = $this->getPreferences($id);
//    $preferences[$key] = $value;
//
//    return $this->setPreferences($id, $preferences);
//  }
//
//  public static function delItem($key)
//  {
//    $preferences = $this->getPreferences($id);
//    if (isset($preferences[$key])) {
//      unset($preferences[$key]);
//    }
//
//    return $this->setPreferences($id, $preferences);
//  }
}