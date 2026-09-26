<?php
/**
 * @var array $data
 */

use AC\app\entities\enums\EventMode;
use AC\app\services\DataService;
use AC\core\modules\reports\entities\enums\ModeClientByPayment;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\TimeHelper;

$sportsByType = DataService::sportsByType();
/** @var EventMode $eventMode */
$eventMode  = $data['eventMode'];
$workDays   = array_keys($data['workDays']);
$titleMonth = $titleDays = $titleWeekdays = '';
foreach ($data['titlesDays'] as $month => $dates) {
  $titleMonth .= '<th colspan="' . count($dates) . '"><span style="display: block;border-right: 1px solid #fff">' . $month . '</span></th>';
  foreach ($dates as $date => $weekday) {
    $titleDays     .= '<th>' . date('d', strtotime($date)) . '</th>';
    $titleWeekdays .= '<th>' . $weekday . '</th>';
  }
}
?>
<div id='main'>
  <div id='top'><h1><?= $data['title'] ?><br><b><?= $data['dateTitle'] ?></b></h1></div>
  <?php foreach ($data['titlesTimes'] as $typeSport => $areas) :
    ksort($areas);
    foreach ($areas as $areaId => $times) :
      ksort($times);
      $area = $sportsByType[$typeSport]->areas[$areaId]; ?>
      <h3><?= $sportsByType[$typeSport]->title_site_url ?> - <?= $area->title ?></h3>
      <table cellspacing='0' cellpadding='3' class='orders_table'>
        <tbody>
        <tr>
          <td rowspan='3'>&nbsp;</td>
          <?= $titleMonth ?>
        </tr>
        <tr>
          <?= $titleDays ?>
        </tr>
        <tr>
          <?= $titleWeekdays ?>
        </tr>
        <?php foreach (array_keys($times) as $time): ?>
          <tr>
            <th>
              <div style='min-width: 80px'><?= $time ?></div>
            </th>
            <?php foreach ($workDays as $date):
              $valueClass = $data['rows'][$typeSport][$areaId][$time][$date] ?? null;
              $class = !isset($valueClass) || $valueClass !== '' ? ' class="period_' . ($valueClass ?? 'unavaliable') . '"' : ''; ?>
              <td<?= $class ?>>&nbsp;</td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endforeach; ?>
  <?php endforeach; ?>
  <p>&nbsp;</p>
</div>
