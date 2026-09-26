<?php
$structure       = Service::structure();
?>
<!DOCTYPE html>
<html lang="<?= config('lang')->getCurrentLang() ?>">
<head>
  <?php if (defined('NOINDEX_SITE') && NOINDEX_SITE) { ?>
    <meta name="robots" content="noindex, indexifembedded">
  <?php } ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <title><?= config('app')->getProjectTitle() ?><?= (isset($_page['title']) ? ' - ' . $_page['title'] : '') ?></title>
  <base href="<?= site_url() ?>">
  <?php if(isset($_page['head']) && !empty($_page['head'])) {
    foreach ($_page['head'] as $head) {
      echo $head;
    }
  }
  ?>
  <?php if (\Service::autoloader()->getPathFile('favicon.ico', 'ico')) { ?>
    <link rel="icon" type="image/ico" href="<?= base_url('favicon.ico') ?>">
    <link rel="shortcut icon" href="<?= base_url('favicon.ico') ?>">
  <?php } ?>

  <?= view()->renderer(paths()->getTplDir('_style.php'), get_defined_vars())?>
  <script type="text/javascript" src="<?= cdn_url(paths()->getAssetsDir('js/jquery.min.js', 'common')) ?>"></script>
  <?= view()->renderer(paths()->getTplDir('_base_script.php', 'common'), get_defined_vars())?>
</head>
<body class="body-bg">
<?= view()->renderer(paths()->getTplDir('_auth.php'), get_defined_vars())?>
<?= view()->renderer(paths()->getTplDir('_header.php'), get_defined_vars())?>
<div class="container middle">
  <div class="row">
    <div class="main">
      <?php if(!isset($_page['useH1']) || $_page['useH1']) {?>
        <div class="title-block">
            <h1 class="bold"><?= (isset($_page['h1']) && !empty($_page['h1']) ? $_page['h1'] : $_page['title']) ?></h1>
          <?= (isset($_page['title_block']) ? $_page['title_block'] : '')?>
        </div>
      <?php } ?>
      <div class="content-block <?= (isset($_page['class']) ? $_page['class'] : '')?>">
        <?= $_page['content'][1]; ?>
      </div>
    </div>
    <!-- Сайдбар -->

    <?= view()->renderer(paths()->getTplDir('_sidebar.php'), get_defined_vars())?>
  </div>
</div>

<!-- Футер -->
<?= view()->renderer(paths()->getTplDir('_footer.php'), get_defined_vars())?>
<?= view()->renderer(paths()->getTplDir('_cookie.php'), get_defined_vars())?>
<?= view()->renderer(paths()->getTplDir('_script.php'), get_defined_vars())?>
</body>
</html>
