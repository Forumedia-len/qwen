<?php
/** @todo на базе есть еще один вариант прорисовки */
$_page['key'] = 'index';

$menu['price'] = [
  'image' => base_url(paths()->getAssetsDir('images/menu/price.svg')),
  'title' => lang('price', 'menu'),
  'url'   => site_url('price.php?type_id=' . $type_id)
];
ob_start();
?>
  <div style="display: flex;justify-content: center;" class="select-content">
    <div style="margin: 0 20px;width: 230px;height: 250px;text-align: center;vertical-align: middle" class="logo-block">
      <?php if(file_exists(ROOT_PATH .paths()->getAssetsDir('images/logo.png'))) { ?>
        <img src="<?= base_url(paths()->getAssetsDir('images/logo.png')) ?>" style="width: 100%;margin: auto" alt="logo"/>
      <?php } else { ?>
        <img src="<?= base_url(paths()->getAssetsDir('images/logo_sm.png', 'common')) ?>" style="width: 100%;margin: auto" alt="logo"/>
      <?php }?>
    </div>
    <?php foreach ($menu as $button) { ?>
      <div style="margin: 0 20px;width: 212px;height: 250px;text-align: center;background: #fff">
        <a href="<?= $button['url'] ?>"
           style="color: #000059 !important; display: flex;width: 100%;height : 100%;flex-direction: column;justify-content: space-around;">
          <img src="<?= $button['image'] ?>" style="width: 50%;margin: auto" alt="<?= $button['title'] ?>"/>
          <span style="font-size: 23px;flex-basis: 25%;">
            <?= $button['title'] ?>
          </span>
        </a>
      </div>
    <?php } ?>
  </div>
<?php
$_page['content'][1] = ob_get_contents();
ob_end_clean();