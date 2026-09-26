<?php

use AC\core\modules\text\engines\TextEngine;

$_page['key']   = 'info';
$_page['title'] = lang('title', 'info');

ob_start();
?>
  <div class="aroundBox">
    <div class="header">

    </div>
    <div class="content">
      <?php
      /** @var TextEngine $txt */
      $txt = getEngine('text', false);
      if ($txt?->getContent($row, 'info')) {
        echo $row['content'];
      }
      ?>
    </div>
    <div class="footer">

    </div>
  </div>
<?php
$_page['content'][1] = ob_get_contents();
ob_end_clean();