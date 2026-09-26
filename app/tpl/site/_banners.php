<div class="banners">
  <div class="row">
    <ul>
      <?php
      $b     = useClass(paths()->enginesDir . 'BannersEngine', true);
      $shown = 0;
      if ($b->getBanners($banners, 4, 1)) {
        foreach ($banners as $banner) {
          if ($banner['image']) { ?>
            <li class="col-xs-6 col-sm-3">
              <?php if (!empty($banner['url'])) : ?>
              <a href="<?= $banner['url'] ?>" target='_blank'>
                <?php endif; ?>
                <img src="<?= $banner['image'] ?>" alt="<?= $banner['alt'] ?>" width="150" height="100"/>
                <?php if (!empty($banner['url'])) : ?>
              </a>
            <?php endif; ?>
            </li>
            <?php $shown++;
          }
        }
      }
      for ($i = $shown; $i < 4; $i++) { ?>
        <li class="col-xs-6 col-sm-3 banner-no-visible">
          <a>
            <?php $placeholder = paths()->getAssetsDir('images/bilder/no.gif', 'common'); ?>
            <img src="<?= Service::url()->getTemplatePath() === 'widget' ? cdn_url($placeholder) : base_url($placeholder) ?>"
                 alt="not banner" width="150" height="100"
                 class="banner-img"/>
          </a>
        </li>
      <?php } ?>
    </ul>
  </div>
</div>
