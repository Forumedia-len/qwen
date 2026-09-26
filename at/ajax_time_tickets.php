<?php

//запрет кеширования
use AC\core\system\helpers\TimeHelper;

use AC\core\engines\Engines;



//время работы площадки
$options_times_start = $options_times_finish = '';
$area_id             = Service::request()->_('area_id');
$time_start          = str_replace('"', '', Service::request()->_('time_start', '12:00'));
$time_finish         = str_replace('"', '', Service::request()->_('time_finish', '12:00'));
if ($area_id !== null) {
  $r = new Engines();

  //находим время работы площадке - общее т.е минимум и максимум
  $start  = 1440;
  $finish = 0;
  if ($r->areas->getAreaData($area_id, $area_data) && $r->areas->getAreaFullTimeTable($area_id, $timetable)) {
    foreach ($timetable as $time) {
      $start  = min($start, TimeHelper::convertMySQLTimeToMinutes($time[0]));
      $finish = max($finish, TimeHelper::convertMySQLTimeToMinutes($time[1]));
    }
  }

  for ($i = $start; $i <= $finish; $i = $i + $area_data['period']) {
    $tmp                  = TimeHelper::convertMinutes2MySQLTime($i);
    if($i !== $finish) {
      $options_times_start  .= '<option value="' . $tmp . '"' . ($tmp == $time_start ? ' selected'
          : '') . '>' . $tmp . '</option>' . "\n";
    }
    if($i !== $start) {
      $options_times_finish .= '<option value="' . $tmp . '"' . ($tmp == $time_finish ? ' selected'
          : '') . '>' . $tmp . '</option>' . "\n";
    }
  }
}

echo '<table border="0" cellspacing="3" cellpadding="0">' . "\n";
echo '<tr>' . "\n";
echo '<td>'.lang('From').':</td>' . "\n";
echo '	<td>' . "\n";
echo '		<select name="time_start" class="input">' . "\n";
echo $options_times_start;
echo '		</select>' . "\n";
echo '	</td>' . "\n";
echo '	<td rowspan="2"><a href="javascript:void null" onclick="var a = document.forms[\'insertTicket\'];a.time_finish.value = a.time_start.value"><img src="' . base_url(paths()->getAssetsDir('images/copy.gif')) . '" alt="'.lang('Copy').'" border="0"></a></td>' . "\n";
echo '</tr>' . "\n";
echo '<tr>' . "\n";
echo '	<td>'.lang('Until').':</td>' . "\n";
echo '	<td>' . "\n";
echo '		<select name="time_finish" class="input">' . "\n";
echo $options_times_finish;
echo '			</select>' . "\n";
echo '		</td>' . "\n";
echo '	</tr>' . "\n";
echo '</table>' . "\n";
?>