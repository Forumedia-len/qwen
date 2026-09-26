<?php
/**
 * @var string $form
 * @var string $title
 * @var string $messages
 */

?>
<div id="content">
  <?= $messages ?>
  <div>
    <?= $form ?>
  </div>
</div>
<script>
  $(document).ready(function () {
    $('#right-info-link').colorbox({iframe: true, width: '80%', height: '80%'})
  })
</script>
