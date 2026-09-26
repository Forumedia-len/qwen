<?php
/**
 * @var string $date         - дата
 * @var string $weekday      - день недели
 * @var array $titles_times - временные промежутки
 */
?>
<h3 class="reservation-time"><?= $date . ', ' . $weekday ?>
  <?php foreach ($titles_times as $time) { ?>
    <br/>
    <div class="time-reserv" style="display:inline"><?= $time . ' ' . lang('hour_lang_out') ?></div>
  <?php } ?>
</h3>