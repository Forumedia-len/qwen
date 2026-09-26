<?php

namespace AC\core\system\exceptions\module;

class ModuleException extends \AC\core\system\exceptions\BaseException
{
  static public function ControllerNotFound()
  {
    return new static(lang('controllerNotFound', 'module'));
  }
}