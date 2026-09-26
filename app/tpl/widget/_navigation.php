<?php

use AC\core\system\helpers\StringHelper;

$engine = Service::engines();
$menu = $structure->getMenu();
$escape = static fn($value): string => StringHelper::shield((string)$value, doubleEncode: false);
$authorized = USE_AUTHORIZATION && $engine->clients->checkAuthorization();
$userIconPath = Service::autoloader()->getPathFile(paths()->getTplDir('images/user.svg', 'widget'), 'svg');
?>
<nav class="fm-widget-nav" aria-label="<?= lang('menu', 'menu') ?>">
  <?php foreach ([true, false] as $bookingItems) { ?>
    <div class="fm-nav-group <?= $bookingItems ? 'fm-nav-courts' : 'fm-nav-pages' ?>">
      <?php foreach ($menu as $item) {
        if (str_contains($item, 'reservations_') !== $bookingItems) { continue; }
        $pageData = $structure->getPageDataByKey($item);
        $active = $structure->pageIsParent($_page['key'], $item);
        ?>
        <a href="<?= $escape($structure->getPageHrefByKey($item)) ?>" class="fm-nav-link<?= $active ? ' fm-active' : '' ?>"
           <?= $active ? 'aria-current="page"' : '' ?><?= isset($pageData['target']) ? ' target="' . $escape($pageData['target']) . '"' : '' ?>>
          <?= $pageData['title'] ?>
        </a>
      <?php } ?>
    </div>
  <?php } ?>
  <?php if (USE_AUTHORIZATION) { ?>
    <?php if ($authorized) { ?>
      <div class="fm-account-name">
        <span class="fm-user-icon" aria-hidden="true"><?php include $userIconPath; ?></span>
        <span class="fm-user-text"><?= lang('hello') ?> <?= $escape($engine->clients->getClientName()) ?></span>
      </div>
    <?php } else { ?>
      <a href="#auth-field" class="fm-login" id="login_block">
        <span class="fm-user-icon" aria-hidden="true"><?php include $userIconPath; ?></span>
        <span class="fm-user-text"><?= lang('Login') ?></span>
      </a>
    <?php } ?>
  <?php } ?>
</nav>
<?php if ($authorized) {
  $client = $engine->clients->current_client_data;
  $showClientData = !isset($client['show_client_data']) || $client['show_client_data'];
  $engine->areas->getAreasTypesData($types);
  $usePrivateAccount = !(count($types) === 1 && current($types)['alias'] === 'open');
  ?>
  <section class="fm-account-links" aria-label="<?= lang('Personal account', 'widget') ?>">
    <?php foreach ($structure->getStructureByParentKey('user_auth') as $item) {
      if ((!$showClientData && in_array($item['title_mess'], ['my_guthaben', 'my_data', 'my_booking']))
        || ($showClientData && defined('HIDE_LINK_AUTH_USER') && HIDE_LINK_AUTH_USER
          && in_array($item['title_mess'], explode(',', HIDE_LINK_AUTH_USER)))
        || (!$usePrivateAccount && $item['title_mess'] === 'my_guthaben')
        || ($engine->clients->isBar() && empty($item['bar']))) { continue; }
      ?>
      <a href="<?= $escape(site_url($item['href'])) ?>">
        <?= $item['title_mess'] === 'my_data' ? lang('Personal account', 'widget') : lang($item['title_mess']) ?>
        <?php if ($item['title_mess'] === 'my_guthaben') { ?>
          : <?= number_format($client['prepayment_sum'], 2, ',', '') . CURR_VALUTE ?>
        <?php } ?>
      </a>
    <?php } ?>
  </section>
<?php } ?>
<?php if (count(config('lang')->getActiveLanguages()) > 1) { ?>
  <nav class="fm-widget-languages" aria-label="<?= lang('Language', 'widget') ?>">
    <?php foreach (config('lang')->getActiveLanguages() as $language) { ?>
      <a href="<?= $escape(Service::url()->currentUrl([], ['lang' => $language])) ?>"
         <?= $language === config('lang')->getCurrentLang() ? 'aria-current="true"' : '' ?>><?= ucfirst($language) ?></a>
    <?php } ?>
  </nav>
<?php } ?>
