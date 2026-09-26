<?php

use AC\core\system\db\DB;

if (Service::request()->_get('run')) {
  $base     = DB::instance('base');
  $baseDBO  = $base->getDbo();
  $path     = __DIR__ . '\data\\';
  $filename = realpath(pathAs($path . 'sites.csv'));
  $csv      = '';
  if ($filename && ($handle = fopen($filename, 'rb')) !== false) {
    // Читаем заголовок (первую строку)
    $headers = fgetcsv($handle, 1000, ';', '"', '\\');
    $data    = [];
    $csv     .= implode(';', $headers) . "\n";
    
    // Читаем оставшиеся строки
    while (($row = fgetcsv($handle, 1000, ';', '"', '\\')) !== false) {
      $tableName = '`' . $row[1] . '`.`' . $row[2] . 'areas`';
      $q         = 'SELECT MAX(light_on) AS L, MAX(heating_on) AS H, MAX(net_on) AS N FROM ' . $tableName;
      if (($state = $baseDBO->query($q, [], true, ['onlyOnce' => 1])) && isset($state[0])) {
        $row[3] = $state[0]['L'] ? 1 : '';
        $row[4] = $state[0]['H'] ? 1 : '';
        $row[5] = $state[0]['N'] ? 1 : '';
      }
      $csv .= implode(';', $row) . "\n";
    }
    fclose($handle);
//    Debug()::saveF($csv, ROOT_PATH. '/logs/', 'new_sites.csv', 0);
  }
}