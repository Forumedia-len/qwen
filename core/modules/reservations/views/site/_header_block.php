<?php
/***
 * @var View   $this
 * @var array  $variables - массив переданых с переменных
 * @var string $title_h2
 * @var string $title_h3
 */

use AC\core\system\view\View;

?>
  <h2><?= $title_h2 ?></h2>
  <h3 class="area-title"><?= $title_h3 ?></h3>
<?= $this->render('_time_block', compact($variables)) ?>