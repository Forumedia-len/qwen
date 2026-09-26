<?php
/**
 * @var string $form
 * @var string $title
 * @var string $messages
 */

?>
<h1><?= $this->h1 ?></h1>
<div id="content">
  <div class="new_content"><?= $title ?></div>
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
