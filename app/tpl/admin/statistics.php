<!doctype html>
<html lang="<?= config('lang')->getCurrentLang() ?>">
<head>
  <?= view()->renderer(paths()->getTplDir('_head.php'), get_defined_vars()) ?>
  <link rel="stylesheet" type="text/css" media="screen" href="<?= cdn_url(paths()->getAssetsDir('css/statistics.css')) ?>">
  <link rel="stylesheet" type="text/css" media="print, handheld" href="<?= cdn_url(paths()->getAssetsDir('css/statistics_print.css')) ?>">
  <title><?= config('app')->getProjectTitle() ?><?= (isset ($_page['title']) ? ' | ' . $_page['title'] : ''); ?></title>
  <script src="<?= cdn_url(paths()->getAssetsDir('js/jquery.min.js', 'common')) ?>"></script>
  <?= view()->renderer(paths()->getTplDir('_base_script.php', 'common'), get_defined_vars()) ?>
</head>
<body>
<?= $_page['content'][0] ?? $_page['content'][1] ?>
</body>
</html>