<?php

use AC\core\system\helpers\StringHelper;
use AC\core\system\helpers\TemplateThemeHelper;

$structure = Service::structure();
$widgetColors = TemplateThemeHelper::getCssVariables(WIDGET_PRIMARY_COLOR, WIDGET_ACCENT_COLOR);
$widgetStyles = [];
foreach ($widgetColors as $name => $value) {
  $widgetStyles[] = $name . ':' . $value;
}
// URL логотипов относятся к установке даже при загрузке всех таблиц стилей с CDN.
foreach (['logo', 'logo_sm'] as $name) {
  $widgetStyles[] = '--ac-image-' . str_replace('_', '-', $name)
    . ':url("' . base_url(paths()->getAssetsDir('images/' . $name . '.png', 'common')) . '")';
}
$hasCalendar = !empty($_page['showCalendar']) && isset($_page['content'][0]);
?>
<!DOCTYPE html>
<html lang="<?= config('lang')->getCurrentLang() ?>"
      style="<?= StringHelper::shield(implode(';', $widgetStyles)) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="robots" content="noindex, indexifembedded">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= config('app')->getProjectTitle() ?><?= isset($_page['title']) ? ' - ' . $_page['title'] : '' ?></title>
  <base href="<?= site_url() ?>">
  <script type="application/json" id="ac-widget-theme-config"><?= json_encode(
    TemplateThemeHelper::getClientConfig(WIDGET_PRIMARY_COLOR, WIDGET_ACCENT_COLOR),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
  ) ?></script>
  <script src="<?= cdn_url(paths()->getAssetsDir('js/theme-helper.js', 'widget')) ?>?v=20260917-1"></script>
  <script src="<?= cdn_url(paths()->getAssetsDir('js/theme.js', 'widget')) ?>?v=20260917-1"></script>
  <?php foreach ($_page['head'] ?? [] as $head) { echo $head; } ?>
  <?= view()->renderer(paths()->getTplDir('_style.php'), get_defined_vars()) ?>
  <script src="<?= cdn_url(paths()->getAssetsDir('js/jquery.min.js', 'common')) ?>"></script>
  <?= view()->renderer(paths()->getTplDir('_base_script.php', 'common'), get_defined_vars()) ?>
</head>
<body class="fm-widget">
  <?= view()->renderer(paths()->getTplDir('_auth.php'), get_defined_vars()) ?>
  <section class="fm-widget-shell">
    <?= view()->renderer(paths()->getTplDir('_navigation.php'), get_defined_vars()) ?>
    <main class="main fm-widget-main">
      <?php if ($hasCalendar) { ?>
        <section class="fm-booking-tools">
          <?= $_page['content'][0] ?>
        </section>
      <?php } elseif (!isset($_page['useH1']) || $_page['useH1']) { ?>
        <h2 class="fm-page-title"><?= $_page['h1'] ?? $_page['title'] ?? '' ?></h2>
      <?php } ?>
      <?php if (!empty($_page['title_block'])) { ?>
        <section class="fm-booking-help"><?= $_page['title_block'] ?></section>
      <?php } ?>
      <section class="content-block <?= $_page['class'] ?? '' ?> fm-widget-content">
        <?= $_page['content'][1] ?>
      </section>
    </main>
  </section>
  <?= view()->renderer(paths()->getTplDir('_script.php'), get_defined_vars()) ?>
</body>
</html>
