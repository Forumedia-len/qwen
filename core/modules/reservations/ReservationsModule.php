<?php

namespace AC\core\modules\reservations;

use AC\core\system\helpers\StringHelper;
use AC\core\modules\areas\models\AreasModel;
use AC\core\system\module\BaseModule;
use AC\core\modules\reservations\models\ReservationsModel;
use AC\core\modules\reservations\controllers\ReservationsController;
use AC\core\system\exceptions\http\RequestValidationException;
use Service;

/**
 * Модуль для управления бронированиями.
 */
class ReservationsModule extends BaseModule
{
  /**
   * Получить готовую модель, в том числе для обработки платежей вне контроллера.
   *
   * @param string|null $modelName
   * @param mixed ...$arguments Параметры конструктора модели
   * @return mixed
   */
  public function useModel($modelName = null, ...$arguments)
  {
    $model = parent::useModel($modelName, ...$arguments);
    if ($model instanceof ReservationsModel) {
      $model->admin = Service::app()->isAdmin();
      $model->touch = Service::app()->isTouch();
      $model->initialize();
    }
    return $model;
  }

  /**
   * Выполняет модуль, вызывая нужный контроллер.
   *
   * @param string|null $action         Действие
   * @param mixed       $key            Ключ
   * @param string|null $controllerName Название контроллера (по умолчанию определяется автоматически)
   * @return mixed Результат выполнения
   */
  public function exec($action = null, $key = null, $controllerName = null)
  {
    try {
      $controllerName = $this->getReservationControllerClassName();
    } catch (RequestValidationException $error) {
      // Тип площадки может быть отклонён ещё до создания специализированного контроллера.
      /** @var ReservationsController $controller */
      $controller = $this->useController('ReservationsController', ['action' => 'showReservations']);
      $controller->showValidationError($error);
      $result = $controller->view->getToArray();
      return $key && isset($result[$key]) ? $result[$key] : $result;
    }
    return parent::exec($action, $key, $controllerName);
  }
  
  /**
   * Получает имя класса контроллера для бронирования в зависимости от условий.
   *
   * @return string Имя класса контроллера
   */
  protected function getReservationControllerClassName(): string
  {
    $openModelClassName = 'OpenControllerReservation';
    $areaId = Service::request()->validated('area_id', 'request', [['integer', ['min' => 0]]], 0);
    $area = $areaId ? new AreasModel($areaId) : null;
    $typeId = Service::request()->validated('type_id', 'request', [['integer', ['min' => 0]]],
      $area->type_id ?? module('areas')->useModel()?->getFirstActiveType());
    $alias  = module('areas')->useModel()?->getEngine()?->getAliasType($typeId);
    $sportId = Service::request()->validated('sport_id', 'request', [['integer', ['min' => 0]]],
      !empty($area->sport_id) && (int)$area->type_id === (int)$typeId ? $area->sport_id : 0);
    if (config('DoubleGame')->doubleFriendsEnabled($typeId, $sportId, $areaId)) {
      $openModelClassName = 'Double' . $openModelClassName;
    }
    
    return match ($alias) {
      'open'  => $openModelClassName,
      default => ucfirst(StringHelper::underscoreToCamelCase($alias)) . 'ControllerReservation',
    };
  }
}
