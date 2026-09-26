
<script type="text/javascript" src="<?= base_url(paths()->getAssetsDir('js/jquery.min.js', 'common')) ?>"></script>
<?= view()->renderer(paths()->getTplDir('_base_script.php', 'common'))?>
<script type="text/javascript" src="<?= base_url(paths()->getAssetsDir('js/jquery.colorbox-min.js')) ?>"></script>

<?= view()->renderer(paths()->getTplDir('template/' . ($type_template ?? Service::engines()->getTypeAlias()) . '/_script')) ?>

<script type="text/javascript" src="<?= base_url(paths()->getAssetsDir('js/login.js')) ?>"></script>
<script type="text/javascript" src="<?= base_url(paths()->getAssetsDir('js/keyboard.js')) ?>"></script>
<script type="text/javascript" src="<?= cdn_url(paths()->getAssetsDir('js/default.js', 'site')) ?>"></script>

<?= view()->renderer(paths()->getTplDir('_script.php', 'common'), ['js' => (isset($_page['js']) ? $_page['js'] : null)]) ?>


