<?php
/**
 * @var Structure $structure
 * @var array $_page
 */

use AC\core\system\structure\Structure;

$parentKey  = isset($structure->getPageDataByKey($_page['key'])['tab']) ? $_page['key'] : ($_page['parent_key']
&& $structure->getRootParent($_page['key']) != $_page['key'] ? $_page['parent_key'] : $_page['parents'][1]['key']);
$parentData = $structure->getPageDataByKey($parentKey);
$add_menu   = $structure->getStructureByParentKey($parentKey, 'sort');

if (!empty($_page['reservations_menu']) && count($_page['reservations_menu']) == 1) {
  $_page['reservations_menu'][key($_page['reservations_menu'])]['title'] = lang('schedule', 'structure');
}

$bar = null;
if (isset($add_menu['reservations_bar'])) {
  $bar = $add_menu['reservations_bar'];
  unset($add_menu['reservations_bar']);
}

if (isset($add_menu['reservations_areas']) && isset($_page['reservations_menu'])) {
  unset($add_menu['reservations_areas']);
  $add_menu = array_merge($add_menu, $_page['reservations_menu']);
}
if (isset($bar)) {
  $add_menu['reservations_bar'] = $bar;
}

if (is_array($add_menu)) {
  echo "<table class='other-menu'><tr>\n";
  $j = 0;
  foreach ($add_menu as $add_item) {
    if (!isset($add_item['visible'])) {
      if ($j == 0) {
        $parent = $structure->getPageDataByKey($add_item['parent_key']);
      }
      if ($structure->pageIsParent($_page['key'], $add_item['key']) || (isset($_page['key_r']) && $_page['key_r'] == $add_item['key'])) {
        $title = '<span>' . $add_item['title'] . '</span>';
      } else {
        $title = '<a href="' . $add_item['href'] . '" ' . (isset($add_item['class']) ? 'class="' . $add_item['class'] . '"'
            : '') . (isset($add_item['target']) ? ' target="' . $add_item['target'] . '"' : '') . '>' . $add_item['title'] . '</a>';
      }
      if (count($add_menu) > 8 || (isset($parentData['submenu']['rows']) && $parentData['submenu']['rows'] > 2)) {
        $tmp_title[] = $title;
        if (count($tmp_title) == ($parentData['submenu']['rows'] ?? 2) || ($j + 1) == count($add_menu) || ($j == 0 && (!isset($parent['j0']) || $parent['j0']))) {
          echo "<td nowrap>" . join('', $tmp_title) . "</td>\n";
          unset($tmp_title);
        } else {
          $j++;
          continue;
        }
      } else {
        echo "<td nowrap>" . $title . "</td>\n";
      }
      
      $j++;
      if ($j < count($add_menu)) {
        echo "<td>  </td>\n";
      }
    }
  }
  echo "</tr></table>\n";
}

