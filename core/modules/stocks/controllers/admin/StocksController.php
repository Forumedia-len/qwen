<?php

namespace AC\core\modules\stocks\controllers\admin;

use AC\app\controllers\AdminController;
use AC\core\engines\StocksEngine;
use AC\core\modules\stocks\entities\dto\StockDto;
use AC\core\system\helpers\LayoutHelper;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use Service;

class StocksController extends AdminController
{
  public function show()
  {
    return [$this->list(), $this->form(StockDto::fromArray([]))];
  }
  
  public function list()
  {
    $stocks        = [];
    $useConditions = false;
    if ($this->stockEngine()?->getStocks($stocks)) {
      foreach ($stocks as $key => $stock) {
        $stocks[$key] = StockDto::fromArray($stock);
        if (count($stocks[$key]->conditions)) {
          $useConditions = true;
        }
      }
      $out = $this->render('list', [
        'stocks'        => $stocks,
        'useConditions' => $useConditions
      ]);
    } else {
      $out = '<p>' . lang('No data') . '</p>';
    }
    
    return $out;
  }
  
  public function edit()
  {
    if (($stockId = Service::request()->_('stockId'))
      && $this->stockEngine()?->getStock($stockId, $item) && $item) {
      return [$this->form(StockDto::fromArray($item)), $this->backButton()];
    }
    
    return $this->redirectDefaultAction($this->getDefaultUrl());
  }
  
  protected function form(?StockDto $item)
  {
    $sports = ModCommHelper::get('areas', 'areas/relevantSportsByTypeAsSelect', [
      'current'    => $item->typeSport ?? null,
      'all_sports' => config('stocks')->useForAllSorts()
    ], 'sports_by_type', '');
    $groups = [];
    getEngine('stocksGroups')?->getData($groups);
    
    return $this->render('_form', [
      'stock'  => $item,
      'sports' => $sports,
      'groups' => $groups
    ]);
  }
  
  function editTime()
  {
    $stockId = (int)Service::request()->_get('stockId');
    if (!$stockId || !$this->stockEngine()?->getStock($stockId, $item)) {
      return $this->redirectDefaultAction($this->getDefaultUrl());
    }
    if ($areas_data = ModCommHelper::get('areas', 'areas/getAreas', ['as' => 'array'], 'areas')) {
      $prev = $finish = 0;
      $interval = 60;
      foreach ($areas_data as $area) {
        if (!empty($item['type_sport']) && $item['type_sport'] !== $area['type_id'] . '_' . $area['sport_id']) {
          continue;
        }
        $interval = min($interval, $area['period']);
        $prev     = !$prev || $area['start'] < $prev ? $area['start'] : $prev;
        $finish   = !$finish || $area['finish'] > $finish ? $area['finish'] : $finish;
      }
      $prev   = substr($prev, 0, 5);
      $finish = substr($finish, 0, 5);
      
      $startTimestamp  = isset($item['duration_start']) ? strtotime($item['duration_start']) : time();
      $minYearStart    = (int)(date('Y', $startTimestamp) < date('Y') - 1 ? date('Y', $startTimestamp) : date('Y') - 1);
      $finishTimestamp = isset($item['duration_finish']) ? strtotime($item['duration_finish']) : time();
      $minYearFinish   = (int)(date('Y', $finishTimestamp) < date('Y') - 1 ? date('Y', $finishTimestamp) : date('Y') - 1);
      $out             = $this->render('time_form', [
        'stock'      => StockDto::fromArray($item),
        'startData'  => [
          'years'  => LayoutHelper::getSelectYears($minYearStart, (int)date('Y') + 3, date('Y', $startTimestamp), 'start_year'),
          'months' => LayoutHelper::getSelectMonths(1, 12, date('m', $startTimestamp), 'start_month'),
          'days'   => LayoutHelper::getSelectDays(1, 31, date('d', $startTimestamp), 'start_day')
        ],
        'finishData' => [
          'years'  => LayoutHelper::getSelectYears($minYearFinish, (int)date('Y') + 3, (int)date('Y', $finishTimestamp), 'finish_year'),
          'months' => LayoutHelper::getSelectMonths(1, 12, date('m', $finishTimestamp), 'finish_month'),
          'days'   => LayoutHelper::getSelectDays(1, 31, date('d', $finishTimestamp), 'finish_day')
        ],
        'prev'       => $prev,
        'finish'     => $finish,
        'interval'   => $interval,
      ]);
      
      return [$out, $this->backButton()];
    }
    
    return $this->redirectDefaultAction($this->getDefaultUrl());
  }
  
  function changeTime()
  {
    if (($stockId = (int)Service::request()->_post('stockId')) && $this->stockEngine()->getStock($stockId, $item)) {
      $postData                    = Service::request()->_post();
      $postData['duration_start']  = $postData['start_year'] . '-' . $postData['start_month'] . '-' . $postData['start_day'];
      $postData['duration_finish'] = $postData['finish_year'] . '-' . $postData['finish_month'] . '-' . $postData['finish_day'];
      $postData['duration']        = isset($postData['active']) ? 1 : 0;
      $item                        = array_merge($item, $postData);
      $stock                       = StockDto::fromArray($item);
      if (getEngine('stocks', false)?->changeStockDuration($stock->stockId, (int)$stock->duration, $stock->durationStart, $stock->durationFinish,
        $stock->durations)) {
        $this->view->addMessage(
          lang('message_element_base_update', 'message_success'),
          'success'
        );
      }
    }
    
    return $this->redirectDefaultAction($this->getDefaultUrl());
  }
  
  /**
   * Функция для получения списка опций в виде массива
   *
   * @param $selected
   * @param $name
   * @param $class
   * @param $multiple
   * @return string
   */
  public function getStocksAsSelect($selected = [], $name = 'stocks', $class = ' ', $multiple = true): string
  {
    $values = [];
    if (empty($selected)) {
      foreach ($this->getSegmentByKey('selectedStocks') as $stock) {
        $selected[] = $stock->{$this->getModel('stocks')?->getPrimaryKey()};
      }
    }
    foreach ($this->stocksAsArray() as $item) {
      $values[$item['stock_id']] = $item['title'] . ' : ' . NumberHelper::format($item['rate']) . ' ' . CURR_VALUTE;
    }
    return useLayout()::render('select', [
      'name'     => $name . ($multiple ? '[]' : ''),
      'values'   => $values,
      'class'    => $class,
      'multiple' => $multiple,
      'size'     => $multiple ? 5 : null,
      'current'  => $selected
    ], 'common');
  }
  
  public function stocksAsArray(): array
  {
    $stocks = [];
    $this->model->getEngine()->getStocks($stocks);
    
    return $stocks;
  }
  
  protected function stockEngine(): StocksEngine
  {
    return getEngine('stocks', false);
  }
}