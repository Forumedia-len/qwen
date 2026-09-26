<!doctype html>
<html lang="<?= config('lang')->getCurrentLang() ?>">
<head>
  <?= view()->renderer(paths()->getTplDir('_head.php'), get_defined_vars()) ?>
  <link href="<?= cdn_url(paths()->getAssetsDir('css/active_time.css')) ?>" rel="stylesheet" type="text/css">
  <title><?= config('app')->getProjectTitle() ?><?= (isset ($_page['title']) ? ' | ' . $_page['title'] : '') ?></title>
</head>
<body leftmargin="0" topmargin="0" marginheight="0" marginwidth="0">
<?= $_page['content'][0] ?>
</body>
</html>