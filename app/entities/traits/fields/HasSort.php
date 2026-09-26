<?php

namespace AC\app\entities\traits\fields;

trait HasSort
{
  public int $sort = 0;
  
  public function getSort(): int { return $this->sort; }
  
  public function setSort(int $sort): void { $this->sort = $sort; }
}