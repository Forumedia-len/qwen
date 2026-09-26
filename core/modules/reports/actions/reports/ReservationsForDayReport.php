<?php

namespace AC\core\modules\reports\actions\reports;


use AC\core\modules\reports\actions\outputRender\TwoColumnRender;

class  ReservationsForDayReport extends ReservationReport
{
  protected string $page_key = 'report_reservations_for_day';
  
  protected function rows(): array
  {
    $rows = [];
    $this->date($rows, lang('Date'), ['year', 'month', 'day']);
    $this->sportsByTypes($rows, lang('Place'));
    $this->eventMode($rows, lang('Type'));
    
    return $rows;
  }
  
  protected function getDateFields(): array
  {
    return [
      'start' => [
        'year'  => ['name' => 'year'],
        'month' => ['name' => 'month'],
        'day'   => ['name' => 'day'],
      ],
      'end'   => [
        'year'  => ['name' => 'year'],
        'month' => ['name' => 'month'],
        'day'   => ['name' => 'day'],
      ],
    ];
  }
  
  protected function getRenderDataForView(array &$data): void
  {
    parent::getRenderDataForView($data);
    (new TwoColumnRender())->renderOutputs($data);
  }
  
  protected function getDateTitle($date_start, $date_finish = null): string
  {
    return date('d/m/Y', strtotime($date_start));
  }
  
  /**
   * @return string
   */
  public function getTemplate(): string
  {
    return 'two-columns';
  }
}