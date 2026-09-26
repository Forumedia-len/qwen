<?php
/**
 * @var int    $sum
 * @var string $door_codes
 * @var string $message
 * @var string $pay
 * @var string $date
 * @var array  $times
 */

?>
<?= $message ?>
<?= $door_codes ?>
<br>
<?= $pay ?>
<p><strong><?= lang('Your booking details', 'show_order')?>:</strong></p>
<div style="padding-left: 20px;margin-bottom: 10px">
  <div style="display: inline-block;min-width: 100px"><?= lang('Appointment', 'show_order')?>:</div>
  <div style="display: inline-block;"><?= date('d.m.Y', strtotime($date)) ?></div>
</div>
<div style="padding-left: 20px;position: relative;">
  <div style="display: inline-block; min-width: 100px; position: absolute; top: 8px;"><?= lang('Time')?>:</div>
  <div style="    display: inline-block; position: relative; left: 104px; top: 0px;">
    <?php foreach ($times as $time) { ?>
      <p><?= str_replace(' - ', ' bis ', $time) ?> <?= lang('clock') ?></p>
    <?php } ?>
  </div>
</div>

<?php if (config('cookieApply')->checkUseOtherCookies() && !LOCAL_SERVER) { ?>
  <script>
    //dataLayer = [];
    window.dataLayer = window.dataLayer || []
    window.dataLayer.push({
      'halle': "<?=config('app')->getProjectTitle() ?>",
      'preis': "<?= $sum ?>"
    })
  </script>
<?php } ?>
