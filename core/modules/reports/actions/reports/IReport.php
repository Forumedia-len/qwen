<?php

namespace AC\core\modules\reports\actions\reports;

interface IReport
{
  /**
   * @return string
   */
  public function form(): string;
  
  /**
   * @return array
   */
  public function getData(): array;
}