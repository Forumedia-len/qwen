<?php
/**
 * @var AreasModel $area_data - данные площадки
 * @var string     $date      - (дата , день недели , временные промежутки)
 * @var array      $times     - массив промежутков времени
 * @var object     $sum       - общаяя стоимость
 * @var int        $page      - номер страницы
 *
 */

use AC\core\modules\areas\models\AreasModel;

$escapeOrder = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<input type="hidden" name="action" value="proceedOrder"/>
<input type="hidden" name="type_id" value="<?= $escapeOrder($area_data->type_id) ?>"/>
<input type="hidden" name="sport_id" value="<?= $escapeOrder($area_data->sport_id) ?>"/>
<input type="hidden" name="area_id" value="<?= $escapeOrder($area_data->area_id) ?>"/>
<input type="hidden" name="sum_price" value="<?= $escapeOrder($sum->sum_price) ?>"/>
<input type="hidden" name="date" value="<?= $escapeOrder($date) ?>"/>
<input type="hidden" name="page" value="<?= $escapeOrder($page) ?>"/>
<?php foreach ($times as $time) { ?>
  <input type="hidden" name="time[<?= $escapeOrder($time) ?>]" value="1"/>
<?php } ?>
