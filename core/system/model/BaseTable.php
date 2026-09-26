<?php

namespace AC\core\system\model;

interface BaseTable
{
  public function insert(&$error_code = false);

  public function beforeInsert();

  public function checkInsert();

  public function afterInsert();

  public function load($data);

  public function save();

}