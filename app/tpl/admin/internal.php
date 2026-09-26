<?php

/** @var array $_page */
$lvl0 = $_page['parents'][1];
$auth = Service::auth();
?>
<!doctype html>
<html lang="<?= config('lang')->getCurrentLang() ?>">
<head>
  <?php if (defined('NOINDEX_SITE') && NOINDEX_SITE) { ?>
    <meta name="robots" content="noindex, indexifembedded">
  <?php } ?>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <META HTTP-EQUIV="pragma" CONTENT="no-cache">
  <title><?= (isset ($_page['title']) ? $_page['title'] . ' | ' : ''); ?><?= config('app')->getProjectTitle() ?></title>
  <base href="<?= site_url() ?>">
  <link href="<?= cdn_url(paths()->getAssetsDir('css/default.min.css')) ?>" rel="stylesheet" type="text/css">
  <link href="<?= base_url(paths()->getAssetsDir('fonts/fontawesome/all.min.css', 'common')) ?>" rel="stylesheet" type="text/css">
<!--  <link href="--><?php //= base_url(paths()->getAssetsDir('css/select2.min.css', 'common')) ?><!--" rel="stylesheet" type="text/css">-->
  <?= view()->renderer(paths()->getTplDir('_style', 'common'), ['css' => (isset($_page['css']) ? $_page['css'] : null)]) ?>

  <script src="<?= cdn_url(paths()->getAssetsDir('js/jquery.min.js', 'common')) ?>"></script>
  <?= view()->renderer(paths()->getTplDir('_base_script.php', 'common'), get_defined_vars())?>
  <script src="<?= base_url(paths()->getAssetsDir('js/popper.min.js')) ?>"></script>
  <script src="<?= cdn_url(paths()->getAssetsDir('js/active_time.js')) ?>"></script>
<!--  <script src="--><?php //= base_url(paths()->getAssetsDir('js/select2.min.js', 'common')) ?><!--"></script>-->

  <?= view()->renderer(paths()->getTplDir('_script.php', 'common'), ['js' => (isset($_page['js']) ? $_page['js'] : null)]) ?>

</head>
<body class="internal-bg <?= 'bg-' . BACKGROUND_ADMIN ?>">
<?= view()->renderer(paths()->getTplDir('menu/_main.php'), get_defined_vars()) ?>
<div class="main-container">
  <div class="main-container-header">
    <table width="100%" border="0" cellspacing="5" cellpadding="5">
      <tr>
        <td nowrap class="page_title"><?= $lvl0['title']; ?></td>
        <td width="100%" align="right">
          <table>
            <tr>

              <?php
              if ($user_data = $auth->getUserData()) {
                if (isset($user_data['image'])) {
                  echo '<td><img src="' . base_url($user_data['image']) . '"/></td>';
                }
                echo '<td nowrap>' . $user_data['name'] . ' [' . $user_data['code'] . ']</td>';
              }
              ?>
              <td><img src="<?= base_url(paths()->getAssetsDir('images/spacer.gif', 'admin')) ?>" width="40" height="1"></td>
              <td nowrap><a href="<?= base_url() ?>" target="_blank" class="preview"><?= lang('to_the_website', 'auth')?></a></td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td colspan="5">
          <?= empty($_page['http_error']) ? view()->renderer(paths()->getTplDir('menu/_tabs.php'), get_defined_vars()) : '' ?>
        </td>
      </tr>
      <tr>
        <td colspan="3">
          <?= empty($_page['http_error']) ? view()->renderer(paths()->getTplDir('menu/_local.php'), get_defined_vars()) : '' ?>
        </td>
      </tr>
    </table>
  </div>

  <?php
  foreach ($_page['content'] as $key => $content) { ?>
    <?php
    if ($content) {
      if(!isset($_page['content_menu'])) {
        $_page['content_menu'] = Service::structure()->getStructureContentMenu($_page['key']);
      }
      ?>
      <?= view()->renderer(paths()->getTplDir('menu/_content_menu.php'), get_defined_vars()) ?>
      <div class="content-block">
        <?= $content ?>
      </div>
      <?php
    } ?>
    <?php
  } ?>


</div>
</body>
</html>
