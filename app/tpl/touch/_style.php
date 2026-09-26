<link href="<?= base_url(paths()->getAssetsDir('css/'. ($type_template ?? (Service::engines()->getTypeAlias() ?: 'close')) .'_default.min.css')) ?>" rel="stylesheet" type="text/css"/>
<link href="<?= base_url(paths()->getAssetsDir('css/styles.css')) ?>" rel="stylesheet" type="text/css"/>

<?= view()->renderer(paths()->getTplDir('_style', 'common') , ['css' => (isset($_page['css']) ? $_page['css'] : null)]) ?>
