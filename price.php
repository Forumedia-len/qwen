<?php

use AC\core\modules\text\engines\TextEngine;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\TranslateHelper;

$_page['key']   = 'price';
$_page['title'] = lang('title', 'price');

$engine = Service::engines();
$engine->areas->getAreasTypesData($areas_type);
$engine->extra->getExtra($extra_data);
$out = '	<div class="content prices-content">' . "\n";
$out .= '	<div class="row">' . "\n";

/** @var TextEngine $txt */
$txt = getEngine('text', false);
$txt?->getContent($row, 'price');
if (!empty($row['content'])) {
  $out .= $row['content'];
} else {
  foreach ($extra_data as $extra) {
    $out .= '<div class="col-md-' . (count($extra_data) > 2 ? 6 : 12) . '">';
    $out .= '<h2>'. lang('prices_for', 'price'). ' ' . $extra->title . ':</h2>';
    $out .= '<div class="prices-type-block">';
    $engine->areas->getAreasTimeTableByType($extra->type, $tts, $prices);
    foreach ($prices as $weekdays) {
      $out .= '<h3>';
      $out .= TranslateHelper::translateWeekday($weekdays[0]);
      if ($weekdays[0] != $weekdays[1]) {
        $out .= ' - ' . TranslateHelper::translateWeekday($weekdays[1]);
      }
      $out .= '</h3>';

      $out .= "<table class='prices-table' cellspacing=\"6\">";
      $out .= '<tr>';
      $out .= '<th class="prices-table-time">&nbsp;</th>';
      foreach ($extra->rate as $rate) {
        $out .= '<th>' . $rate->title . '</th>';
      }
      $out .= '</tr>';
      foreach ($weekdays[2] as $period) {
        $out .= '<tr><td class="prices-table-time">' . $period[0] . ' - ' . $period[1] . '</td>';
        foreach ($extra->rate as $rate) {
          $out .= '<td>' . NumberHelper::valute($period[2] + $rate->rate) . '</td>';
        }
        $out .= '</tr>';
      }
      $out .= "</table>";
    }
    $out .= "</div>";
    $out .= "</div>";
  }
}
$out                 .= '	</div>' . "\n";
$out                 .= '	</div>' . "\n";
$_page['content'][1] = $out;