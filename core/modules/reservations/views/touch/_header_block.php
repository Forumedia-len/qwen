<?php
/***
 * @var View   $this
 * @var array  $variables - массив переданых с переменных
 * @var string $title_h2
 * @var string $title_h3
 */

use AC\core\system\view\View;

?>
<div class="resh1">
  <?= $title_h2 ?>, <?= $title_h3 ?>
  <?= $this->render('_time_block', compact($variables)) ?>
</div>
