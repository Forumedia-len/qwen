<script>
  window.baseUrl = '<?= base_url()?>'
  window.siteUrl = '<?= site_url()?>'
  window.cdnUrl = '<?= cdn_url()?>'
  window.currentLang = '<?= config('lang')->getCurrentLang() ?>'
  window.maskIban = '<?= (USE_TEST_AND_MASK_IBAN ?: "AA 99 9999 9999 9999 9999 99") ?>'

</script>
<script type="text/javascript" src="<?= cdn_url(paths()->getAssetsDir('js/base.js', 'common')) ?>" ></script>