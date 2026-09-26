<?php
/**
 * @var string $date         - дата
 * @var string $weekday      - день недели
 * @var array  $titles_times - временные промежутки
 */



?>
<span class="f24"><?= $date . ', ' . $weekday ?></span><br/>
<?php foreach ($titles_times as $time) { ?>
  <span class="f24">
        <div class="time-reserv" style="display:inline"><?= $time . ' ' . lang('clock') ?></div>
     </span><br/>
<?php } ?>
