<!DOCTYPE html>
<html>
<head>
  <?php if (defined('NOINDEX_SITE') && NOINDEX_SITE) { ?>
    <meta name="robots" content="noindex, indexifembedded">
  <?php } ?>
<meta name="viewport" content="width=device-width,initial-scale=1" />
<link href="<?=BASE_URL?>view/css/bootstrap.min.css" rel="stylesheet">
<link href="<?=BASE_URL?>view/css/bootstrap-icons.css" rel="stylesheet">
<link href="<?=BASE_URL?>view/css/styles.css" rel="stylesheet">
</head>
<body>
<header>
<div class="head-exit p-4 d-flex justify-content-end">
	<?if(MAutorize::isAutorize()):?>
	<form method="post" action="<?=BASE_URL?>?action=CAutorize">
		<input type="hidden" name="exit" value="Y">
		<input type="submit" class="btn btn-primary" value="Выйти">
	</form>
	<?endif?>
</div>
<script>
	var BASE_URL=<?=BASE_URL?>;
</script>	
</div>
</header>

