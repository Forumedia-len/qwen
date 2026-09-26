<?php

namespace AC\core\system\actions\admin\show_table;

class MarkExecution
{
  public function markExecution($action = null)
  {
    if (method_exists($this, $action) && in_array($action, $this->getActionsWhereDontCheckAccountIds())) {
      return $this->$action();
    }

    return '';
  }

 protected function getActionsWhereDontCheckAccountIds(): array
  {
    return [];
  }
}