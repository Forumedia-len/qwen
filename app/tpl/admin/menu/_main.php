<?php
/**
 * @var Structure           $structure
 * @var array               $_page
 */

use AC\app\locators\Service;
use AC\core\system\structure\Structure;

?>
<div class="header">
  <div class="header-container">
    <?php
    foreach ($structure->getStructureByParentKey(null, 'sort') as $item_data) {
      /*--- проверка прав пользователя на доступ к этой странице --*/
      if (Service::auth()->CheckRights((isset($item_data['access']) ? (int)$item_data['access'] : 1))) {
        $active = $structure->getRootParent($_page['key']) == $item_data['key'];
        $menuTitle = $item_data['menu_title'] ?? $item_data['title'];
        ?>
        <div class="menu-item <?= (isset($item_data['icon']) ? 'ac-m-' . $item_data['icon'] : '') . ($active ? ' active' : '') ?>">
          <?php if (!$active) { ?>
          <a href="<?= $item_data['href'] ?>"
             title="<?= $item_data['title'] . (isset($item_data['notice']) ? ' - ' . $item_data['notice'] : '') ?>">
            <?php } ?>
            <?= ($active ? '<span>' : '') . $menuTitle . ($active ? '</span>' : '') ?>
            <?php if (!$active) { ?>
          </a>
        <?php } ?>
        </div>
        <?php
      }
    }
    ?>
  </div>
</div>
