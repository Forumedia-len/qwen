<?php

use AC\core\system\helpers\AssetHelper;

if (isset($css) && is_array($css)) {
  foreach ($css as $fileNameCss) {
    $href = AssetHelper::cssUrl($fileNameCss, $assetOrigin ?? null);
    ?>
    <link href="<?=$href ?>" rel="stylesheet" type="text/css"/>
  <?php }
}

if (isset($cssCode) && is_array($cssCode)) {
  foreach ($cssCode as $code) {
    echo '<style>' . $code. '</style>' . "\n";
  }
}
