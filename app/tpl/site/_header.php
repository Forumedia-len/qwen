<?php
/**
 * @var Engines $engine
 * @var Structure $structure
 * @var array $_page
 */

use AC\core\system\structure\Structure;
use AC\core\engines\Engines;
use AC\core\system\helpers\StringHelper;

$engine = Service::engines();
/**
 * @param           $menuItems
 * @param Structure $structure
 * @param           $_page
 *
 * @return string
 */
$prepareReservationMenuItem = static function ($menuItems, $structure, $_page)
{
  //Если у нас несколько типов кортов, то формируем выподающий список с ними
  $reservations_count          = 0;
  $reservations_items          = [];
  $reservations_menu_is_active = false;

  foreach ($menuItems as $i => $item) {
    if (stristr($item, "reservations_")) {
      $reservations_count++;
      $reservations_items[$i] = $item;

      if ($structure->pageIsParent($_page['key'], $menuItems[$i]) == true) {
        $reservations_menu_is_active = true;
      }
    }
  }

  $reservations_menu = "";
  if ($reservations_count > 1) {
    $reservations_menu .= '
            <li class="dropdown-menu">
                <a class="' . ($reservations_menu_is_active == true ? 'active' : '') . ' dropdown-menu-button">
                    <span>' . (USE_AUTHORIZATION ? lang('booking', 'menu') : lang('place_overview', 'menu')) . '</span>
                </a>
                <div class="dropdown-menu-content dropdown-menu-invisible">';

    foreach ($reservations_items as $i => $reservations_item) {
      $page_data         = $structure->getPageDataByKey($reservations_items[$i]);
      $reservations_menu .= '<div class="header-dropdown-menu-item"><a href="' . $structure->getPageHrefByKey(
          $reservations_items[$i]
        ) . '"  ' . (isset($page_data['target']) ? ' target="' . $page_data['target'] . '"' : '') . ' ' . ($structure->pageIsParent(
          $_page['key'],
          $reservations_items[$i]
        ) == true ? 'class="active"' : '') . ' id="' . $page_data['id'] . '_nav"><span>' . $page_data['title'] . '</span></a></div>';
    }

    $reservations_menu .= '
                            </div>
                        </li>';
  } elseif ($reservations_count == 1) {
    foreach ($reservations_items as $i => $item) {
      $page_data         = $structure->getPageDataByKey($reservations_items[$i]);
      $reservations_menu .= '<li><a href="' . $structure->getPageHrefByKey($menuItems[$i]) . '" ' . (isset($page_data['target'])
          ? ' target="' . $page_data['target'] . '"' : '') . ' ' . ($structure->pageIsParent(
          $_page['key'],
          $menuItems[$i]
        ) == true ? 'class="active"' : '') . ' id="' . $page_data['id'] . '_nav"><span>' . (USE_AUTHORIZATION ? lang('booking',
          'menu') : lang('place_overview', 'menu')) . '</span></a></li>';
    }
  }

  return $reservations_menu;
};

