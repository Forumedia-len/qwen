<?php

namespace AC\core\system\helpers;

class IconHelper
{
  static public function statsWebIoIcon($aliasStats, $active = false, $style = '')
  {
    return '<span class="state-icon state-icon-' . $aliasStats . ' ' . ($active ? 'yellow' : '') . '" style="' . $style . '"> </span>';
  }
}