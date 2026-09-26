<?php

namespace AC\core\system\exceptions;

class InvalidConfigException extends BaseException
{
  /**
   * @return string the user-friendly name of this exception
   */
  public function getName()
  {
    return 'Invalid Configuration';
  }

}