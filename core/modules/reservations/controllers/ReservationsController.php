<?php

namespace AC\core\modules\reservations\controllers;

use AC\core\modules\users\controllers\admin\UsersAuthController;
use AC\core\system\controller\BaseController;

use AC\core\system\helpers\RedisHelper;
use AC\core\system\helpers\TranslateHelper;
use AC\core\modules\reservations\models\ReservationsModel;
use AC\core\modules\reservations\exceptions\ReservationDateUnavailableException;
use AC\core\system\exceptions\http\RequestValidationException;
use AC\core\ReservationsVisualizationCommon;
use authorization;
use Service;

/** Общие действия бронирования и отображение ошибок проверки входных данных. */
class ReservationsController extends BaseController
{
  public    $default_action   = 'showReservations';
  public    $default_template = '';
  protected $base_model       = 'ReservationsModel';
  public    $tpl_view         = 'reservations';

  public $baseView = 'ReservationView';
  /**
   * @var ReservationsModel
   */
  public $model;

  /** @var array{type_id: int, sport_id: int, page: int}|null Параметры только для календаря ошибки. */
  private ?array $validationCalendarContext = null;

  public function __construct($action = false, $runController = true)
  {
    $this->admin = Service::app()->isAdmin();
    $this->touch = Service::app()->isTouch();

    parent::__construct($action, $runController);
  }

  /** Показать ошибку проверки данных внутри бронирования, не продолжая прерванное действие. */
  public function runController()
  {
    try {
      parent::runController();
    } catch (RequestValidationException $e) {
      $this->showValidationError($e);
    }
  }

  /** Показать ошибку модели или выбора контроллера стандартным блоком бронирования. */
  public function showValidationError(RequestValidationException $error): void
  {
    // После ранней ошибки модель может ещё не содержать тип площадки и спорт.
    // Запасной контекст используется только для навигации, не для выполнения заказа.
    $typeId = (int)($this->model?->type_id ?? 0);
    $sportId = (int)($this->model?->sport_id ?? 0);
    if ($typeId <= 0) {
      $typeId = (int)$this->engine->areas->getFirstActiveType();
      $sportId = 0;
    }
    if ($sportId <= 0) {
      $sportId = (int)$this->engine->areas->getMinSportByType($typeId);
    }
    $this->validationCalendarContext = [
      'type_id' => $typeId,
      'sport_id' => $sportId,
      'page' => max(1, (int)($this->model?->page ?? 1)),
    ];
    if ($this->view === null) {
      $this->setView();
    }
    $messageKey = $error instanceof ReservationDateUnavailableException
      ? 'reservation_date_unavailable'
      : match ($error->getAlias()) {
        'date' => 'reservation_invalid_date',
        'time' => 'reservation_invalid_time',
        'area_id', 'type_id', 'sport_id' => 'reservation_invalid_court',
        'ticket_id' => 'reservation_invalid_ticket',
        'page', 'week' => 'reservation_invalid_calendar',
        'prepayment' => 'reservation_invalid_payment',
        'light_state', 'heating_state', 'net_state' => 'reservation_invalid_services',
        default => 'reservation_invalid_input',
      };
    $this->view->setErrorMessage(lang($messageKey, 'message_error'));
    $this->view->useH1 = false;
    $this->view->_back_href = 'reservations.php?' . http_build_query([
      'action' => 'showReservations',
      'type_id' => $typeId,
      'sport_id' => $sportId,
      'date' => date('Y-m-d'),
    ]);
    $this->view->content[(int)!$this->admin] = $this->view->getInfoBlockContent();
  }

  public function start()
  {
    if (preg_match('/ajax/i', $this->action)) {
      echo parent::start();
    } else {
      if ($this->admin) {
        /** @var $auth UsersAuthController */
        $auth                       = Service::auth();
        $this->model->customer      = $auth->getUserId();
        $this->model->customerTitle = $auth->getUserData()['code'] . '|' . $auth->getUserData()['name'];
      }

      if ($this->action !== $this->default_action) {
        if ($this->view->_content = parent::start()) {
          return $this->view->getInfoBlockContent();
        } else {
          $this->view->_content = $this->view->getErrorMessage();

          return $this->view->getInfoBlockContent();
        }
      } else {
        return parent::start();
      }
    }
  }

  public function getTitle()
  {
    return $this->engine->areas->getTitleByTypeAndSport($this->model->type_id, $this->model->sport_id,
        'title_site_url') . $this->model->prefixTitle();
  }


