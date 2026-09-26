<?php
/***
 * @var View   $this
 * @var array  $variables - массив переданых с переменных
 * @var string $title_h2
 * @var string $title_h3
 */

use AC\core\system\view\View;

?>
  <strong style="font-size: 37px">
        <span class="green">
          <?= $title_h2 ?>, <?= $title_h3 ?>
</span>
  </strong><br/>
<?= $this->render('_time_block', compact($variables)) ?>