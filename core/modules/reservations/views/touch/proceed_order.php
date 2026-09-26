<?php
/**
 * @var array|bool $door_codes
 * @var string     $message
 */

$second = 11;
?>
<div class="alignC popup-window-content" style="display: block;padding: 20px">
  <script>
    function timeRefresh () {
      var tb = document.getElementById('time-box')
      if (parseInt(tb.innerHTML) == 1) {
        location.href = 'index.php'
      }
      tb.innerHTML = (parseInt(tb.innerHTML) - 1)
      window.setTimeout(function () {timeRefresh()}, 1000)
    }

    function clearRefresh () {
      document.getElementById('time-box').innerHTML = '<?= $second?>'
      return false
    }

    $(document).ready(function () {timeRefresh()})
  </script>

  <h1><?= lang('Booking completed', 'message_success') ?></h1>
  <?= $message ?>
  <?= $door_codes ?> <br/><br/>
  <div style="font-size:35px">
    <span><?= lang('This page closes in seconds', 'message_notify',
        ['second' => '<strong><span id="time-box">' . $second . '</span></strong>']) ?></span>
  </div>
  <br/><br/>

  <a href="index.php" style="margin:0 auto; float:none;" class="button show_order_button"><?= lang('Close') ?></a>
</div>
