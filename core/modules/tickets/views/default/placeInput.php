<?php
/**
 * @var string $path_ajax_params
 * @var string $selectAreas
*/
?>
<script>
  let load_time = new ajaxLoader('load_time', 'ajax_time_tickets.php?action=getAreaTimes', 'loadTimeBlock', 'loaderTimeBlock')
  todo.onload(function () {
    load_time.loadModule('<?=$path_ajax_params?>')})
</script>
<div id="loaderTimeBlock" style="float:right"><img src="<?= base_url(paths()->getAssetsDir('images/ajax_loader.gif')) ?>" alt="load"/></div>
<?= $selectAreas ?>