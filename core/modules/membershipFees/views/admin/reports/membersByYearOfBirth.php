<?php

/**
 * @var string $title
 * @var string $dateTime
 * @var array  $data
 */

?>
<div id="main-export">
  <div id="top">
    <h1>
      <div style="float: right"><?= $dateTime ?></div>
      <div style="width: 50%"><?= $title ?></div>
    </h1>
  </div>
  <div><h2 style="text-align: left"><?= lang('Member data', 'membership_fees_reports') ?></h2></div>
  <div class="periods">
    <?= $this->render('table', ['data' => $data])?>
  </div>
</div>