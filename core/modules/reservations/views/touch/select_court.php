<?php

/**
 * @var int   $type_id
 * @var array $sports
 *
 */

$temp_sports = [];
foreach ($sports as $sport) {
  $temp_sports[$sport->sport_id] = $sport;
}
$sports = $temp_sports;
?>
<div id="main">
  <div id="areasMenu" class="select-sport-block">
    <?php foreach ($sports as $sport) { ?>
      <a href="reservations.php?action=selectPage&type_id=<?= $type_id ?>&sport_id=<?= $sport->sport_id ?>" class="bar">
        <span class="<?= strtolower($sport->title) ?>"><?= $sport->title ?></span>
      </a>
    <?php } ?>
  </div>
</div>
