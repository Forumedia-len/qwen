<!doctype html>
<html lang="<?= config('lang')->getCurrentLang() ?>">
<head>
  <?= view()->renderer(paths()->getTplDir('_head.php'), get_defined_vars()) ?>
  <title><?= config('app')->getProjectTitle() ?><?= (isset ($_page['title']) ? ' | ' . $_page['title'] : ''); ?></title>
</head>
<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" bgcolor="#FFFFFF">
<table width="100%" cellspacing="10">
  <tr>
    <td>
      <?= $_page['content'][0] ?>
    </td>
  </tr>
</table>
</body>
</html>