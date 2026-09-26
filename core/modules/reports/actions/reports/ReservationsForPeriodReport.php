<?php

namespace AC\core\modules\reports\actions\reports;

use AC\app\entities\enums\ModeClient;
use AC\app\services\DataService;
use AC\core\modules\reports\helpers\DataRenderReportHelper;

class ReservationsForPeriodReport extends ReservationReport
{
  protected string $page_key = 'report_reservations_for_period';
  
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
    $rows = $data['rows'];
    $data['rows'] = [];
    foreach ($rows as $row) {
      $keyTypeSport = $row['area']->typeSportAsString();
      $keyAreaTime  = $row['area']->areaId . '|' . $row['time'];
      
      $data['rows'][$keyTypeSport][$row['date']][$keyAreaTime] = [
        'eventTitle' => DataRenderReportHelper::getEventTitleData($row),
        'date_time'  => $row['time'],
        'client'     => $row['client_name'] . ' (' . ModeClient::from($row['client_mode'])->label() . ')',
        'optional'   => DataRenderReportHelper::getOptionalData($row),
        'club_state' => $row['club_state'],
        'prices'     => DataRenderReportHelper::getPricesData($row),
        'encash'     => $row['encash'],
      ];
    }
    
    ksort($data['rows']);
  }
  
  /**
   * @return string
   */
  public function getTemplate(): string
  {
    return 'period';
  }
}