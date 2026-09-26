<!DOCTYPE html>
<html lang="<?= config('lang')->getCurrentLang() ?>">
<head>
  <?php if (defined('NOINDEX_SITE') && NOINDEX_SITE) { ?>
    <meta name="robots" content="noindex, indexifembedded">
  <?php } ?>
  <title><?= config('app')->getProjectTitle() ?><?= (isset($_page['title']) ? ' - ' . $_page['title'] : '') ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <?php if (isset($show) && $show == 'ok'): ?>
    <meta http-equiv="refresh" content="10;url=<?= Service::structure()->getPageHrefByKey('index') ?>">
  <?php endif; ?>
  <?= view()->renderer(paths()->getTplDir('_style'), get_defined_vars()) ?>
  <?= view()->renderer(paths()->getTplDir('_script'), get_defined_vars()) ?>

</head>
<body>
<?php
if (isset($_page['content'])) {
  echo $_page['content'][1];
}
?>
<?= view()->renderer(paths()->getTplDir('_time_reload'), get_defined_vars()) ?>
</body>
</html>
