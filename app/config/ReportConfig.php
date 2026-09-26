<?php

namespace AC\app\config;

use AC\core\system\config\BaseConfig;

class ReportConfig extends BaseConfig
{
  protected $listAgeMembers
    = [
      '_6',
      '7_14',
      '15_18',
      '19_26',
      '27_40',
      '41_60',
      '60_',
    ];

  public function getAgeMembers($as = 'all')
  {
    $out = [];
    foreach ($this->listAgeMembers as $keyListAgeMember) {
      $list = match ($as) {
        'age' => $this->getTitleAge($keyListAgeMember),
        'year' => $this->getTitleAge($keyListAgeMember, true),
        default => ['year' => $this->getTitleAge($keyListAgeMember, true), 'age' => $this->getTitleAge($keyListAgeMember)]
      };

      $out[$keyListAgeMember] = $list;
    }

    return $out;
  }

  public function checkKeylistAgeMembers($key, &$age = '', $ageAsYear = false, $binary = false)
  {
    $keys = explode('_', $key);
    if (empty($keys[0]) && !empty($keys[1])) {
      $age = $ageAsYear ? (date('Y') - $keys[1]) : $keys[1];

      return $binary ? -1 : 'after';
    } elseif (!empty($keys[0]) && empty($keys[1])) {
      $age = $ageAsYear ? (date('Y') - $keys[0]) : $keys[0];

      return $binary ? 1 : 'before';
    } else {
      $age = ($ageAsYear ? (date('Y') - $keys[1]) : $keys[0]) . '-' . ($ageAsYear ? (date('Y') - $keys[0]) : $keys[1]);

      return $binary ? 0 : 'list';
    }
  }

  public function getTitleAge($keyListAgeMember, $ageAsYear = false)
  {
    $prefix = $this->checkKeylistAgeMembers($keyListAgeMember, $age, $ageAsYear);

    return lang(($ageAsYear ? 'years' : 'age') . '_' . $prefix, 'membership_fees_reports', ['year' => $age]);
  }

  public function checkTheYearOfBirthByAgeRange($year, $range): bool
  {
    $range = is_string($range) ? explode('_', $range) : $range;
    $age = date('Y') - $year;
    if (empty($range[0]) && !empty($range[1]) && $range[1] >= $age) {

      return true;
    } elseif (!empty($range[0]) && empty($range[1]) && $range[0] <= $age) {

      return true;
    } elseif($range[1] >= $age && $range[0] <= $age) {

      return true;
    }

    return false;
  }
}