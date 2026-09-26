<?php

namespace AC\core\system\actions;

interface DeviceActionInterface
{

  public function before();

  public function after($variable);

}