<?php if (count(config('lang')->getActiveLanguages()) > 1): ?>
  <div class='header-langs'>
    <div class='container'>
      <div class='row'>
        <div class="block_langs">
          <?php foreach (config('lang')->getActiveLanguages() as $lang): ?>
            <?php if ($lang == config('lang')->getCurrentLang()) : ?>
              <span class='lang-item active' data-lang='<?= $lang ?>'><?= ucfirst($lang) ?></span>
            <?php else: ?>
              <a href='<?= Service::url()->currentUrl([], ['lang' => $lang]) ?>' class='lang-item' data-lang='<?= $lang ?>'><?= ucfirst($lang) ?></a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>