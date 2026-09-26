<?php

namespace AC\core\modules\reports\helpers;

use AC\core\system\helpers\ObjectHelper;

class LayoutReportHelper
{
  public static function buttonExecute(): string
  {
    return useLayout()->render('submit', [
      'field' => ObjectHelper::createObject([
        'name'  => 'action',
        'value' => lang('Execute'),
        'class' => 'button',
      ], true)
    ], 'common');
  }
  
  public static function buttonCSV(): string
  {
    return useLayout()->render('submit', [
      'field' => ObjectHelper::createObject([
        'name'  => 'action',
        'value' => 'CSV',
        'class' => 'button',
      ], true)
    ], 'common');
  }
  
  public static function radioTable(string $name, array $values, string $current): string
  {
    return useLayout()->render('reports/radioTable', [
      'field' => ObjectHelper::createObject([
        'name'    => $name,
        'values'  => $values,
        'current' => $current,
      ], true)
    ]);
  }
  
  public static function selectTable(array $names, array $values, array $currents): string
  {
    return useLayout()->render('reports/selectTable', [
      'field' => ObjectHelper::createObject([
        'name'    => $names,
        'values'  => $values,
        'current' => $currents,
      ], true)
    ]);
  }
  
}