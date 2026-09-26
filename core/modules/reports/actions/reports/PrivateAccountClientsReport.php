<?php

namespace AC\core\modules\reports\actions\reports;

use AC\core\modules\reports\helpers\LayoutReportHelper;
use AC\core\system\helpers\NumberHelper;
use Service;

class PrivateAccountClientsReport extends ReservationReport
{
  protected string $page_key = 'report_private_account_clients';
  
  protected function rows(): array
  {
    $rows = [];
    $this->addRowYearMonth($rows);
    
    return $rows;
  }
  
  protected function getRenderDataForView(array &$data): void
  {
    $date                = implode('-', [
      Service::request()->_('year'),
      Service::request()->_('month'),
      '01'
    ]);
    $unix_date           = strtotime($date);
    $dateStart           = date('Y', $unix_date) . '-01-01';
    $dateFinish          = date('Y-m', $unix_date) . '-' . date('t', $unix_date);
    $data['date']        = $date;
    $data['date_start']  = $dateStart;
    $data['date_finish'] = $dateFinish;
    $data['dateTitle']   = $this->getDateTitle($date, null);
    $rows                = $this->getEngine()->getPrivateAccountData($dateStart, $dateFinish);
    foreach ($rows as $row) {
      if ($row['income'] > 0 || $row['outcome'] > 0 || $row['prepayment_sum'] > 0) {
        $balance        = $row['income'] - $row['outcome'];
        $data['rows'][] = [
          'login'           => $row['login'],
          'client_surname'  => $row['surname'],
          'client_name'     => $row['name'],
          'income'          => NumberHelper::valute($row['income']),
          'outcome'         => NumberHelper::valute(($row['outcome'])),
          'balance'         => ($balance < 0 ? '-' : '') . NumberHelper::valute(abs($balance)),
          'private_account' => NumberHelper::valute($row['prepayment_sum']),
        ];
      }
    }
    $this->getFieldsAsTitle($data);
  }
  
  private function getFieldsAsTitle(array &$data): void
  {
    $year           = date('Y', strtotime($data['date']));
    $data['fields'] = [
      'title_row' => 1,
      'fields'    => [
        'login'           => ['row' => 1, 'col' => 1, 'title' => lang('User name'), 'parent' => null, 'style' => 'text-align: left;'],
        'client_surname'  => ['row' => 1, 'col' => 1, 'title' => lang('Family name'), 'parent' => null, 'style' => 'text-align: left;'],
        'client_name'     => ['row' => 1, 'col' => 1, 'title' => lang('First name'), 'parent' => null, 'style' => 'text-align: left;'],
        'income'          => [
          'row'    => 1,
          'col'    => 1,
          'title'  => lang('Payments received', 'reports', ['year' => $year]),
          'parent' => null,
          'style'  => 'text-align: right;'
        ],
        'outcome'         => [
          'row'    => 1,
          'col'    => 1,
          'title'  => lang('Payments paid out', 'reports', ['year' => $year]),
          'parent' => null,
          'style'  => 'text-align: right;'
        ],
        'balance'         => [
          'row'    => 1,
          'col'    => 1,
          'title'  => lang('Remaining balance', 'reports', ['year' => $year]),
          'parent' => null,
          'style'  => 'text-align: right;'
        ],
        'private_account' => [
          'row'    => 1,
          'col'    => 1,
          'title'  => lang('Current total balance', 'reports'),
          'parent' => null,
          'style'  => 'text-align: right;'
        ],
      ]
    ];
  }
  
  protected function getDateTitle($date_start, $date_finish = null): string
  {
    return date('m/Y', strtotime($date_start));
  }
  
  protected function actions(): string
  {
    return parent::actions() . LayoutReportHelper::buttonCSV();
  }
}