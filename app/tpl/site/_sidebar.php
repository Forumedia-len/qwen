<?php

use AC\core\modules\text\engines\TextEngine;
use AC\core\system\db\Query;
use AC\core\system\helpers\TranslateHelper;


?>

<!--<div class="sidebar col-lg-2 col-lg-pull-10 col-md-3 col-md-pull-9">-->
<div class="sidebar">
  <?php if (isset($_page['content'][0])) {
    echo $_page['content'][0];
  } else { ?>
    <div class="sidebar-block">
      <h2 class="sidebar-block-title"><?= lang('kontakt') ?></h2>
      <?php
      /** @var TextEngine $txt */
      $txt = getEngine('text', false);
      if ($txt?->getContent($row, 'address_home')) { ?>
        <div class="sidebar-block-content">
          <?= $row['content'] ?>
        </div>
      <?php } ?>
    </div>
    <?php if (SHOW_OPENING_HOURS) { ?>
      <div class="sidebar-block">
        <h2 class="sidebar-block-title"><?= lang('opening_hours') ?></h2>
        <div class="sidebar-block-content">
          <?php if ($txt?->getContent($row, 'working_time') && !empty($row['content'])) { ?>
            <?= $row['content'] ?>
          <?php } else {
            //общее расписание работы клуба - по минимальному и максимальному рабочему времени всех площадок

            $rows = Query::sqlQuery(
              'select weekday, substring(min(start),1,5),substring(max(finish),1,5)
	from ' . Query::tableName('areas_timetables') . '
	group by weekday
	order by weekday', [],
              true,
              ['style' => PDO::FETCH_NUM]
            );

            $tmp = '';
            reset($rows);
            do {
              $first = current($rows);
              do {
                $n = next($rows);
              } while ($n && $first[1] == $n[1] && $first[2] == $n[2]);
              $last = $n === false ? end($rows) : prev($rows);
              $tmp  .= '<p>' . TranslateHelper::translateWeekday($first[0]) .
                ($first[0] != $last[0] ? ' - ' . TranslateHelper::translateWeekday($last[0]) : '')
                . '<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $first[1] . ' - ' . $last[2] . ' ' . lang('clock') . '&nbsp;</p>' . "\n";
            } while (next($rows));
            print substr($tmp, 0, -6);
          }
          ?>
        </div>
      </div>
    <?php } ?>
  <?php } ?>
  <?php if ((!defined('VIEW_BANNER_ACTIVE_COURT') || VIEW_BANNER_ACTIVE_COURT) && !MC_ARENA) { ?>
    <div class="ac-banner-block">
      <a href="http://www.active-court.de/" target="_blank">
        <img style="width: 100%; max-width: 320px" src="<?= base_url(paths()->getAssetsDir('images/bilder/active_court_banner.gif', 'common')) ?>"
             alt="<?= lang('tennis_reservation_online', 'sidebar') ?>">
      </a>
    </div>
  <?php } ?>
  <a style="cursor: pointer;"
     onclick="window.open('<?= site_url() ?>user_info.php','user_right','height=500,width=600');" class="conf-link">
    <u><?= (MC_ARENA) ? lang('privacy_arena', 'sidebar') : lang('privacy', 'sidebar'); ?> </u></a>

</div>
