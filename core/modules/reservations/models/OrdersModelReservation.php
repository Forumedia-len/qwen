<?php

namespace AC\core\modules\reservations\models;

use AC\app\entities\enums\Encash;
use AC\core\engines\Engines;
use AC\core\modules\areas\models\AreasLightsModel;
use AC\core\modules\clients\models\ClientsModel;
use AC\core\modules\config\helpers\ScopedConstantHelper;
use AC\core\modules\reservations\helpers\TmpBlockingHelper;
use AC\core\modules\reservations\locators\ReservServiceLocator;
use AC\core\modules\webio\models\WebIoModel;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\IconHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\helpers\TranslateHelper;
use Codeception\Util\Debug;
use Service;


/** Данные формы подтверждения и выполнения бронирования. */
class OrdersModelReservation extends ReservationsModel
{
  /** Максимальное количество бронирований
   *
   * @var int
   */
  public $max_count_reservation = CLOSE_MANY_HOURS;

  public ?int $prepayment;

  public $ticket;
  /**
   * @var bool Открытые корты переключатель
   */
  public $open = false;

  protected $res_ids = [];

  /** Загружает параметры формы после основных данных бронирования. */
  protected function initializeData(): void
  {
    parent::initializeData();
    [$prepayment, $payOnlineType] = array_pad(explode('_', Service::request()->validated('prepayment', 'request', 'string', ''), 2), 2, null);
    $this->prepayment    = is_numeric($prepayment) ? (int)$prepayment : null;
    $this->payOnlineType = $payOnlineType ?? 'elv';
    if (USE_NEW_STRATEGY_OPTION) {
      $option = explode('_', Service::request()->validated('option_id', 'request', 'string', ''));
      switch ($option[0]) {
        case 'stock' :
          $this->stock_id  = (int)$option[1];
          $this->sprice_id = 0;
          break;
        case 'sprice' :
          $this->stock_id  = 0;
          $this->sprice_id = (int)$option[1];
          break;
      }
    } else {
      $this->stock_id  = Service::request()->_('stock_id', []);
      $this->sprice_id = Service::request()->_('sprice_id');
    }

    foreach ((array)$this->stock_id as $stock_id) {
      $this->addStockRate[(int)$stock_id] = 0;
    }
    $this->memo = Service::request()->validated('memo', 'request', 'string');
    $this->setStateWebIo();
  }

  public function prefixTitle()
  {
    return ' - ' . lang('Your reservation', 'order');
  }

  /** Загрузить данные для заказа
   *
   * @param $error_code
   *
   * @return array|bool
   */
  public function getOrder(&$error_code)
  {
    if ($this->checkData($error_code)) {
      $this->setTotalCostPayment($check_area, $error_code);
      if ($check_area) {
        if ($this->checkOrder($error_code)) {
          $this->engine->areas->getAreasTypesData($types);
          $sum = $this->setOrderPrice();

          return [
            'model'                    => $this,
            'title_h2'                 => count($types) > 1 ? $this->areas->type_title : $this->areas->sport_title,
            'title_h3'                 => (count($types) > 1
              && Engines::checkShowTitleSport($this->type_id, $this->sport_id) ? $this->areas->sport_title . ', ' : '') . $this->areas->title,
            'area_data'                => $this->areas,
            'times'                    => $this->times,
            'weekday'                  => TranslateHelper::translateWeekday(CalendarHelper::getWeekdayByUnixtime(strtotime($this->date))),
            'titles_times'             => TimeHelper::getTimeByStartFinish($this->times, $this->areas->period),
            'date'                     => date('d.m.Y', strtotime($this->date)),
            'sum'                      => (object)[
              'title'     => number_format($sum, 2, ',', ' '),
              'sum_price' => $sum,
            ],
            'webIo'                    => $this->getStateLHN(),
            'page'                     => $this->page,
            'options'                  => $this->getPriceOptions(),
            'prepayment_sum'           => number_format($this->current_client->prepayment_sum, 2, ',', ' '),
            'min_rejection_days_count' => $this->engine->config['min_max'][$this->type_id][$this->sport_id]['min_rejection_days_count'],
            'prices'                   => array_merge($this->renderPricesBlock()),
            'courtType'                => $this->areas_types->alias,
            'new_strategy'             => defined('USE_NEW_STRATEGY_IF_SUM_NULL') && USE_NEW_STRATEGY_IF_SUM_NULL && $sum == 0,
          ];
        }
      }
    }

    return false;
  }

