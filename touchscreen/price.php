<?php

use AC\core\modules\text\engines\TextEngine;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\TranslateHelper;

$e = Service::engines();
$e->areas->getAreasTimeTableByType(1, $tts, $prices);

$e->extra->getExtra($extra_data);
?>
<!doctype html>
<html>
<head>
  <?php if (defined('NOINDEX_SITE') && NOINDEX_SITE) { ?>
    <meta name="robots" content="noindex, indexifembedded">
  <?php } ?>
  <title><?=config('app')->getProjectTitle()?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <link href="<?= base_url(paths()->getAssetsDir('css/close_default.min.css')) ?>" rel="stylesheet" type="text/css"/>
  <link href="<?=base_url(paths()->getAssetsDir('css/styles.css')) ?>" rel="stylesheet" type="text/css"/>
  <script src="<?= base_url(paths()->getAssetsDir('js/jquery.min.js')) ?>"></script>
</head>

<body>
<table width="100%" id="price" cellpadding="0" cellspacing="0">
  <tr>
    <td class="top_bottom_line" style="height: 200px">
      <h1><?= lang('welcome') ?></h1>
      <h2><?= lang('with_the_touch_screen_booking_system_from', 'price') ?></h2>
    </td>
  </tr>
  <tr>
	<td valign="top" id="section_right">
		<div id="loginForm">
		<h1><?= lang('price_list', 'price') ?></h1>
			<?php
	    /** @var TextEngine $txt */
	    $txt = getEngine('text', false);
	    $txt?->getContent($row, 'price');
	    if(!empty($row['content']))
	    {
		    echo $row['content'];
	    }
	    else {
	    foreach ($prices as $weekdays) {
		    echo '<strong>';
		    echo TranslateHelper::translateWeekday($weekdays[0]);
		    if ($weekdays[0] != $weekdays[1])
			    echo ' - ' . TranslateHelper::translateWeekday($weekdays[1]);
		    echo '</strong><br/>';

		    echo "<table cellspacing=\"6\">";
		    echo '<tr><td width="120">&nbsp;</td><td><i>'.lang('members').'</i></td><td><i>'.lang('non_members').'</i></td></tr>';
		    foreach ($weekdays[2] as $period) {
			    echo '<tr><td width="120">' . $period[0] . ' - ' . $period[1] . '</td><td>' . NumberHelper::format ($period[2]+$extra_data['club_rate']) . ' '.CURR_VALUTE.'</td><td>' . NumberHelper::format($period[2]+$extra_data['no_club_rate']) . ' '.CURR_VALUTE.'</td></tr>';
		    }
		    echo "</table><br/>";
	    }
	    }
	?><br/>
	<a href="<?= site_url('index.php')?>" class="a-back" style="margin-left:50px"><?= lang('back')?></a>
		<p/>
		</div>
	</td>
</tr>
  <tr>
    <td class="top_bottom_line2" style="height: 200px"><?= Service::view()->renderer(paths()->getTplDir('_banners')) ?>
    </td>
  </tr>
</table>
<!--<script>-->
<!--    let time = 180000-->
<!--    if ($('time').length > 0) {time = $('time').data('time')}-->
<!--    let handle = setTimeout(function () {window.location.href = "http://tests.lc/tennishalle-buechlberg/touchscreen/"}, time)-->
<!--    $('body').on('click', function () {-->
<!--      clearTimeout(handle)-->
<!--      handle = setTimeout(function () {window.location.href = "http://tests.lc/tennishalle-buechlberg/touchscreen/"}, time)-->
<!--    })-->
<!---->
<!--  </script>-->
</body>
</html>
