<meta charset="UTF-8">
<?php if (defined('NOINDEX_SITE') && NOINDEX_SITE) { ?>
  <meta name="robots" content="noindex, indexifembedded">
<?php } ?>
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title><?= (isset ($_page['title']) ? $_page['title'] . ' | ' : ''). config('app')->getProjectTitle() ?></title>
<base href="<?= site_url() ?>">
