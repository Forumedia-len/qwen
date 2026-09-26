<link href="<?= base_url(paths()->getAssetsDir('css/default.min.css')) ?>?v=20260917-1" rel="stylesheet" type="text/css"/>
<link href="<?= base_url(paths()->getAssetsDir('fonts/flaticon/flaticon.css', 'common')) ?>" rel="stylesheet" type="text/css"/>
<link href="<?= base_url(paths()->getAssetsDir('fonts/fontawesome/all.min.css', 'common')) ?>" rel="stylesheet" type="text/css"/>
<link href="<?= cdn_url(paths()->getAssetsDir('css/styles.css')) ?>" rel="stylesheet" type="text/css"/>

<?= view()->renderer(paths()->getTplDir('_style', 'common') , ['css' => (isset($_page['css']) ? $_page['css'] : null)]) ?>
