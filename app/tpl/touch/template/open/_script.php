<script type="text/javascript" src="<?= base_url(paths()->getAssetsDir('js/open/main.js')) ?>"></script>
<script type="text/javascript" src="<?= base_url(paths()->getAssetsDir('js/open/login.js')) ?>"></script>
<script type="text/javascript" src="<?= base_url(paths()->getAssetsDir('js/open/loading.js')) ?>"></script>
<script type="text/javascript" src="<?= base_url(paths()->getAssetsDir('js/open/cards.js')) ?>"></script>
<script type="text/javascript" src="<?= base_url(paths()->getAssetsDir('js/open/cookie.js')) ?>"></script>

<script>
  amount_cell = 0
  current_amount_cell = 0
  per_page = <?= OPEN_STREET_PER_PAGE ? OPEN_STREET_PER_PAGE : PER_PAGE?>

  var cookie = new Cookie('scrolldata')
  if (cookie.hr)
    cookie.hr = 0
  if (cookie.vr)
    cookie.vr = 0
  cookie.store(1)
</script>