  /** Базовые проверки данных на доступность и проверка авторизации
   *
   * @param $error_code
   *
   * @return bool
   */
  public function checkData(&$error_code): bool
  {
    if ($this->area_id !== null && $this->time !== null) {
      $get_unix_time = strtotime($this->date . ' ' . $this->time);
      //проверка даты
      if (date('Y-m-d H:i', $get_unix_time) == $this->date . ' ' . $this->time) {
        //дата верна
        if (!$this->areas->checkError) {
          //площадка найдена
          $action     = Service::request()->_('action');
          $isOpenType = config('reservations')->isOpenType((int)$this->type_id);
          if ((!$isOpenType)
            || ($isOpenType && !empty($this->ticket_id))
            || ($isOpenType && in_array($action, ['joinForm', 'unJoin', 'join']))
            || ($this->admin || !(defined('RESERVATION_ONLY_POSSIBLE_FROM_FULL_HOUR')
                && RESERVATION_ONLY_POSSIBLE_FROM_FULL_HOUR
                && TimeHelper::returnPartTimeFromStringMysqlTime($this->time) != RESERVATION_ONLY_POSSIBLE_FROM_FULL_HOUR))
          ) {
            if ($this->admin || $this->engine->clients->checkAuthorization() == true || $this->checkBarClient()) {
              //проверка авторизации
              if (($this->current_client->area_type == 0 && !$this->checkBarClient())
                || ($this->current_client->area_type == $this->type_id && !$this->checkBarClient())
                || ($this->checkBarClient() && !$isOpenType)
                || ($this->checkBarClient() && $isOpenType && USE_GUEST_OPEN)
                || ((!$this->checkBarClient() || USE_GUEST_OPEN) && $isOpenType && USE_OPEN_PP)
              ) {
                // проверка доступности бронирования по типу площадки ? если бар клиент то он может бронировать все кроме открытых кортов
                return true;
              } else {
                $error_code = lang('You are not entitled to book this type of space', 'message_error');
              }
            } else {
              //посетитель не авторизован
              $error_code = 'error_authorization';
            }
          } else {
            $error_code = lang('Booking at this time period is not possible', 'message_error');
          }
        } else {
          $error_code = 'error_area_id';
        }
      } else {
        $error_code = 1;
      }
    } else {
      if (empty($this->time)) {
        $error_code = 'null_interval';
      } //не всех входные данные есть
      else {
        $error_code = 'error_not_data';
      }
    }

    return false;
  }

