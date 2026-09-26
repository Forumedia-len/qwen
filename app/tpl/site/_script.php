<script type="text/javascript" src="<?= cdn_url(paths()->getAssetsDir('js/common.js')) ?>"></script>
<script type="text/javascript" src="<?= cdn_url(paths()->getAssetsDir('js/default.js')) ?>"></script>
<script type="text/javascript" src="<?= cdn_url(paths()->getAssetsDir('js/all_device.js', 'common')) ?>"></script>
<?= view()->renderer(paths()->getTplDir('_script.php', 'common'), ['js' => (isset($_page['js']) ? $_page['js'] : null)]) ?>

<?php if (config('cookieApply')->checkUseOtherCookies() && MC_ARENA && !LOCAL_SERVER) { ?>
  <!-- Google Tag Manager -->
  <script>(function (w, d, s, l, i) {
      w[l] = w[l] || []
      w[l].push({
        'gtm.start'                  :
          new Date().getTime(), event: 'gtm.js'
      })
      var f  = d.getElementsByTagName(s)[0],
          j  = d.createElement(s),
          dl = l != 'dataLayer' ? '&l=' + l : ''
      j.async = true
      j.src =
        'https://www.googletagmanager.com/gtm.js?id=' + i + dl
      f.parentNode.insertBefore(j, f)
    })(window, document, 'script', 'dataLayer', 'GTM-WBHVBS8')</script>
  <!-- End Google Tag Manager -->
  <!-- Google Tag Manager (noscript) -->
  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WBHVBS8"
            height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>
  <!-- End Google Tag Manager (noscript) -->
<?php } ?>
<?php if(false && config('cookieApply')->checkUseOtherCookies() && !LOCAL_SERVER): ?>
  <script>// facebook
    $(function (d, s, id) {
      var js,
          fjs = d.getElementsByTagName(s)[0]
      if (d.getElementById(id)) return
      js = d.createElement(s)
      js.id = id
      js.src = '//connect.facebook.net/de_DE/all.js#xfbml=1&appId=390245934380325'
      fjs.parentNode.insertBefore(js, fjs)
    }(document, 'script', 'facebook-jssdk'))
  </script>
  <br>
  <div class="fb-like" data-href="http://www.facebook.com/activecourt" data-send="false" data-layout="button_count" data-width="150"
       data-show-faces="false" style="display: none"></div>
<?php endif;?>
