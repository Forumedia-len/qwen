<?php
/**
 * @var Structure $structure
 * @var array     $_page
 */

use AC\core\system\structure\Structure;
$tabsMenu = $structure->getStructureTabsForPageKey($_page['key']);
if (!empty($tabsMenu)) {
  ?>
  <div class="list-type">
    <?php foreach ($tabsMenu as $item) {
      $active = $structure->pageIsParent($_page['key'], $item['key']);
      ?>
      <div class="item-type <?= ($active ? 'active' : '') ?>">
        <a href="<?= $item['href'] ?>"><?= $item['title'] ?></a>
      </div>
    <?php } ?>
  </div>
<?php } ?>
