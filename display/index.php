<?php
use AC\core\ReservationsVisualizationCommon;

$engine = Service::engines();
$height = 130;
$w_img = 110;
$w_h1 = 160;
$p_img = 0;


if (isset($_GET['date'])) {
  $date = date('Y-m-d', strtotime($_GET['date']));
} else {
  $date = date('Y-m-d');
}
ReservationsVisualizationCommon::setView(['tpl_view' => 'reservations']);

$unixtime = time();
?><!DOCTYPE html>
<html lang="de">
<head>
  <?php if (defined('NOINDEX_SITE') && NOINDEX_SITE) { ?>
    <meta name="robots" content="noindex, indexifembedded">
  <?php } ?>
  <title><?= config('app')->getProjectTitle() ?></title>
  <meta charset="utf-8">
  <link href="<?= base_url(paths()->getAssetsDir('css/reservations.css')) ?>" rel="stylesheet" type="text/css">
  <script>
    window.baseUrl = '<?= base_url()?>'
    window.siteUrl = '<?= site_url()?>'
  </script>
  <script type="text/javascript" src="<?= base_url(paths()->getAssetsDir('js/jquery.js')) ?>"></script>
  <script type="text/javascript" src="<?= base_url(paths()->getAssetsDir('js/ajaxloadmodule.js')) ?>"></script>
</head>
<body>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>
      <div id="section_img_tennis">
        <div style="float:left;margin-right:20px;height: <?= $height ?>px" class="h1-sect">
          <img src="<?= base_url(paths()->getAssetsDir('images/logo.png', 'common')) ?>"
               style="float: left;display: block;width: <?= $w_img ?>px;padding: <?= $p_img ?>px; margin-top: <?= $p_img ?>px; margin-left: <?= $p_img ?>px"/>
          <span id="current-date-box" style="display: none"><?= date('d.m.Y') ?></span>
          <h1 style="margin: 0 10px 0 <?= ($w_img + $p_img * 3 + 15) ?>px;display: block;width: <?= $w_h1 ?>px"><?= lang('tennis_courts',
              'index') ?></h1>
          <div id="date-box" style="position: relative; top: -40px;left: 120px">
            <ul>
              <li id="day-box">03</li>
              <li>.</li>
              <li id="month-box">01</li>
              <li>.</li>
              <li id="year-box">2012</li>
              <li> &nbsp;</li>
              <li id="hour-box">13</li>
              <li>:</li>
              <li id="minute-box">33</li>
              <li>:</li>
              <li id="second-box">33</li>
            </ul>
          </div>
        </div>
        <div style=" margin-left: 400px">
          <?
          //баннеры
          $b     = useClass(paths()->enginesDir . 'BannersEngine', true);
          $shown = 0;

          $b->getBanners($banners, 5, 1);
          //          echo '<img src="' . BASE_HREF . 'assets/images/' . '/bilder/active_court_banner.gif" alt="Tennis-Reservierung Online" hspace="15" style="max-width: 300px;max-height:'.$height.'px">';
          for ($i = 0; $i < 4; $i++) {
            if (isset ($banners[$i]['image'])) {
              echo '		<img src="' . base_url($banners[$i]['image']) . '" alt="' . $banners[$i]['alt'] . '" hspace="15" style="max-width: 300px;max-height:' . $height . 'px">' . "\n";
            } else {
              echo '		<img src="' . base_url(
                  paths()->getAssetsDir('images/bilder/no.gif', 'common')
                ) . '" alt="' . lang('your_advertising_could_be_here',
                  'index') . '" style="max-width: 300px;max-height:' . $height . 'px" hspace="15">' . "\n";
            }
          }
          ?>
        </div>
      </div>
      <table style="width:100%">
        <tr>
          <td>
            <?php
            $arr_type_sport = [['type_id' => 1, 'sport_id' => 1], ['type_id' => 2, 'sport_id' => 1]];
            if (DISPLAY_TYPE) {
              $arr_type_sport = [];
              $type_sport     = explode(',', DISPLAY_TYPE);
              foreach ($type_sport as $val) {
                $type_sport_val   = explode('_', $val);
                $arr_type_sport[] = ['type_id' => $type_sport_val[0], 'sport_id' => $type_sport_val[1]];
              }
            }
            echo ReservationsVisualizationCommon::renderAreasTypeColumns(
              $engine,
              $date,
              $arr_type_sport,
//              1,
              1,
              1,
              null,
              0,
              false,
              false,
              true
            );
            ?>
          </td>
        </tr>
      </table>
  </tr>
</table>
</body>
</html>
