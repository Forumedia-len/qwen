<?php

use AC\core\modules\text\engines\TextEngine;

$_page['key'] = 'impressum';
$_page['title'] = lang('title', 'contact');
/** @var TextEngine $txt */
$txt = getEngine('text', false);

ob_start();
?>
  <div class="content">
    <?php
    if ($txt->getContent($row, 'address')) {
      echo $row['content'];
    }
    ?>
  </div>
<?php
$_page['content'][1] = ob_get_contents();
ob_end_clean();