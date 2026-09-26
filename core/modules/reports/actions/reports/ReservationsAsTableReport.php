<?php

namespace AC\core\modules\reports\actions\reports;

use AC\app\services\DataService;
use AC\core\modules\areas\entities\dto\AreaDto;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\helpers\TranslateHelper;

/**
 * Таблица занятости доступных площадок за выбранный период.
 */
class ReservationsAsTableReport extends ReservationReport
{
  protected string $page_key = 'report_reservations_as_table';
  
  protected function rows(): array
  {
    $rows = [];
    $this->date($rows, lang('From'),
      $this->getDateFields()['start'],
    );
    $this->date($rows, lang('Until'),
      $this->getDateFields()['end'],
    );
    $this->sportsByTypes($rows, lang('Place'));
    
    return $rows;
  }
  
  protected function getRenderDataForView(array &$data): void
  {
    parent::getRenderDataForView($data);
    $dataSportsByType = DataService::sportsByType();
    $areas            = DataService::areas();
    if (!empty($data['type_sport']) && isset($dataSportsByType[$data['type_sport']])) {
      $dataSportsByType = [$data['type_sport'] => $dataSportsByType[$data['type_sport']]];
    }
    $mysql_start_date = $data['date_start'];
    $mysql_end_date   = $data['date_finish'];
    $workDays         = CalendarHelper::getWorkDaysByPeriod($mysql_start_date, $mysql_end_date, null, null, 1, [], false);
    $data['titlesDays'] = [];
    $data['titlesTimes'] = [];
    ksort($workDays);
    foreach ($workDays as $date => $weekday) {
      $month = lang('month_' . date('n', strtotime($date)));
      $data['titlesDays'][$month . ' ' . date('Y', strtotime($date))][$date] = TranslateHelper::translateWeekday($weekday, true);
    }
    $data['workDays'] = $workDays;
    $rows = $data['rows'];
    $data['rows'] = [];
    // абоненты и бронирования
    foreach ($rows as $row) {
      $area = $row['area'] ?? null;
      if (!$area instanceof AreaDto) {
        continue;
      }
      foreach ($row['times'] as $time) {
        $timeTitle = TimeHelper::generateTitleByTimeAndPeriod($time, $area->period);
        
        $data['rows'][$area->typeSportAsString()][$area->areaId][$timeTitle][$row['date']] = 'ordered';
      }
    }
    $blocksData = [];
    //блокировки
    foreach ($this->getEngine()->getBlocks($mysql_start_date, $mysql_end_date) as $areaId => $blocks) {
      /** @var AreaDto $area */
      $area      = $areas[$areaId] ?? null;
      if (!$area instanceof AreaDto) {
        continue;
      }
      $typeSport = $area->typeSportAsString();
      if (isset($dataSportsByType[$typeSport])) {
        foreach ($blocks as $block) {
          if (in_array($block['type'], ['periodical', 'unlimited'])) {
            $date       = date('Y-m-d', strtotime($block[0]));
            $timeStart  = date('H:i', strtotime($block[0]));
            $timeFinish = date('H:i', strtotime($block[1]));
            foreach (TimeHelper::generateArrayTimeInIncrements($timeStart, $timeFinish, $area->period, false) as $time) {
              $timeTitle                                            = TimeHelper::generateTitleByTimeAndPeriod($time, $area->period);
              $data['rows'][$typeSport][$areaId][$timeTitle][$date] = 'blocked';
            }
          } else {
            $blocksData[$typeSport][$areaId][] = $block;
          }
        }
      }
    }
    // праздники
    $holidays = [];
    foreach ($this->getEngine()->getHolidays($mysql_start_date, $mysql_end_date) as $holidayData) {
      $holidays[] = date('Y-m-d', strtotime($holidayData[0]));
    }
    foreach ($dataSportsByType as $typeSport => $dataSportByType) {
      /** @var AreaDto $area */
      foreach ($dataSportByType->areas as $area) {
        $workTimes = $area->getWorkingHours();
        foreach ($workDays as $date => $weekday) {
          foreach (array_keys($workTimes[$weekday] ?? []) as $time) {
            $timeTitle = TimeHelper::generateTitleByTimeAndPeriod($time, $area->period);
            $data['titlesTimes'][$typeSport][$area->areaId][$timeTitle] ??= 1;
            $data['rows'][$typeSport][$area->areaId][$timeTitle][$date] = $data['rows'][$typeSport][$area->areaId][$timeTitle][$date] ?? '';
            if (in_array($date, $holidays)) {
              $data['rows'][$typeSport][$area->areaId][$timeTitle][$date] = 'blocked';
            } else {
              
              foreach ($blocksData[$typeSport][$area->areaId] ?? [] as $block) {
                if (strtotime($block[0]) <= strtotime($date . ' ' . $time) && strtotime($block[1]) >= strtotime($date . ' ' . $time)) {
                  $data['rows'][$typeSport][$area->areaId][$timeTitle][$date] = 'blocked';
                }
              }
            }
          }
        }
      }
    }
    ksort($data['titlesTimes']);
  }
  
  /**
   * @return string
   */
  public function getTemplate(): string
  {
    return 'table';
  }
}
