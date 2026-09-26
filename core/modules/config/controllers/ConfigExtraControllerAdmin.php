<?php

namespace AC\core\modules\config\controllers;



use AC\core\modules\areas\models\AreasModel;
use AC\core\modules\config\models\ConfigExtraModel;

use AC\core\system\helpers\NumberHelper;
use Service;
use stdClass;

class ConfigExtraControllerAdmin extends ConfigControllerAdmin
{
  public    $default_action   = 'showList';
  protected $base_model       = 'ConfigExtraModel';
  public    $default_template = 'extra';

  /**
   * @var ConfigExtraModel
   */
  public $model;

  public function update($new = false)
  {
    $type  = Service::request()->_('type');
    $sport = Service::request()->_('sport');
    if ($type !== null && $sport !== null) {
      $extra_data = $this->model->getExtraByTypeAndSport($type, $sport);
      $_rate      = Service::request()->_('rate');

      $use_time = Service::request()->_('use_time');
      if ($_rate !== null) {
        $update_check = true;
        /** @var $extra ConfigExtraModel */
        foreach ($extra_data->rate as $rate) {
          $extra = $rate->extra;
          if ($extra->load(
            array('rate' => $_rate[$extra->{$extra->getPrimaryKey()}], 'use_time' => (int)isset($use_time[$extra->{$extra->getPrimaryKey()}]))
          )) {
            if (!$extra->save()) {
              $update_check = false;
            }
            $rate->rate = $extra->rate;
          } else {
            $this->view->addMessages($extra->getErrors(), 'error');
          }
        }
        if ($update_check && !$this->view->issetMessages()) {
          $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
//          $this->redirectDefaultAction();
        }
      }

      return array($this->render('_form', array('model' => $extra_data)), $this->renderUseTimeForm($extra_data), $this->backButton());
    } else {
      $this->view->addMessage(
        lang('message_not_element_in_base', 'message_error'),
        'error'
      );
      $this->redirectDefaultAction();
    }
  }

  public function renderUseTimeForm($extra_data)
  {
    $out = '<div style="display: flex;justify-content: center">';
    foreach ($extra_data->rate as $rate) {
      $model           = new stdClass();
      $model->title    = $rate->title;
      $model->extra_id = $rate->extra->extra_id;
      if ($rate->extra->use_time) {
        $model->prices = ConfigExtraModel::getExtraPriceWeek($rate->extra->extra_id);
        $model->times  = AreasModel::generateWorkTime($extra_data->type, $extra_data->sport, true);
        $model->timesTitles  = AreasModel::generateWorkTime($extra_data->type, $extra_data->sport, true, false);
        $out           .= $this->render(
          '_form_week',
          array(
            'model' => $model
          )
        );
      }
    }

    return $out . '</div>';
  }

  public function changeExtraWeek()
  {
    $prices = Service::request()->_('price');
    $model  = new ConfigExtraModel(Service::request()->_('extra_id'));
    if ($prices && is_array($prices)) {
      foreach ($prices as $weekday => $times) {
        foreach ($times as $time => $price) {
          $price = NumberHelper::float($price);
          if (isset($model->weekPrices[$weekday][$time]) && (float)$price != (float)$model->weekPrices[$weekday][$time]) {
            $model->updateRowWeekPrice($weekday, $time, $price);
          }
        }
      }
    }
    $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
    $extra_data = $model->getExtraByTypeAndSport($model->area_type_id, $model->area_sport_id);

    return array($this->render('_form', array('model' => $extra_data)), $this->renderUseTimeForm($extra_data), $this->backButton());
  }
}