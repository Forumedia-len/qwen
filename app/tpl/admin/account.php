<!doctype html>
<html lang="<?= config('lang')->getCurrentLang() ?>">
<head>
  <?= view()->renderer(paths()->getTplDir('_head.php'), get_defined_vars()) ?>
  <link rel="stylesheet" type="text/css" media="screen" href="<?= base_url(paths()->getAssetsDir('css/buttons.css')) ?>">
  <link rel="stylesheet" type="text/css" media="screen" href="<?= base_url(paths()->getAssetsDir('css/account.css')) ?>">
  <link rel="stylesheet" type="text/css" media="print, handheld" href="<?= base_url(paths()->getAssetsDir('css/account_print.css')) ?>">
  <script src="<?= base_url(paths()->getAssetsDir('js/jquery.min.js', 'common')) ?>"></script>
  <title><?= config('app')->getProjectTitle() ?><?= (isset ($_page['title']) ? ' | ' . $_page['title'] : ''); ?></title>
  <?= view()->renderer(paths()->getTplDir('_base_script.php', 'common'), get_defined_vars())?>
  <?= view()->renderer(paths()->getTplDir('_style', 'common') , ['css' => (isset($_page['css']) ? $_page['css'] : null)]) ?>
  <?= view()->renderer(paths()->getTplDir('_script.php', 'common'), ['js' => (isset($_page['js']) ? $_page['js'] : null)]) ?>
</head>
<body>
<?= $_page['content'][0]; ?>
</body>
</html>