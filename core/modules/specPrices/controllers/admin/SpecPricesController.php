<?php

namespace AC\core\modules\specPrices\controllers\admin;

use AC\app\controllers\AdminController;
use AC\core\modules\specPrices\engines\SpecPricesEngine;
use AC\core\modules\specPrices\engines\SpecPricesTriggerConditionsEngine;
use AC\core\modules\specPrices\entities\dto\SpecPriceDto;
use AC\core\system\helpers\LayoutHelper;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use Service;

class SpecPricesController extends AdminController
{
  public function show()
  {
    return [$this->list(), $this->form(SpecPriceDto::fromArray([]))];
  }
  
  public function list()
  {
    if ($specPrices = $this->specPricesEngine()->getSpecPricesAs('dto')) {
      $out = $this->render('list', ['specPrices' => $specPrices]);
    } else {
      $out = '<p>' . lang('No data') . '</p>';
    }
    
    return $out;
  }
  
  public function edit()
  {
    if (($specPriceId = Service::request()->_('specPriceId'))
      && $this->specPricesEngine()?->getSprice($specPriceId, $item) && $item) {
      return [$this->form(SpecPriceDto::fromArray($item)), $this->backButton()];
    }
    
    return $this->redirectDefaultAction($this->getDefaultUrl());
  }
  
  protected function form(?SpecPriceDto $item)
  {
    $sports = ModCommHelper::get('areas', 'areas/relevantSportsByTypeAsSelect', [
      'current'    => $item->typeSport ?? null,
      'all_sports' => config('specPrices')->useForAllSorts()
    ], 'sports_by_type', '');
    
    return $this->render('_form', [
      'specPrice' => $item,
      'sports'    => $sports,
    ]);
  }
  
  function editTime()
  {
    $specPriceId = (int)Service::request()->_get('specPriceId');
    if (!$specPriceId || !$this->specPricesEngine()?->getSprice($specPriceId, $item)) {
      return $this->redirectDefaultAction($this->getDefaultUrl());
    }
    if ($areas_data = ModCommHelper::get('areas', 'areas/getAreas', ['as' => 'array'], 'areas')) {
      $prev     = $finish = 0;
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
        'specPrice'  => SpecPriceDto::fromArray($item),
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
    if (($specPriceId = (int)Service::request()->_post('specPriceId')) && $this->specPricesEngine()->getSprice($specPriceId, $item)) {
      $postData                    = Service::request()->_post();
      $postData['duration_start']  = $postData['start_year'] . '-' . $postData['start_month'] . '-' . $postData['start_day'];
      $postData['duration_finish'] = $postData['finish_year'] . '-' . $postData['finish_month'] . '-' . $postData['finish_day'];
      $postData['duration']        = isset($postData['active']) ? 1 : 0;
      $item                        = array_merge($item, $postData);
      $specPrice                   = SpecPriceDto::fromArray($item);
      if ($this->specPricesEngine()->changeSpriceDuration($specPrice->spriceId, (int)$specPrice->duration, $specPrice->durationStart,
        $specPrice->durationFinish,
        $specPrice->durations)) {
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
  public function getSpecPricesAsSelect($selected = [], $name = 'specPrices', $class = ' ', $multiple = true): string
  {
    $values = [];
    if (empty($selected)) {
      foreach ($this->getSegmentByKey('selectedSpecPrices') as $specPrice) {
        $selected[] = $specPrice->{$this->getModel('SpecPrices')?->getPrimaryKey()};
      }
    }
    foreach ($this->specPricesAsArray() as $item) {
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
  
  public function specPricesAsArray(): array
  {
    $specPrices = [];
    $this->model->getEngine()->getSprices($specPrices);
    
    return $specPrices;
  }
  
  protected function specPricesEngine(): SpecPricesEngine
  {
    return getEngine('specPrices', false);
  }
  
  protected function getTriggerConditionsEngine(): SpecPricesTriggerConditionsEngine
  {
    return $this->specPricesEngine()->getTriggerConditionsEngine();
  }
}