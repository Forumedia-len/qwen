<?php

namespace AC\core\system\model;


abstract class Table
{
  public $table;

  public function __construct()
  {
    $this->setTable();
  }

  /**
   * @return mixed
   */
  abstract function setTable();
}