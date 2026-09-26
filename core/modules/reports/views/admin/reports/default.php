<?php

/**
 * @var array  $data
 */
?>
<div id="main-export">
  <div id="top">
    <h1>
      <?= $data['title'] ?>
      <br>
      <?= $data['dateTitle'] ?>
    </h1>
  </div>
  <div class="periods">
    <?= $this->render('table', ['data' => $data])?>
  </div>
</div>