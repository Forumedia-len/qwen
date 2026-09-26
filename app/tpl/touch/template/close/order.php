<!DOCTYPE html>
<html lang="<?= config('lang')->getCurrentLang() ?>">
<head>
  <?php if (defined('NOINDEX_SITE') && NOINDEX_SITE) { ?>
    <meta name="robots" content="noindex, indexifembedded">
  <?php } ?>
  <title><?= config('app')->getProjectTitle() ?><?= (isset($_page['title']) ? ' - ' . $_page['title'] : '') ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <?= view()->renderer(paths()->getTplDir('_style'), get_defined_vars()) ?>
  <?= view()->renderer(paths()->getTplDir('_script'), get_defined_vars()) ?>
  <script type="text/javascript" src="<?= base_url(paths()->getAssetsDir('js/reservations_form.js')) ?>"></script>
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
