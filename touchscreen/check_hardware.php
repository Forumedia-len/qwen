<?php


 header ('location:reservations_select.php');


if(isset($_GET['type'])){
    $block = false;
    $engine = getEngine ('reservation');
    if($engine->areas->getAreasDataByType((int)$_GET['type'], $area_data))
    {
	foreach($area_data as $area){
	    if($area['light_on'] == 1 || $area['heating_on'] == 1)
		$block = true;
	}
	
	if(!$block || $engine->light->checkSock())
	{
	   header ('location:reservations_select.php');
	   die;
	}
    }

}else {
    header ('location: index.php');
    die;
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <?php if (defined('NOINDEX_SITE') && NOINDEX_SITE) { ?>
    <meta name="robots" content="noindex, indexifembedded">
  <?php } ?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?=config('app')->getProjectTitle()?></title>
<link href="<?= base_url(paths()->getAssetsDir('css/index.css'))?>" rel="stylesheet" type="text/css">
<link href="<?= base_url(paths()->getAssetsDir('css/styles.css'))?>" rel="stylesheet" type="text/css"/>
</head>

<body>
<table width="100%" id="main" cellpadding="0" cellspacing="0" border="0">
	<tr><td class="top_bottom_line">
		<a href="index.php" class="button_back2" style="position:absolute; right:1%; top:50px;"><?= lang('cancel')?></a>
		<h1><?= lang('welcome') ?>!</h1>
	</td>
	</tr>
	<tr><td style="height:60%; text-align:center; vertical-align:middle;">
		<table id="selector" border="0" cellspacing="0" cellpadding="0"  style="width:500px;">
		<tr>
			<td style="padding-right:20px;">
				<img src="<?= base_url(paths()->getAssetsDir('images/no-connect.gif'))?>" style="float:left"/>
			</td>
			<td class="big" style="text-align:left">
			    <?= lang('error_light_control', 'check_hardware') ?>
			</td>
		</tr>
		</table>	
	<tr><td class="top_bottom_line">&nbsp;</td></tr>
</table>
</body>
</html>
