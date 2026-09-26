<?php
/**
 * @var array $data
 */

use AC\app\entities\enums\Encash;
use AC\app\entities\enums\EventMode;
use AC\app\services\DataService;

$sportsByType = DataService::sportsByType();
/** @var EventMode $eventMode */
$eventMode = $data['eventMode'];
?>
<div id='main'>
  <div id='top'>
    <h1><?= $data['title'] ?><br><b><?= $data['dateTitle'] ?></b></h1>
  </div>
  <?php foreach ($data['rows'] as $typeSport => $dates): ksort($dates); ?>
    <div class='typeTitle'><?= $sportsByType[$typeSport]->title_site_url ?></div>
    <?php foreach ($dates as $date => $periods): ?>
      <div class='client'>
        <div class='title'><?= date('d.m.Y', strtotime($date)) ?></div>
        <div class='periods'>
          <table cellspacing='0' class='periods'>
            <tbody>
            <?php foreach ($periods as $period): ?>
              <tr>
                <td class='a' style='width:20%'><?= $period['eventTitle'] ?></td>
                <td class='p' style='width:20%'><?= $period['date_time'] ?></td>
                <td class='a' style='width:50%'><?= $period['client'] ?></td>
                <td style='width:10%'><?= $period['optional'] ?></td>
                <td style='width:10%'><?= $period['club_state'] ?></td>
                <td class='p<?= $period['encash'] == Encash::Cash ? ' encash' : '' ?>' style='width:10%'><?= $period['prices'] ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endforeach; ?>
</div>