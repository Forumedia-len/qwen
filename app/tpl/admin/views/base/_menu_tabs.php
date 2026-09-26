<?php
/**
 * Reusable tabs menu component.
 *
 * @var array  $tabs             Tabs config: [['id' => 'common', 'label' => 'Common', 'href' => '#'], ...]
 * @var string $active_tab       Active tab id
 * @var string $content_selector CSS selector for tab content items
 * @var string $content_prefix   Prefix for tab class in content items
 * @var string $container_class  Additional CSS class for tabs container
 * @var string $container_id     Unique container id
 */
$tabs             = $tabs ?? [];
$active_tab       = $active_tab ?? '';
$content_selector = $content_selector ?? '.menu-tab-content';
$content_prefix   = $content_prefix ?? 'tab-';
$container_class  = $container_class ?? '';
$container_id     = $container_id ?? 'menu-tabs-' . uniqid();
?>
<?php if (count($tabs) > 1): ?>
  <style>
    <?php if (!isset($GLOBALS['_menu_tabs_styles_loaded'])): ?>
    .menu-tab-content {
      display: none;
    }


    .menu-tab-content.active {
      display: block;
    }

    tr.menu-tab-content.active {
      display: table-row;
    }

    <?php $GLOBALS['_menu_tabs_styles_loaded'] = true; ?>
    <?php endif; ?>
  </style>
  <div class="menu-tabs-container <?= $container_class ?>"
       id="<?= $container_id ?>"
       data-content-selector="<?= $content_selector ?>"
       data-content-prefix="<?= $content_prefix ?>">
    <div class="list-type list-type-content">
      <?php foreach ($tabs as $tab):
        $tabId    = $tab['id'] ?? '';
        $tabLabel = $tab['label'] ?? $tabId;
        $tabHref  = $tab['href'] ?? '#';
        $isActive = $tabId === $active_tab;
        ?>
        <div class="item-type <?= $isActive ? 'active' : '' ?>">
          <a href="<?= $tabHref ?>"
             class="menu-tab-link"
             data-tab="<?= $tabId ?>"
             onclick="switchMenuTab('<?= $tabId ?>', '<?= $container_id ?>'); return false;">
            <?= $tabLabel ?>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <script>
    if (typeof window.switchMenuTab === 'undefined') {
      window.switchMenuTab = function (tabId, containerId) {
        var container = containerId ? document.getElementById(containerId) : document.querySelector('.menu-tabs-container');
        if (!container) {
          return;
        }

        var contentSelector = container.getAttribute('data-content-selector') || '.menu-tab-content';
        var contentPrefix = container.getAttribute('data-content-prefix') || 'tab-';

        document.querySelectorAll(contentSelector).forEach(function (element) {
          element.classList.remove('active');
        });

        container.querySelectorAll('.item-type').forEach(function (item) {
          item.classList.remove('active');
        });

        document.querySelectorAll(contentSelector + '.' + contentPrefix + tabId).forEach(function (element) {
          element.classList.add('active');
        });

        var activeLink = container.querySelector('.menu-tab-link[data-tab="' + tabId + '"]');
        if (activeLink) {
          activeLink.closest('.item-type').classList.add('active');
        }
      };
    }
  </script>
<?php endif; ?>
