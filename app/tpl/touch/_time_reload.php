<script>
  let time = 180000
  if ($('time').length > 0) {
    time = $('time').data('time')
  }
  let handle = setTimeout(function () {window.location.href = "<?= site_url() ?>"}, time)
  $('body').on('click', function () {
    clearTimeout(handle)
    handle = setTimeout(function () {window.location.href = "<?= site_url() ?>"}, time)
  })
</script>
