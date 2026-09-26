<?php

namespace AC\core\modules\reports\actions\reports;

use AC\core\modules\reports\engines\ReportsEngine;
use AC\core\modules\reports\helpers\LayoutReportHelper;
use AC\core\system\helpers\DateHelper;
use AC\core\system\helpers\ObjectHelper;

abstract class Report implements IReport
{
  protected string $method   = 'get';
  protected string $target   = '_black';
  protected string $template = 'form';
  protected string $page_key = 'report';
  
  public function form(): string
  {
    return view()->render($this->template, [
      'form'      => ObjectHelper::createObject([
        'action' => $this->actionForm(),
        'method' => $this->method,
        'target' => $this->target,
      ], true),
      'tableForm' => ObjectHelper::createObject([
        'caption' => $this->captionTableForm(),
        'rows'    => $this->rows(),
        'actions' => $this->actions(),
      ], true),
      'data'      => $this->additionalData(),
    ]);
  }
  
  protected function additionalData(): array
  {
    return [];
  }
  
  abstract protected function rows(): array;
  
  abstract protected function actionForm(string $url = ''): string;
  
  abstract protected function captionTableForm(string $title = ''): string;
  
  abstract protected function actions(): string;
  
  protected function date(array &$data, string $title, array $params = []): void
  {
    $names = $values = $currents = [];
    $this->yearsData($names, $values, $currents, $params);
    $this->monthsData($names, $values, $currents, $params);
    $this->daysData($names, $values, $currents, $params);
    $this->addRow(
      $title,
      LayoutReportHelper::selectTable($names, $values, $currents),
      $data);
  }
  
  protected function yearsData(array &$names, array &$values, array &$currents, array $params = []): void
  {
    if (!empty($params['year']) || in_array('year', $params)) {
      $year       = $params['year'] ?? [];
      $values[]   = DateHelper::yearsAsArray($year['start'] ?? $this->minYear(), $year['finish'] ?? DateHelper::addYear(1));
      $names[]    = $year['name'] ?? 'year';
      $currents[] = $year['current'] ?? (int)date('Y');
    }
  }
  
  protected function monthsData(array &$names, array &$values, array &$currents, array $params = []): void
  {
    if (!empty($params['month']) || in_array('month', $params)) {
      $month      = $params['month'] ?? [];
      $values[]   = DateHelper::monthsAsArray($month['start'] ?? 1, $month['finish'] ?? 12);
      $names[]    = $month['name'] ?? 'month';
      $currents[] = $month['current'] ?? (int)date('m');
    }
  }
  
  protected function daysData(array &$names, array &$values, array &$currents, array $params = []): void
  {
    if (!empty($params['day']) || in_array('day', $params)) {
      $day        = $params['day'] ?? [];
      $values[]   = DateHelper::daysAsArray($day['start'] ?? 1, $day['finish'] ?? 31);
      $names[]    = $day['name'] ?? 'day';
      $currents[] = $day['current'] ?? (int)date('d');
    }
  }
  
  protected function minYear(): int
  {
    return (int)date('Y', strtotime('-4 years'));
  }
  
  protected function addRow(string $name, string $value, array &$data): void
  {
    $data[] = ObjectHelper::createObject(['title' => $name, 'value' => $value], true);
  }
  
  protected function getEngine(): ReportsEngine
  {
    return getEngine('reports', false);
  }
}