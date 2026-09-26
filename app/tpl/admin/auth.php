<?php
/**
 * @var array $_page
 */
?>
<!doctype html>
<html lang="<?= config('lang')->getCurrentLang() ?>">
<head>
  <?= view()->renderer(paths()->getTplDir('_head.php'), get_defined_vars()) ?>
  <link href="<?= base_url(paths()->getAssetsDir('css/bootstrap.min.css')) ?>" rel="stylesheet" type="text/css">
  <link href="<?= base_url(paths()->getAssetsDir('css/default.min.css')) ?>" rel="stylesheet" type="text/css">
  <title><?= config('app')->getProjectTitle() ?><?= (isset ($_page['title']) ? ' | ' . $_page['title'] : ''); ?></title>
  <script src="<?= base_url(paths()->getAssetsDir('js/jquery.min.js', 'common')) ?>"></script>
</head>
<body class="login-bg">
<?= $_page['content'][1] ?>
<script src="<?= base_url(paths()->getAssetsDir('js/jquery.min.js', 'common')) ?>"></script>
<?= view()->renderer(paths()->getTplDir('_base_script.php', 'common'), get_defined_vars())?>
<script src="<?= base_url(paths()->getAssetsDir('js/popper.min.js')) ?>"></script>
<script src="<?= base_url(paths()->getAssetsDir('js/bootstrap.min.js')) ?>"></script>
</body>
</html>