<?php
use AC\core\system\helpers\AssetHelper;

// Общие стили поставляются с CDN; локальный custom.css содержит только дополнения площадки.
$widgetStyleVersion = '20260922-3';
$widgetCustomCss = AssetHelper::localCustomUrl('css');
?>
<link href="<?= cdn_url(paths()->getAssetsDir('css/default.min.css')) ?>?v=<?= $widgetStyleVersion ?>" rel="stylesheet" type="text/css"/>
<link href="<?= cdn_url(paths()->getAssetsDir('fonts/flaticon/flaticon.css', 'common')) ?>" rel="stylesheet" type="text/css"/>
<link href="<?= cdn_url(paths()->getAssetsDir('fonts/fontawesome/all.min.css', 'common')) ?>" rel="stylesheet" type="text/css"/>
<link href="<?= cdn_url(paths()->getAssetsDir('css/styles.css')) ?>" rel="stylesheet" type="text/css"/>

<?= view()->renderer(paths()->getTplDir('_style', 'common'), ['css' => $_page['css'] ?? null, 'assetOrigin' => 'cdn']) ?>
<link href="<?= cdn_url(paths()->getAssetsDir('css/interface.min.css')) ?>?v=<?= $widgetStyleVersion ?>" rel="stylesheet" type="text/css"/>
<?php if ($widgetCustomCss !== null) { ?>
  <link href="<?= $widgetCustomCss ?>" rel="stylesheet" type="text/css"/>
<?php } ?>
