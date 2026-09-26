<?php
use AC\core\modules\text\engines\TextEngine;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <?php if (defined('NOINDEX_SITE') && NOINDEX_SITE) { ?>
    <meta name="robots" content="noindex, indexifembedded">
  <?php } ?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?= lang('declaration_on_data_protection_user_agreement', 'user_info') ?></title>
<link href="<?=base_url(paths()->getAssetsDir('css/default.min.css'))?>" rel="stylesheet" type="text/css" />
</head>
<body style="margin:20px;">
<div class="user-info">
  <?php
  /** @var TextEngine $txt */
  $txt = getEngine('text', false);
  $txt?->getContent($row, 'rights');
  if(!empty($row['content']))
  {
    echo $row['content'];
  }
  ?>
</div>
</body>
</html>
