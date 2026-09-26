<?php

namespace AC\core\system\helpers;

use AC\app\locators\Service;
use AC\core\system\object\StdObject;

class ObjectHelper
{

  /**
   * @param $params
   * @param bool $isValues
   * @return StdObject
   */
  public static function createObject($params = null, bool $isValues = false): StdObject
  {
    $object = new StdObject();
    self::setParamsByObject($params, $isValues, $object);

    return $object;
  }

  /**
   * @param $params
   * @param bool $isValues
   * @param object $object
   * @return void
   */
  public static function setParamsByObject($params = null, bool $isValues = false, object &$object = new StdObject()): void
  {
    if (is_array($params) || is_object($params)) {
      if ($isValues) {
        $object->addPropertiesAndValues($params);
      } else {
        $object->addProperties($params);
      }
    }
  }

  /**
   * @param string $name
   * @param null $additionalName
   * @param mixed ...$arguments
   * @return object
   */
  public static function createEntity(string $name = 'entity', $additionalName = null, ...$arguments): object
  {
    $className = self::searchActualEntityClassName(str_contains($name, '\\') ? $name : ucfirst($name), $additionalName);
    return useClass($className, true, ...$arguments);
  }

  protected static function searchActualEntityClassName($name = 'entity', $additionalName = null): string
  {

    $name           = str_contains($name, '\\') ? $name : ucfirst($name);
    $additionalName = !empty($additionalName) ? ucfirst($additionalName) : '';

    $search         = array_unique(array_merge(
      Service::locator()->search(paths()->getEntityDir() . paths()->getTemplateDir() . $name . $additionalName),
      Service::locator()->search(paths()->getEntityDir() .  $name . $additionalName),
      Service::locator()->search(paths()->getEntityDir() . paths()->getTemplateDir() . $name),
      Service::locator()->search(paths()->getEntityDir() . $name),
      Service::locator()->search('entities\\' . paths()->getEntityDir() . $name . $additionalName),
      Service::locator()->search('entities\\' . paths()->getEntityDir() . $name),
      Service::locator()->search('object\\' . paths()->getEntityDir() . $name . $additionalName),
      Service::locator()->search('object\\' . paths()->getEntityDir() . $name),
    ));

    foreach ($search as $path) {
      return Service::locator()->getClassname($path);
    }

    return '';
  }
}