  /**  Показать таблицу с расписанием
   *
   * @return string
   */
  public function showReservations()
  {
    $this->view->showCalendar = true;
    ReservationsVisualizationCommon::setView(['default_template' => $this->default_template ?? $this->model->areas_types->alias], true);

    if (!$this->model->areas_types->checkError) {
      //ключ, дата для отображения в шаблоне и заголовок страницы
      $v_zag             = explode('.', date('d.m.Y', strtotime($this->model->date)));
      $this->view->title = $this->getTitle() . ' ' . $v_zag[0] . '. ' . TranslateHelper::translateMonth($v_zag[1], true) . ' ' . $v_zag[2];
      $this->view->h1    = '<span class="reserv-title-pc">' . $this->getTitle() . ' ' . $v_zag[0] . '. '
        . TranslateHelper::translateMonth($v_zag[1], true) . ' ' . $v_zag[2] . '</span>';

      /**
       * Выводит на мобилке кнопки вперед на день и назад на день (листание календаря)
       * todo : странная логика формирования ссылок нужно переделать
       */
      parse_str($_SERVER['QUERY_STRING'], $output);
      $output_right         = (!empty($output)) ? $output : ['date' => date('Y-m-d')];
      $output_right['date'] = date('Y-m-d', strtotime(($this->model->date) ? ($this->model->date . ' + 1 day') : date('Y-m-d') . ' + 1 day'));
      $right                = '?' . http_build_query($output_right);
      $output_left          = (!empty($output)) ? $output : ['date' => date('Y-m-d')];
      $output_left['date']  = date('Y-m-d', strtotime(($this->model->date) ? ($this->model->date . ' - 1 day') : date('Y-m-d') . ' - 1 day'));
      $left                 = '?' . http_build_query($output_left);
      $url                  = explode('?', $_SERVER['REQUEST_URI'])[0];
      $url_left             = $url . $left;
      $url_right            = $url . $right;
      $this->view->h1       .= '<span class="reserv-title-mobile">';
      $this->view->h1       .= ((strtotime($this->model->date) - strtotime(date('Y-m-d'))) > 0) ? '<a class="left-date" href="' . $url_left . '"></a>'
        : '';
      $this->view->h1       .= '<span>' . $v_zag[0] . '. '
        . TranslateHelper::translateMonth($v_zag[1], true) . ' ' . $v_zag[2] . '</span>';
      $this->view->h1       .= '<a class="right-date" href="' . $url_right . '"></a>';
      $this->view->h1       .= '</span>';

      //центральный блок
      if ($out = ReservationsVisualizationCommon::renderAreasTypeColumns(
        $this->engine,
        $this->model->date,
        $this->model->type_id,
        $this->model->sport_id,
        $this->model->page,
        $this->model->_limit,
        $this->model->week,
        $this->admin,
        $this->touch
      )) {
        $prefix_text_messages = !USE_AUTHORIZATION ? '_without_authorization' : '';
        $courtType            = $this->model->areas_types->alias;
        $typeId               = $this->model->type_id;
        $title_block          = '
  <div class="text-mess">
    <div class="pc-mess">
    ' . langByAreaType('info_text_above_timetable' . $prefix_text_messages, $courtType, $typeId) . '
    </div>
    <div class="mobile-mess">
    ' . langByAreaType('mobile_info_text_above_timetable' . $prefix_text_messages, $courtType, $typeId) . '
    </div>
  </div>';

        $this->view->setByKey('title_block', $title_block);

        return $out;
      } else {
        $this->view->setErrorMessage(lang('Today is a holiday!', 'message_error'));

        return $this->view->getInfoBlockContent();
      }
    } else {
      $this->view->setErrorMessage('type_id invalid');

      return $this->view->getInfoBlockContent();
    }
  }

  /** Выбор спорта для корта на мобилке или точскрине
   *
   */
  public function selectSport()
  {
    $this->view->title = 'reservations';
    $this->view->key   = 'reservations';
    $sports            = $this->model->selectSport();
    if (count($sports) > 1) {
      return $this->view->render('../select_court', ['sports' => $sports, 'type_id' => $this->model->type_id]);
    }

    return $this->selectPage();
    //return $this->showReservations();
  }

  public function selectPage()
  {
    $this->view->title = 'reservations';
    $this->view->key   = 'reservations';
    $pages             = $this->model->selectPage();
    if (!config('reservations')->isOpenType((int)$this->model->type_id) && (count($pages) > 1)) {
      return $this->view->render('../select_page', ['pages' => $pages, 'type_id' => $this->model->type_id, 'sport_id' => $this->model->sport_id]
      );
    }

    return $this->showReservations();
  }

  public function checkActionData()
  {
    if (method_exists($this, 'check' . ucfirst($this->action))) {
      return $this->{'check' . ucfirst($this->action)}();
    }

    return true;
  }

  protected function checkShowReservations()
  {
    return true;
  }

  protected function getModulePath($modulePath = null)
  {
    return 'core\modules\reservations\\';
  }

  /** Подготовить календарь, в том числе при ошибке входных данных бронирования. */
  public function setViewParams()
  {
    $context = $this->getCalendarContext();
    $this->view->key = 'reservations_' . $context['type_id'];
    // Дата календаря не меняет дату заказа, загрузка которого могла прерваться.
    $calendarDate = $this->model?->date ?? date('Y-m-d');
    $this->view->content[0] = $this->renderCalendar($calendarDate);
  }

  /** Отобразить календарь навигации для текущего типа площадки и спорта. */
  protected function renderCalendar(string $date): string
  {
    $context = $this->getCalendarContext();
    return ReservationsVisualizationCommon::renderCalendar(
      $this->engine,
      $date,
      $context['type_id'],
      $context['sport_id'],
      $context['page']
    );
  }

  /** @return array{type_id: int, sport_id: int, page: int} Контекст календаря без повторного чтения запроса. */
  protected function getCalendarContext(): array
  {
    return $this->validationCalendarContext ?? [
      'type_id' => (int)$this->model->type_id,
      'sport_id' => (int)$this->model->sport_id,
      'page' => (int)$this->model->page,
    ];
  }
}
