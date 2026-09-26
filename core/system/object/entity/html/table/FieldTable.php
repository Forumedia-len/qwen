<?php

namespace AC\core\system\object\entity\html\table;

use AC\core\system\object\entity\html\ElementHtml;

class FieldTable extends ElementHtml
{
  public int     $row    = 1;
  public int     $col    = 1;
  public string  $title  = '';
  public ?string $parent = null;
}