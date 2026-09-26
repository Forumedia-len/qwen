<?php

namespace AC\core\system\helpers;

use AC\core\system\validators\Validator;
use Service;

class ValidatorHelper
{
  public static function getValidatorClassName($validateType): ?string
  {
    return Service::locator()->getPriorityClassNameForUsedModules('validators\\' . ucfirst($validateType) . 'Validator');
  }
  
  public static function getValidator($validateType, $attribute, $params = []): ?Validator
  {
    if ($className = self::getValidatorClassName($validateType)) {
      return useClass($className, true, $attribute, $validateType, $params);
    }
    return null;
  }
}