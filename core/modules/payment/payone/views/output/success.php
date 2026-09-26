<?php
/**
 * @var string $message
 * @var string $typeOrder
 * @var string $price
 * @var string $messageOrder
 * @var string $door_code_out
 */

?>
<?= $message ?>
<?php
if ($typeOrder == 'reservation') { ?>
  <?php if (config('cookieApply')->checkUseOtherCookies() && MC_ARENA && !LOCAL_SERVER) { ?>
    <script>
      dataLayer = []
      dataLayer.push({
        halle: "<?= config('app')->getProjectTitle() ?>",
        preis: <?= $price ?>
      })
    </script>
  <?php } ?>
  <br/>
  <?= $door_code_out ?>
<?php } ?>
<p style="text-align: center">
  <a href="<?= site_url() ?>">
    <?= lang('home') ?>
  </a>
</p>
