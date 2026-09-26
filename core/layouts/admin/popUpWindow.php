<?php
/**
 * @var $place_id
 * @var $place_class
 * @var $window_id
 * @var $window_class
 */
?>
<div class="<?= $place_class?>" id="<?= $place_id ?>"></div>
<div class="<?= $window_class?>" id="<?= $window_id?>"></div>
<script>var popup = new popupAjaxWindow('<?= $place_id?>', '<?=$window_id?>');</script>