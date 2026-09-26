<?php
/**
 * Reports
 * @var array<string> $reportForms
 */
?>
<h1><?= lang('Reports', 'reports') ?></h1>
<div class="reports-main-block">
  <?php foreach ($reportForms as $reportForm) : ?>
    <div>
      <?= $reportForm ?>
    </div>
  <?php endforeach; ?>
</div>

