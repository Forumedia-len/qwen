<?php
/**
 * @var array $_page
 */

?>
<!doctype html>
<html lang="<?= config('lang')->getCurrentLang() ?>">
<head>
  <?php if (defined('NOINDEX_SITE') && NOINDEX_SITE) { ?>
    <meta name="robots" content="noindex, indexifembedded">
  <?php } ?>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title><?= config('app')->getProjectTitle() ?><?= (isset($_page['title']) ? ' - ' . $_page['title'] : '') ?></title>
  <link href="<?= base_url(paths()->getAssetsDir('css/close_default.min.css')) ?>" rel="stylesheet" type="text/css"/>
  <link href="<?= base_url(paths()->getAssetsDir('css/styles.css')) ?>" rel="stylesheet" type="text/css"/>
</head>

<body>
<table width="100%" id="main" cellpadding="0" cellspacing="0">
  <tr>
    <td class="top_bottom_line">
      <h1><?= lang('welcome') ?>!</h1>
      <h2><?= lang('with_the_touch_screen_booking_system_from') ?></h2>
    </td>
  </tr>
  <tr>
    <td style="height:60%; text-align:center; vertical-align:middle;max-width: 100vw;" class="select-content">
      <table id="selector" border="0" cellspacing="0" cellpadding="0">
        <?php foreach ($_page['content'] as $content) { ?>
          <tr>
            <?= $content ?>
          </tr>
        <?php } ?>
      </table>
    </td>
  </tr>
  <tr>
    <td class="top_bottom_line2">
      <?= Service::view()->renderer(paths()->getTplDir('_banners')) ?>
    </td>
  </tr>
</table>
</body>
</html>
