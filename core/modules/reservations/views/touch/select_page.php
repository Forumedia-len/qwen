<?php
/**
 * @var int               $type_id
 * @var int             $sport_id
 * @var array           $pages
 */
?>
<div id="main">
  <div id="areasMenu" class="select-sport-block">
    <?php foreach ($pages as $page) { ?>
      <a href="reservations.php?action=showReservations&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>&page=<?=$page['page']?>" class="bar">
        <span class="<?=strtolower($page['sport_title']) ?>"><?= $page['page_title'] ?></span>
      </a>
    <?php } ?>
  </div>
</div>
