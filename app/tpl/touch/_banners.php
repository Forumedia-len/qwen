<?php
//баннеры

/** @var BannersEngine $b */

use AC\core\engines\BannersEngine;

$b = useClass(paths()->enginesDir . 'BannersEngine', true);
$b->getBanners($banners, 4, 1);

?>
  <img src="<?= base_url(paths()->getAssetsDir('images/bilder/active_court_banner.gif', 'common')) ?>"
       alt="<?= lang('tennis_reservation_online')?>" hspace="15">
<?php for ($i = 0; $i < 4; $i++) {
  if (isset ($banners[$i]['image'])) { ?>
    <img src="<?= base_url($banners[$i]['image']) ?>" alt="<?= $banners[$i]['alt'] ?>" hspace="15">
  <?php } else { ?>
    <img src="<?= base_url(paths()->getAssetsDir('images/bilder/no.gif', 'common')) ?>" alt="<?=lang('your_advertising_could_be_here')?>"
         width="150" height="100" hspace="15">
  <?php }
}
