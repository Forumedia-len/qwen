<?php
/**
 * @var array $door_codes
 */
?>
<div style="font-size: 16px;">
  <p><strong><?= lang('important', 'door_code') ?>:</strong></p>
  <?= lang('info_text_door_code_text', 'messages_close') ?>
  <div class="orderItemBox">
    <strong><?= lang('access_code', 'door_code') ?>:</strong>
    <?php foreach ($door_codes as $code) { ?>
      <p class="door-code"><?= $code->time ?> <?= lang('watch', 'door_code') ?>: &nbsp; &nbsp; <span><?= $code->code ?></span></p>
    <?php } ?>
  </div>
</div>

