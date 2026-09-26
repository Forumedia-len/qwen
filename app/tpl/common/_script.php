<?php

use AC\core\system\helpers\AssetHelper;

if (isset($js) && is_array($js)) {
  foreach ($js as $fileNameJs) {
    $src = AssetHelper::jsUrl($fileNameJs, $assetOrigin ?? null);
    echo '<script type="text/javascript" src="' . $src . '" ></script>' . "\n";
  }
}
if (isset($this->jsCode) && is_array($this->jsCode)) {
  foreach ($this->jsCode as $code) {
    echo '<script>' . $code. '</script>' . "\n";
  }
}
