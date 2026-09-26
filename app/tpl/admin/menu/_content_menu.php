<?php
/**
 * @var string $key
 * @var array $_page
 * @var Structure $structure
*/

use AC\core\system\structure\Structure;


if(!empty($_page['content_menu']) && isset($_page['content_menu'][$key])) {  ?>
  <div class="list-type list-type-content">
    <?php foreach ($_page['content_menu'][$key] as $key_content_menu => $content_menu) { ?>
      <div class="item-type <?= ($content_menu['active'] ? 'active' : '')?>" data-content-menu="<?= $key_content_menu?>">
        <a href="<?= $content_menu['href'] ?>"><?= $content_menu['title'] ?></a>
      </div>
    <?php } ?>
  </div>
<?php }