?>
<div class="header">
  <div class="header-line"></div>
  <div class="container">
    <div class="row">
      <div class="inner-header col-md-12">
        <div class="row">
          <div class="header-left">
            <?php if (file_exists(pathAs(ROOT_PATH . paths()->getAssetsDir('images/logo.png',
                  'common'))) && file_exists(pathAs(ROOT_PATH . paths()->getAssetsDir('images/logo_sm.png', 'common')))) : ?>
              <a href="<?= site_url('index.php') ?>">
                <?php if (file_exists(pathAs(ROOT_PATH . paths()->getAssetsDir('images/logo.png', 'common')))) : ?>
                  <img class="header-logo" src="<?= base_url(paths()->getAssetsDir('images/logo.png', 'common')) ?>" alt="">
                <?php endif; ?>
                <?php if (file_exists(pathAs(ROOT_PATH . paths()->getAssetsDir('images/logo_sm.png', 'common')))) : ?>
                  <img class="header-logo-sm" src="<?= base_url(paths()->getAssetsDir('images/logo_sm.png', 'common')) ?>" alt="">
                <?php endif; ?>
              </a>
            <?php endif; ?>
          </div>
          <?= view()->renderer(paths()->getTplDir('_langs.php')) ?>
          <div class="header-right">
            <ul class="header-navigation-menu">
              <div class="header-dropdown-menu">
                <div class="mobile-menu-item main-menu">
                  <a class="header-dropdown-menu-button main-menu-btn visible-xs-block visible-sm-block">
                    <span class="text-menu-btn"><?= lang('menu', 'menu') ?></span>
                  </a>
                  <div class="header-dropdown-menu-content dropdown-menu-content dropdown-menu-invisible">
                    <ul>
                      <?php
                      //верхнее меню
                      $items = Service::structure()->getMenu();

                      $out_menu          = '';
                      $reservations_menu = $prepareReservationMenuItem($items, $structure, $_page);

                      $is_reservation_menu_insert = false;

                      foreach ($items as $i => $item) {
                        $page_data = $structure->getPageDataByKey($items[$i]);
                        if ($structure->pageIsParent($_page['key'], $items[$i]) == true) {
                          $out_menu = '<script type="text/javascript">menu_selected = ' . $i . ';</script>' . $out_menu;
                        }

                        if (stristr($item, "reservations_")) {
                          if (!$is_reservation_menu_insert) {
                            $out_menu                   .= $reservations_menu;
                            $is_reservation_menu_insert = true;
                          }
                        } else {
                          $out_menu .= '<li><a href="' . $structure->getPageHrefByKey($items[$i]) . '" ' . (isset($page_data['target'])
                              ? ' target="' . $page_data['target'] . '"' : '') . ' ' . ($structure->pageIsParent(
                              $_page['key'],
                              $items[$i]
                            ) == true ? 'class="active"'
                              : '') . ' id="' . $page_data['id'] . '_nav"><span>' . $page_data['title'] . '</span></a></li>';
                        }
                      }
                      print $out_menu;
                      ?>
                    </ul>
                  </div>

                </div>
                <?php
                if (USE_AUTHORIZATION) {
                  if ($engine->clients->checkAuthorization() == true) {
                    ?>
                    <div class="mobile-menu-item user-btn-wrapper dropdown-menu registered-user">
                      <a class="user-btn tablet-user-button dropdown-menu-button">
                        <span class="text-menu-btn"><?= lang('hello') ?> <?= StringHelper::shield($engine->clients->getClientName(),
                            doubleEncode: false) ?></span>
                        <span class="arrow-down"></span>
                      </a>
                      <?php /* --------     user-drop-menu       ----------*/ ?>
                      <div class="dropdown-menu-content dropdown-menu-invisible">
                        <?php
                        $client = $engine->clients->current_client_data;
                        $usePrivateAccount = true; //скрываем/показывать кнопку "Мой счет"
                        $showClientData = !isset($client['show_client_data']) || $client['show_client_data']; //скрываем/показывать все данные клиента
                        $engine->areas->getAreasTypesData($types);
                        if (count($types) == 1 && current($types)['alias'] == 'open') {
                          $usePrivateAccount = false;
                        }
                        ?>
                        <?php foreach ($structure->getStructureByParentKey('user_auth') as $key => $item) {
                          if ((!$showClientData && in_array($item['title_mess'], ['my_guthaben','my_data','my_booking']))
                            || ($showClientData && defined('HIDE_LINK_AUTH_USER') && (HIDE_LINK_AUTH_USER)
                            && (in_array($item['title_mess'],explode(',', HIDE_LINK_AUTH_USER))))
                          ) {
                            continue;
                          }
                          ?>
                          <?php if ((($engine->clients->isBar() && isset($item['bar']) && $item['bar']) || (!$engine->clients->isBar()))
                            && ($usePrivateAccount || $item['title_mess'] != 'my_guthaben')) { ?>
                            <div class="header-dropdown-menu-item">
                              <a href="<?= site_url() . $item['href'] ?>" class="<?= (substr(
                                $_SERVER['REQUEST_URI'],
                                1
                              ) && $item['href'] && stripos(
                                $item['href'],
                                substr($_SERVER['REQUEST_URI'], 1)
                              ) !== false ? 'active' : '') ?>">
                                <span>
                                  <?= (($item['title_mess'] == 'my_guthaben')
                                    ? (lang($item['title_mess']) . ' : ' . number_format(
                                        $engine->clients->current_client_data['prepayment_sum'],
                                        2,
                                        ',',
                                        ''
                                      ) . CURR_VALUTE)
                                    : lang($item['title_mess']))
                                  ?>
                                </span>
                              </a>
                            </div>
                          <?php } ?>
                        <?php } ?>
                      </div>
                      <?php /* --------     user-drop-menu       ----------*/ ?>
                    </div>
                  <?php } else { ?>
                    <div class="mobile-menu-item user-btn-wrapper dropdown-menu" style="cursor: pointer">
                      <a class="user-btn tablet-user-button dropdown-menu-button" id="login_block">
                        <span class="text-menu-btn"><?= (MC_ARENA ? lang('register', 'menu') : lang('Login')) ?></span>
                      </a>
                    </div>
                  <?php } ?>
                <?php } else { ?>
                  <div class="no-registered">
                    <div class="no-registered-text"><?= lang('text_please_phone_book_under') ?></div>
                    <div class="no-registered-phone">02641-95070-10</div>
                  </div>
                <?php } ?>
                <?php if (isset($_page['mobile_calendar'])) { ?>
                  <div class="mobile-menu-item calendar-button">
                    <a class="tablet-calendar-button" id="tablet-calendar-button-id">
                      <span class="text-menu-btn"><?= lang('calendar') ?></span>
                    </a>
                  </div>
                <?php } ?>

              </div>
            </ul>
            <div class="logo2">
              <?php if (file_exists(pathAs(ROOT_PATH . paths()->getAssetsDir('images/logo2.png', 'common')))) : ?>
                <img class="header-logo2" src="<?= base_url(paths()->getAssetsDir('images/logo2.png', 'common')) ?>" alt="logo2">
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