  /** Получить значение света
   *
   * @param int|null $weekday будний день (1–7); null — из {@see $this->date}
   *
   * @return array
   */
  protected function getStateLHN($states = [], $times = [], $disable = 0, $weekday = null)
  {
    $weekday = $weekday === null
      ? CalendarHelper::getWeekdayByUnixtime(strtotime($this->date))
      : (int)$weekday;
    foreach (WebIoModel::getWebIoTypes() as $_state => $item) {
      if ($this->areas->{$_state . '_on'} == 1) {
        if (!isset($states[$_state])) {
          $states[$_state] = (object)[
            'name'   => $_state,
            'type'   => $item['id'],
            'title'  => $item['title'],
            'price'  => $this->areas->{$_state . '_price'},
            'hidden' => 1,
            'label'  => lang(
              'show_order_block_webIo_label',
              'show_order',
              [
                'title'    => $item['title'],
                'price'    => number_format($this->areas->{$_state . '_price'}, 2, ',', ''),
                'currency' => CURR_VALUTE,
                'period'   => $this->areas->period,
              ]
            ),
            'icon'   => IconHelper::statsWebIoIcon($_state, true, 'background-size: 18px;width: 21px;height: 21px;position: relative;top: 6px;'),
          ];
        }

        foreach ((!empty($times) ? $times : $this->times) as $time) {
          $states[$_state]->{'times'}[$time]['title']   = TimeHelper::generateTitleByTimeAndPeriod($time, $this->areas->period) . ' ' . lang(
              'clock'
            );
          $hidden                                       = (int)AreasLightsModel::checkAreasDefaultState(
            $this->area_id,
            $weekday,
            date('H:i:s', strtotime($this->date . ' ' . $time)),
            $item['id']
          );
          $states[$_state]->{'times'}[$time]['hidden']  = $hidden;
          $states[$_state]->{'times'}[$time]['disable'] = $disable;
          if (!$hidden) {
            $states[$_state]->{'hidden'} = $hidden;
          }
        }
      }
    }

    return $states;
  }

  /** Произвести заказ
   *
   * @param $error_code
   * todo поправить снятие денег если платит гутхабен
   *
   * @return array|bool
   */
  public function proceed(&$error_code)
  {
    $prices = [];
    if ($this->checkData($error_code)) {
      $this->setTotalCostPayment($check_area, $error_code);
      // время доступно для бронирования
      if ($check_area) {
        // проверка резерва на ограничения
        if ($this->checkOrder($error_code)) {
          if ($this->checkPaymentAvailability($error_code)) {
            // установка статуса бронирования (делаем сработку разных событий, например если не присоединился второй игрок)
            $this->setStatus();
            $check = true;
            if (!TmpBlockingHelper::checkOrderBlock($this->getAreaId(), $this->getDate(), $this->getTimes(), $this->getClientId())) {
              $check      = false;
              $error_code = lang('Reserved block order', 'message_error');
            }
            if ($check && $this->runProceed($prices, $error_code)) {
              $this->mailAfterInsertReservation();
              if (!$this->online_pay) {
                TmpBlockingHelper::deleteOrderBlock($this->getAreaId(), $this->getDate(), $this->getTimes(), $this->getClientId());
              }

              $doorCodesEnabled = ScopedConstantHelper::boolValue(
                'DOOR_CODES',
                ScopedConstantHelper::contextKey(
                  (int)$this->type_id,
                  (int)$this->sport_id,
                  (int)$this->getAreaId()
                )
              );

              return [
                'sum'        => array_sum($prices), //$this->getTotalCostOrder(),
                'res_ids'    => implode('|', $this->res_ids),
                'door_codes' => (count($this->door_codes) > 0 && $doorCodesEnabled)
                  ? view()->render('door_codes', ['door_codes' => $this->door_codes]) : '',
                'message'    => $this->getMessageAfterBooking(
                  array_sum($prices),
                  $this->engine->config['min_max'][$this->type_id][$this->sport_id]['min_rejection_days_count']
                ),
                'courtType'  => $this->areas_types->alias,
                'pay_type'   => $this->payOnlineType,
              ];
            }
          }
        }
      }
    }

    return false;
  }


  protected function runProceed(&$prices, &$error_code)
  {
    $check     = 0;
    $sprice_id = $this->sprice_id;
    $stock_id  = $this->stock_id;

    foreach ($this->times as $this->time) {
      $this->setProceedTime();
      $this->price     = $this->sum_price_array[$this->time]['sum_price'];
      $this->sprice_id = $this->sum_price_array[$this->time]['spec_price'] !== null ? $sprice_id : null;
      $this->stock_id  = $this->sum_price_array[$this->time]['stock'] !== null ? $stock_id : null;
      foreach (array_keys(WebIoModel::getWebIoTypes()) as $state) {
        $this->{$state . '_price'} = $this->state_sum_price[$state][$this->time];
        $this->{$state . '_state'} = (int)$this->checkWebIoStateByTime($state, $this->time);
      }
      $prices[$this->time] = $this->getFullPrice();
      // перейти к процессу бронирования
      $this->insertionProcess($check, $error_code);
    }
    $this->stock_id = $stock_id;
    $this->engine->webIo->sendFTPCurrentIcal($this->engine, $this->area_id, $this->getDate());
    return $check == 0;
  }

