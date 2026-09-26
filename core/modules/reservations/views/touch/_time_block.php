<?php
/**
 * @var string $date         - дата
 * @var string $weekday      - день недели
 * @var array $titles_times - временные промежутки
 */



?>
<div class="reservation-time"><?= $date . ', ' . $weekday ?>
  <?php foreach ($titles_times as $time) { ?>
    <p class="resh1-time time-reserv"><?= $time . ' ' . lang('hour_lang_out') ?></p>
  <?php } ?>
</div>