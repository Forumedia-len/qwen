<?php

namespace AC\core\system\object\entity\format;

interface FormatInterface
{
  /**
   * Get
   *
   * @param array $params Additional param
   *
   * @return mixed
   */
  public function get(array $params = []);

  /**
   * Set
   *
   * @param mixed $value  Data
   * @param array $params Additional param
   *
   * @return
   */
  public function set($value, array $params = []);
}