  protected function setProceedTime(): void
  {
    $this->start  = date('Y-m-d H:i:s', strtotime($this->date . ' ' . $this->time));
    $this->finish = date('Y-m-d H:i:s', strtotime($this->date . ' ' . TimeHelper::addMinutes2MySQLTime($this->time, $this->areas->period)));
  }

  public function insertionProcess(&$check, &$error_code)
  {
    if ($this->insertReservation($error_code)) {
      $this->res_ids[] = $this->inserted_reservation_id;
      if ($this->door_code) {
        $this->door_codes[strtotime($this->time)] = (object)[
          'time' => $this->time,
          'code' => $this->door_code,
        ];
      }
      $this->switchStateLHN($error_code);
    } else {
      $check++;
    }
  }

  /** Проверка перед заказом бронирования
   *
   * @param $error_code
   * todo переработать проверку на количество бронирований
   *
   * @return bool
   */
  public function checkOrder(&$error_code)
  {
    $device_type = 'pc';
    if ($this->touch) {
      $device_type = 'touch';
    }
    $rule           = $this->engine->rules->rulePriority($this->area_id, $this->type_id, $this->sport_id, $this->current_client->club_state,
      $device_type);
    $restrictionCtx = config('clientRestriction')->fromReservationModel($this, $device_type);

    if (!$this->admin && !$this->current_client->super && config('clientRestriction')->isBookingDenied($restrictionCtx)) {
      $error_code = lang('message_noclub_error', 'message_error');

      return false;
    }

    if (!$this->admin && !$this->current_client->super && ($countPeriod = $this->checkMinCountPeriod())
      && (count($this->times) < $countPeriod || (config('Reservations')->getConsecutiveBookings($this->type_id,
            $this->sport_id) && !$this->checkConsecutiveBookings(count($this->times))))) {
      $commonTime = TranslateHelper::translatePeriodInMinute($countPeriod * $this->areas->period);
      $error_code = lang('Minimum booking time for this lounge', 'message_error',
        ['period' => $commonTime]);
      if (config('Reservations')->getConsecutiveBookings($this->type_id, $this->sport_id)) {
        $error_code = lang('Minimum booking time for this lounge is consecutive', 'message_error', [
          'time'         => $commonTime,
          'count_period' => $countPeriod . '*' . $this->areas->period,
        ]);
      }

      return false;
    }

    if (!$this->admin && !$this->current_client->super && !$this->checkMaximumNumberOfConsecutivePeriods($error_code)) {
      return false;
    }

    if ($this->checkMaxForward($rule, $error_code)) {
      if ($this->checkOrderForUnavailableSports($error_code)) {
        if ($this->checkOrderForRangeClubState($error_code)) {
          $this->engine->getCountReservationByClient(
            $this->current_client->client_id,
            date('Y-m-d H:i'),
            false,
            $countReservation,
            $this->date . ' ' . $this->time_finish,
            $this->type_id,
            isset($rule['sport_id']) ? $rule['sport_id'] : null
          );
          //проверка ограничений по кол-ву заказов
          //Sie haben Limit der Buchungen erreicht!

          $max_count_reservation = config('clientRestriction')->resolveEffectiveReservationLimit(
            $restrictionCtx,
            isset($rule['reservation_limit']) ? (int)$rule['reservation_limit'] : null,
            $this->max_count_reservation,
          );
          $totalReservationCount = count($this->times) + $countReservation;
          if ($this->admin
            || $this->current_client->super == 1
            || !$max_count_reservation
            || $totalReservationCount <= $max_count_reservation
          ) {
            if ($this->checkOrderOfTimeRange($error_code)) {
              return true;
            }
          } else {
            $error_code = $rule['error_reservation_limit'] ?? lang('You have reached your limit of bookings!', 'message_error');
          }
        }
      }
    }

    return false;
  }

