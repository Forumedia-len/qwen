<?php
use AC\app\locators\Service;
$_page['key'] = 'index';

$engine = Service::engines();
$engine->clients->logOut();
$structure = Service::structure('touch');
//Берем все типы кортов и проверяем их количество
$engine->areas->getAreasTypesData($areas_type_data);
setcookie("one_court_type", 0);
//Если количество меньше 2, то нет смысла показывать окно выбора, сразу переходим к странице имеющегося типа
if (count($areas_type_data) == 1 && count($structure->getAllKeysByPattern('login_')) == count($areas_type_data)) {
  $type_id = current($areas_type_data)['type_id'];
  Service::session()->set('type_id', $type_id);
  setcookie("one_court_type", 1);
  if (defined('TOUCH_SHOW_4BUTTON') && TOUCH_SHOW_4BUTTON) {
    include Service::autoloader()->getPathFile(paths()->getDeviceDir('index_buttons.php'));
  } else {
    Service::redirect()->redirect(site_url(Service::structure()->getPageHrefByKey('login'). "?type_id=" . $type_id))->send();
  }
} else {
  ob_start();
  ?>
  <td class="button type-selector-page-logo">
    <img src="<?= base_url(paths()->getAssetsDir('images/logo_sm.png', 'common')) ?>" class="l_t logo_type_court">
  </td>
  <?php foreach ($structure->getAllKeysByPattern('login_') as $key):
    $item = $structure->getPageDataByKey($key);
    ?>
    <td class="route_type_court">
      <a href="<?= (str_starts_with($item['href'], 'http') ? $item['href'] : site_url(). $item['href']) ?>">
        <?= $item['title'] ?>
      </a>
    </td>
  <?php endforeach; ?>
  <?php
  $_page['content'][1] = ob_get_contents();
  ob_end_clean();
}