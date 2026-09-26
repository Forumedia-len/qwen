<?php

namespace AC\core\modules\tickets\entities\entity\html\table;

use AC\app\helpers\LayoutHelper;
use AC\core\system\helpers\HtmlHelper;
use AC\core\system\object\entity\html\Table;
use AC\core\system\object\entity\html\table\RowTable;
use Service;

class RowTableDate extends RowTable
{
  public string $idForm = 'insertTicket';

  public function generateItems($data = [], $new = true, $classItems = null): RowTableDate
  {
    if (!isset($data['start_year']) && !isset($data['start_month']) && !isset($data['start_day']) && !isset($data['finish_year']) && !isset($data['finish_month']) && !isset($data['finish_day'])) {
      [$data['start_year'], $data['start_month'], $data['start_day']] = explode('-', date('Y-m-d', strtotime($data['period_start'])));
      [$data['finish_year'], $data['finish_month'], $data['finish_day']] = explode('-', date('Y-m-d', strtotime($data['period_finish'])));
    }
    if ($new) {
      $this->addItem(
        HtmlHelper::tagTdTh('td', null, null,
          [
            'content' => lang('Date') . ':',
            'class'   => $classItems
          ],
        )
      );
      $this->addItem(
        HtmlHelper::tagTdTh('td', null, null,
          [
            'content' => $this->generateDateTable($data)->asString(),
            'class'   => $classItems,
          ]
        )
      );
    }

    return parent::generateItems();
  }

  protected function generateDateTable($data = []): Table
  {
    $table        = HtmlHelper::table();
    $table->style = 'border:none;cell-spacing:0;cell-content:3;';
    $table
      ->addRow(HtmlHelper::tagTr()
        ->addItem(HtmlHelper::tagTdTh('td', null, null, ['content' => lang('From') . ':']))
        ->addItem(HtmlHelper::tagTdTh('td', null, null, [
          'content' => LayoutHelper::getSelectYears((int)date('Y') - 1, (int)date('Y') + 1,
            $this->getParamDate('start_year', 's_year',date('Y'), $data), 's_year')
        ]))
        ->addItem(HtmlHelper::tagTdTh('td', null, null, ['content' => '.']))
        ->addItem(HtmlHelper::tagTdTh('td', null, null, [
          'content' => LayoutHelper::getSelectMonths(1, 12,
            $this->getParamDate('start_month','s_month', date('m'), $data), 's_month', true)
        ]))
        ->addItem(HtmlHelper::tagTdTh('td', null, null, ['content' => '.']))
        ->addItem(HtmlHelper::tagTdTh('td', null, null, [
          'content' => LayoutHelper::getSelectDays(1, 31,
            $this->getParamDate('start_day','s_day', date('d'), $data), 's_day')
        ]))
        ->addItem(HtmlHelper::tagTdTh('td', null, null,
          [
            'content' => HtmlHelper::tag('a', null, null,
              [
                'content' => HtmlHelper::img(null, null,
                  ['src' => base_url(paths()->getAssetsDir('images/copy.gif')), 'alt' => 'Kopieren', 'style' => 'border: none'])->asString()
              ])
              ->addOtherAttribute('href', 'javascript:void null')
              ->addOtherAttribute('onclick', 'blocks_copyPeriodDate (document.forms[\'' . $this->idForm . '\']);')
              ->asString(),
            'rowspan' => 2
          ]))
      )
      ->addRow(HtmlHelper::tagTr()
        ->addItem(HtmlHelper::tagTdTh('td', null, null, ['content' => lang('Until') . ':']))
        ->addItem(HtmlHelper::tagTdTh('td', null, null, [
          'content' => LayoutHelper::getSelectYears((int)date('Y') - 1, (int)date('Y') + 1,
            $this->getParamDate('finish_year','f_year', date('Y'), $data), 'f_year')
        ]))
        ->addItem(HtmlHelper::tagTdTh('td', null, null, ['content' => '.']))
        ->addItem(HtmlHelper::tagTdTh('td', null, null, [
          'content' => LayoutHelper::getSelectMonths(1, 12,
            $this->getParamDate('finish_month','f_month', date('m'), $data), 'f_month', true)
        ]))
        ->addItem(HtmlHelper::tagTdTh('td', null, null, ['content' => '.']))
        ->addItem(HtmlHelper::tagTdTh('td', null, null, [
          'content' => LayoutHelper::getSelectDays(1, 31,
            $this->getParamDate('finish_day','f_day', date('d'), $data), 'f_day')
        ])));

    return $table;
  }

  protected function getParamDate($nameCookie, $namePost ,$default, $data = [])
  {
    return $_COOKIE[$nameCookie] ?? Service::request()->findByAlias($namePost, $data, $default, 'post');
  }

  public function setIdForm(string $idForm): RowTableDate
  {
    $this->idForm = $idForm;
    return $this;
  }
}