  protected function checkMaxForward($rule, &$error_code)
  {
    $count    = !empty($rule['max_forward_reservation_days_count']) ? $rule['max_forward_reservation_days_count'] : null;
    $interval = (strtotime($this->date) - strtotime(date('Y-m-d', strtotime('now')))) / 24 / 3600;
    if ($this->admin || $this->current_client->super || $count === null || $count > $interval) {
      return true;
    } else {
      $error_code = $rule['error_max_forward_reservation_days_count'];

      return false;
    }
  }

  /** Проверка бронирования по наличию заблокированных площадок
   *
   * @param $error_code
   *
   * @return bool
   */
  protected function checkOrderForUnavailableSports(&$error_code)
  {
    if ($this->current_client->unavailable_sports == null || $this->admin
      || module('clients')->useModel()->getEngine()->checkClientAllowedPlayAreaForInaccessibility((array)$this->current_client, $this->date,
        $this->type_id, $this->sport_id)) {
      return true;
    }
    $error_code = lang('You are not entitled to book this type of space', 'message_error');

    return false;
  }

  /** Проверка бронирования по рангу клубного членства
   *
   * например блокировки для ни членов площадок после 16 часов
   *
   * @param $error_code
   *
   * @return bool
   */
  protected function checkOrderForRangeClubState(&$error_code)
  {
    if ($this->admin || $this->current_client->super) {
      return true;
    }
    if ($this->checkOrderForClientClubState($error_code)) {
      $rule = $this->engine->rules->rulePriority(
        $this->area_id,
        $this->type_id,
        $this->sport_id,
        $this->current_client->club_state,
        $this->touch ? 'touch' : 'pc'
      );
      if ($this->engine->checkThePossibilityOfReservationByClubRule($rule, $this->times, $this->date)) {
        return true;
      }
      $error_code = lang('You can book between', 'message_error', ['time_start' => $rule['time_start'], 'time_finish' => $rule['time_finish']]);
    } else {
      $error_code = lang('You are not authorized for this booking', 'message_error');
    }

    return false;
  }

  /** Проверить клиента на возможность играть на этом корте
   *
   * @param $error_code
   *
   * @return bool
   */
  protected function checkOrderForClientClubState(&$error_code): bool
  {
    return true;
  }

  /** Удалить заказ
   *
   * @param $error_message
   *
   * @return bool
   */
  public function removeOrder(&$error_message): bool
  {
    if ($this->checkRemove($error_message)) {
      $this->removeReservation($error_message);
      $this->engine->webIo->sendFTPCurrentIcal($this->engine, $this->area_id, $this->getDate());
      return true;
    }

    return false;
  }

  /** Проверка перед удалением бронирования
   *
   * @param $error_message
   *
   * @return bool
   */
  public function checkRemove(&$error_message)
  {
    if ($this->checkData($error_message)) {
      if ($this->engine->getReservationData($this->area_id, $this->getDate() . ' ' . $this->time, $reservation_data)) {
        if ($this->admin) {
          $this->current_client = new ClientsModel($reservation_data['client_id']);
        }
        //резервирование найдено
        if ($reservation_data['client_id'] == $this->current_client->client_id || $this->admin) {
          //залогиненный клиент есть заказчик этого промежутка
          if ($this->admin || strtotime($reservation_data['start']) > strtotime(
              date(
                'Y-m-d'
              ) . ' + ' . $this->engine->config['min_max'][$this->type_id][$this->sport_id]['min_rejection_days_count'] . 'day'
            )
          ) {
            return true;
          }

          // нельзя удалить бронирование за опеределенное количество дней
          $error_message = lang(
            'error_min_rejection_days_count',
            'message_error',
            ['min_rejection_days_count' => $this->engine->config['min_max'][$this->type_id][$this->sport_id]['min_rejection_days_count']]
          );
        } else {
          // нельзя удалить бронирование другого клиента
          $error_message = '<span class="info">' . lang('You can not delete requests of other users', 'message_error') . '</span>';
        }
      } else {
        //резервирование не найдено
        $error_message = '<span class="info">' . lang('order not found', 'message_error') . '</span>';
      }

      return false;
    }

    return false;
  }

