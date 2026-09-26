<!DOCTYPE html>
<html lang="<?= config('lang')->getCurrentLang() ?>">
<head>
  <?php if (defined('NOINDEX_SITE') && NOINDEX_SITE) { ?>
    <meta name="robots" content="noindex, indexifembedded">
  <?php } ?>
  <title><?= config('app')->getProjectTitle() ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <link href="<?= BASE_HREF ?>touchscreen/css/close_default.min.css" rel="stylesheet" type="text/css"/>
  <script type="text/javascript" src="<?= BASE_HREF ?>touchscreen/js/jquery.min.js"></script>
  <?= view()->renderer(paths()->getTplDir('_base_script.php', 'common'), get_defined_vars())?>
  <script type="text/javascript" src="<?= BASE_HREF ?>touchscreen/js/jquery.colorbox-min.js"></script>
  <script type="text/javascript" src="<?= BASE_HREF ?>assets/js/default.js"></script>
  <script type="text/javascript" src="<?= BASE_HREF ?>touchscreen/js/login.js"></script>
  <script type="text/javascript" src="<?= BASE_HREF ?>touchscreen/js/keyboard.js"></script>
</head>
<body>
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin:20px auto;">
  <tr>
    <?php require_once 'template/close/_menu.php' ?>
    <td id="section_right">
      <div id="loginForm">
        <?php
        if (isset($_page['content'][1])) {
          echo $_page['content'][1];
        }
        ?>
      </div>
    </td>
  </tr>
</table>
</body>
</html>