  public function setOrderPrice($ajax = false)
  {
    return $this->getTotalCostOrder();
  }

  public function setStatus($status = 1)
  {
    $this->status = $status;
  }

  public function setPayStatus(int $status = 1): void
  {
    $this->pay_status = $status;
  }

  protected function checkPaymentAvailability(&$error_code)
  {
    // проверка если платит с лицевого счета, хватает на нем денег или нет
    if (Encash::from($this->prepayment) === Encash::PrivateAccount && $this->setOrderPrice() > $this->current_client->prepayment_sum) {
      // не хватает денег на гутхабене
      $error_code = 'p1';

      return false;
    }

    return true;
  }

  /**
   *  Новая проверка количество игр в текущем дне
   *
   * @todo переделать на таблицу
   */
  protected function checkCountGameToday($client_id)
  {
    static $count_game = [];

    if (defined('OPEN_MANY_HOURS_IN_DAY') && OPEN_MANY_HOURS_IN_DAY) {
      if (empty($count_game[$this->date])) {
        $count_game[$this->date] = $this->engine->clients->getQuantityReservationsClients(
          $this->type_id,
          $this->sport_id,
          date('Y-m-d', strtotime($this->date)) . ' 00:00',
          date('Y-m-d', strtotime($this->date)) . ' 23:59'
        );
      }
      if (isset($count_game[$this->date][$client_id]) && $count_game[$this->date][$client_id] >= OPEN_MANY_HOURS_IN_DAY) {
        return false;
      }
    }

    return true;
  }

  protected function checkConsecutiveBookings($countBooking = 1)
  {
    return empty(array_diff($this->times,
      TimeHelper::generateTimeArraySequentiallyForGivenNumberOfPeriods($this->time_start, $countBooking, $this->areas->period, false)));
  }


  protected function checkMaximumNumberOfConsecutivePeriods(&$error): bool
  {
    $consecutivePeriod = $this->engine->rules->maximumNumberOfConsecutivePeriods($this->area_id, $this->type_id, $this->sport_id,
      $this->current_client->club_state, $this->getDeviceType());
    if ($consecutivePeriod) {
      foreach (TimeHelper::countTheNumberOfConsecutivePeriods($this->times, $this->areas->period) as $countPeriods) {
        if ($countPeriods > $consecutivePeriod) {
          $error = lang('Maximum number of consecutive intervals', 'message_error', [
            'count_period' => $consecutivePeriod,
          ]);

          return false;
        }
      }
    }

    return true;
  }

  /** Проверка возможности бронирования по временному промежутку и если площадка доступна для бронирования онлайн
   *
   * @param $error_message -  возвращаемое сообщение об ошибке
   *
   * @return bool
   */
  public function checkOrderOfTimeRange(&$error_message)
  {
    return $this->current_client->super || ($this->engine->checkingForReservationsBasedOnTimeConditions(
          $this->date,
          $this->time,
          $this->type_id,
          $error_message,
          $this->touch,
          $this->sport_id
        ) && $this->engine->getLastReserv(
          $this->date,
          $this->time,
          $this->type_id,
          $this->area_id,
          $this->touch
        ));
  }

  public function checkNegativeValueStocks(): bool
  {
    return !USE_NEW_STRATEGY_OPTION && !empty($this->stock_id) && ReservServiceLocator::priceOptions()->checkNegativeValueStocks($this->getStocks());
  }
}
