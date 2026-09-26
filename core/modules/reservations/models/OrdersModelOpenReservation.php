<?php

namespace AC\core\modules\reservations\models;

use AC\core\engines\ExtraEngine;
use AC\core\modules\clients\models\ClientsModel;
use AC\core\modules\config\models\ConfigClubStateModel;
use AC\core\modules\config\models\ConfigOpenTypeModel;
use AC\core\modules\webio\models\WebIoModel;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\TranslateHelper;
use Service;


/** Данные заказа открытого корта и присоединения игроков. */
class OrdersModelOpenReservation extends OrdersModelReservation
{
  /** Максимальное количество бронирований
   *
   * @var int|false
   */
  public $max_count_reservation = OPEN_MANY_HOURS;

  /** Система где за все платит первый игрок и сумма платежа рассчитывается сразу
   *
   * @var bool
   */
  public $main_customer_pays = false;
  /**
   * @var bool Открытые корты переключатель
   */
  public $open = true;
  const DOOR_CODE = false;

  /**
   * @var string - имя второго игрока или гостя
   */
  public $client2_name     = null;
  public $second_player_id = 0;

  /**
   * @var bool Есть ли гость хоть один в игроках
   */
  public $checkGuest = false;

  /** Загрузить данные присоединения после основных данных заказа. */
  protected function initializeData(): void
  {
    parent::initializeData();
    $this->main_reservation_id = Service::request()->_('main_reservation_id');
  }

  /** Загрузить данные для заказа открытые корты
   *
   *
   * @param $error_code
   *
   * @return array|bool
   */
  public function getOrder(&$error_code)
  {
    if ($content = parent::getOrder($error_code)) {
      $content['times']           = [$this->time];
      $content['guest']           = $this->viewGuestForm();
      $content['checkBlockGuest'] = $this->checkBlockedGuest();

      return $content;
    }

    return false;
  }

  /** Показывать гостевую форму или нет
   *
   * todo доработать чтобы передавались названия полей
   *
   *  сделано проверка по членству для объеденнения, и до update версии
   *  идет проверка для возможности бронировать член + гость только в течение 6 часов от текущего
   *  todo доработать чтобы данные брались из таблицы с правилами
   *
   */
  public function viewGuestForm()
  {
    $guest          = new \stdClass();
    $guest->visible = $this->checkPlayForPLayerByClubSate(0);

    $real_interval     = abs(strtotime($this->date . ' ' . $this->time_start) - strtotime('now'));
    $time_interval_max = OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION_V1_PLUS_GUEST
      ? OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION_V1_PLUS_GUEST * 60 : 0;
    if ($guest->visible && $this->current_client->club_state == 2 && OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION_V1_PLUS_GUEST
      && $real_interval > $time_interval_max) {
      $guest->visible = false;
    }

    return $guest;
  }

  /** Установить цену заказа
   * может зависеть от членства в клубе,
   * от того платит ли за все первый игрок сразу
   * или какие-то другие условия
   *
   * @param bool $ajax - запрос в режиме ajax
   *
   * @return int
   */
  public function setOrderPrice($ajax = false)
  {
    if ($ajax) {
      $this->setTotalCostPayment($chek, $err);
    }

    $price                                               = $this->getOrderPriceByClientId($this->client_id, $this->times[0]);
    $this->sum_price_array[$this->times[0]]['sum_price'] = $price;
    $this->sum_price_array[$this->times[0]]['totalCost'] = $price;
    $this->price                                         = $price;
    $price                                               = $this->getOrderPriceByClientId($this->second_player_id, $this->times[1]);
    $this->price                                         += $price;
    $this->sum_price_array[$this->times[1]]['sum_price'] = $price;
    $this->sum_price_array[$this->times[1]]['totalCost'] = $price;

    return $this->price + $this->state_sum_price['all']['sum_price'];
  }

  public function getOrderPriceByClientId($client_id, $time)
  {
    if (empty($client_id) || $client_id === 0 || $client_id === '0') { // игрок еще не добавлен

      return 0;
    }
    if (defined('PRICE_FOR_GUEST_CHILD') && PRICE_FOR_GUEST_CHILD && $client_id === 'guest-child') {
      return PRICE_FOR_GUEST_CHILD;
    }

    if (config('OpenType')->checkOpenTypeAsClose($this->type_id, $this->sport_id, $this->area_id)) {
      return $this->checkPriceClientForPeriod($client_id, $time, $prices);
    }
    switch (config('OpenType')->getPricingSystemId($this->type_id, $this->sport_id, $this->area_id)) {
      case 'GT':
      case 'DGT':
        if ($client_id === 'guest') { // гость
          return $this->sum_price_array[$time]['price'];
        } else {
          return $this->sum_price_array[$time]['extra']; // все остальные
        }
        break;
      case 'PFP':
        return 0;
        break;
      case 'ST':
      case 'DT':
      default:
        $clubStateGuest = ConfigClubStateModel::getMark('no_club_rate')->id;
        $extraGuest     = ExtraEngine::getExtraRate($clubStateGuest, $this->type_id, $this->sport_id, $this->date, $time);
        if ($client_id !== 'guest') {
          $client = new ClientsModel($client_id);

          if ($client->club_state != $clubStateGuest) {
            return ExtraEngine::getExtraRate($client->club_state, $this->type_id, $this->sport_id, $this->date, $time);
          }
        }

        return $this->sum_price_array[$time]['price'] + $extraGuest;
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
    $rule = $this->engine->rules->rulePriority($this->area_id, $this->type_id, $this->sport_id, $this->current_client->club_state, $device_type);
    if ($this->checkMaxForward($rule, $error_code)) {
      // проверка можно ли бронировать на данном устройстве
      if ($this->checkOrderOnAllowedDevice($rule, $error_code)) {
        // проверка на возможность бронирования по списку недоступных спортов
        if ($this->checkOrderForUnavailableSports($error_code)) {
          // проверка возможности бронирования по рангу клубного членства
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
            $max_count_reservation = isset($rule['reservation_limit']) ? $rule['reservation_limit'] : $this->max_count_reservation;
            //если будет ограничение по конкретной площадке, то функция не сработает. Надо добавить area_id
            $count_game = $countReservation + count(
                $this->engine->getStreetFriendsReservationsByIdOld(
                  $this->current_client->client_id,
                  date('Y-m-d H:i'),
                  false,
                  (!empty($rule['type_id']) && ($this->areas->type_id == $rule['type_id'])) ? $rule['type_id'] : false,
                  (!empty($rule['sport_id']) && ($this->areas->sport_id == $rule['sport_id'])) ? $rule['sport_id'] : false
                )['all']
              );
            //проверка ограничений по кол-ву заказов
            //Sie haben Limit der Buchungen erreicht!
            if ($this->admin
              || $this->current_client->super == 1
              || !$max_count_reservation
              || ($max_count_reservation && $count_game < $max_count_reservation
                && $this->checkCountGameToday($this->current_client->client_id))) {
              if ($this->checkOrderOfTimeRange($error_code)) {
                return true;
              }
            } else {
              $error_code = $rule['error_reservation_limit'];
            }
          }
        }
      }
    }

    return false;
  }

  /** Проверка можно ли бронировать на данном устройстве
   *
   * @param bool $error_code
   *
   * @return bool
   */
  public function checkOrderOnAllowedDevice($rule, &$error_code = false)
  {
    if ($this->admin) { // если запрос из админки , то админу позволено все
      return true;
    } else {
      $device_type = 'pc';
      if ($this->touch) {
        $device_type = 'touch';
      }
      $flg_area_link = !(!empty($rule) && isset($rule['show_on'])) || strtolower($rule['show_on']) == $device_type;
      if (!$flg_area_link) {
        if (!($this->touch || $this->areas->online_reservation == 1)) {
          $error_code = lang('Booking is only possible on the touch screen', 'message_error');
        } else {
          $error_code = $rule['error_show_on'];
        }
      }
    }

    return $flg_area_link;
  }

  protected function runProceed(&$prices, &$error_code)
  {
    $prices[]                  = $this->setOrderPrice();
    $check                     = 0;
    $this->main_reservation_id = null;
    $this->main_client_id      = null;
    $this->setStatus();// проверяем на наличие второго игрока (гостя)
    if (!$this->barClient) {
      $this->client_name    = null;
      $this->client_surname = null;
    }
    $sprice_id = $this->sprice_id;
    $stock_id  = $this->stock_id;
    foreach ($this->times as $key => $this->time) {
      $this->setProceedTime();
      $this->client_id = $this->playersByTime[$this->time] ?: null;
      foreach (array_keys(WebIoModel::getWebIoTypes()) as $state) {
        $this->{$state . '_price'} = $key !== 0 ? '0.00' : $this->state_sum_price[$state]['sum_price'];
        $this->{$state . '_state'} = (int)$this->checkWebIoStateByTime($state, $this->time);
      }
      if ($key != 0) {
        $this->price          = '0.00';
        $client_id            = $this->playersByTime[$this->times[0]];
        $this->main_client_id = $client_id == 'guest' ? null : $client_id;
        $this->client_name    = !$this->playersByTime[$this->time] || $this->playersByTime[$this->time] == 'guest'
          ? (isset($this->street_friends[$key + 1]) ? $this->street_friends[$key + 1]['name'] : $this->client2_name)
          : null;
        $this->client_surname = null;
      }
      $this->sprice_id = $this->sum_price_array[$this->time]['spec_price'] !== null ? $sprice_id : null;
      $this->stock_id  = $this->sum_price_array[$this->time]['stock'] !== null ? $stock_id : null;
      // перейти к процессу бронирования
      $this->insertionProcess($check, $error_code);

      if ($key == 0) {
        $this->main_reservation_id = $this->inserted_reservation_id;
      }
    }
    if ($check != 0) {
      foreach ($this->res_ids as $id) {
        $this->engine->removeReservationById($id);
      }
    }
    $this->engine->webIo->sendFTPCurrentIcal($this->engine, $this->area_id, $this->getDate());

    return $check == 0;
  }


  /** Проверка присоеденятеся к бронированю гость
   *  если да то генерится имя для гостя
   *
   */
  public function checkGuestPlayer()
  {
    $second_player = Service::request()->_('second_player');
    $guest_name    = Service::request()->_('guest_name');
    $guest_surname = Service::request()->_('guest_surname');
    if ($second_player !== null && ($second_player == 'guest' || $second_player == 'guest-child')) {
      $this->client2_name     = $this->generatePlayerName($guest_name, $guest_surname);
      $this->second_player_id = 'guest';

      return true;
    }

    return false;
  }

  /**
   *  Выбран ли второй игрок для игры
   */
  public function checkSecondPlayer()
  {
    // проверка если второй игрок гость
    if ($this->checkGuestPlayer()) {
      return true;
    }

    // здесь логика если есть возможность выбрать второго игрока из списка доступных игроков

    return false;
  }

  /** Получить данные для вывода формы присоеденения
   *
   * @param $error_code
   *
   * @return array|bool
   */
  public function getDataJoinForm(&$error_code)
  {
    // общие проверки на доступность данных
    if ($this->checkData($error_code)) {
      // проверка на разрешение присоедеенения
      if ($this->checkJoin($error_code)) {
        $unix_time   = strtotime($this->date . ' ' . $this->time);
        $_times      = [];
        $time_start  = date('H:i', strtotime('- ' . $this->areas->period . ' minutes', $unix_time));
        $time_finish = date('H:i', strtotime('+ ' . $this->areas->period . ' minutes', $unix_time));
        $_times[]    = (object)[
          'title' => $time_start . ' - ' . $time_finish,
        ];
        $content     = [
          'model' => $this,
          'date'  => (object)[
            'date'    => date('d.m.Y', $unix_time),
            'weekday' => TranslateHelper::translateWeekday(CalendarHelper::getWeekdayByUnixtime($unix_time)),
            'times'   => $_times,
          ],
        ];

        return $content;
      }
    }

    return false;
  }

  /** Проверки на возможность присоеденения
   *
   * @param $error_code
   *
   * @return bool
   */
  public function checkJoin(&$error_code = false)
  {
    $device_type = 'pc';
    if ($this->touch) {
      $device_type = 'touch';
    }
    $rule = $this->engine->rules->rulePriority($this->area_id, $this->type_id, $this->sport_id, $this->current_client->club_state, $device_type);

    $this->engine->getCountReservationByClient(
      $this->current_client->client_id,
      date('Y-m-d H:i'),
      false,
      $countReservation,
      $this->date . ' ' . $this->time,
      $this->type_id,
      isset($rule['sport_id']) ? $rule['sport_id'] : null
    );
    //проверка ограничений по кол-ву заказов, или у клиента статус супер то он может бронировать любое количество
    //Sie haben Limit der Buchungen erreicht!

    $max_count_reservation = isset($rule['reservation_limit']) ? $rule['reservation_limit'] : $this->max_count_reservation;
    //если будет ограничение по конкретной площадке, то функция не сработает. Надо добавить area_id
    $count_game = $countReservation + count(
        $this->engine->getStreetFriendsReservationsByIdOld(
          $this->current_client->client_id,
          date('Y-m-d H:i'),
          false,
          (!empty($rule['type_id']) && ($this->areas->type_id == $rule['type_id'])) ? $rule['type_id'] : false,
          (!empty($rule['sport_id']) && ($this->areas->sport_id == $rule['sport_id'])) ? $rule['sport_id'] : false
        )['all']
      );

    if ($this->admin
      || $this->current_client->super == 1
      || !$max_count_reservation
      || ($max_count_reservation && $count_game < $max_count_reservation)) {
      return true;
    } else {
      $error_code = lang('You have reached your limit of bookings!', 'message_error');

      return false;
    }
  }

  /** Обработка процесса присоеденения игрока
   *
   * @param $error_code
   *
   * @return array|bool
   */
  public function joinProceed(&$error_code)
  {
    // общие проверки на доступность данных
    if ($this->checkData($error_code)) {
      // проверка на разрешение присоедеенения
      if ($this->checkJoin($error_code)) {
        // получить цену присоеденения
        if (!$this->getJoinPrice($error_code)) {
          return false;
        }
        if ($this->engine->joinReservation(
          (int)$this->main_reservation_id,
          1,
          $this->price,
          $this->current_client->client_id,
          $this->getClientNameForJoin()
        )) {
          return true;
        } else {
          $error_code = lang('Joining failed', 'message_error');

          return false;
        }
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

  /** Получить цену за присоеденение к бронированию , берется цена без надбавок ??? с этим не понятно почему
   *
   * @param                   $error_code
   *
   * @param null|ClientsModel $client - можно проверить взять цену не для
   *
   * @return bool
   */
  public function getJoinPrice(&$error_code, $client = null)
  {
    if (!$this->areas->checkError) {
      if (!$this->main_customer_pays) {
        // если за все уже не заплатил первый чувак
        if ($this->engine->areas->getAreaPrice(
            $this->area_id,
            $result,
            $error_code
          ) && ($period_id = $this->engine->areas->getPeriodByDate(
            date(
              'Y-m-d',
              strtotime($this->date)
            )
          ))) {
          // если не передана модель клиента, то берем клиента который авторизован в данный момент
          if ($client === null) {
            $client = $this->current_client;
          }
          if (!$client->checkError && $client->club_state != ConfigClubStateModel::getMark('no_club_rate')->id) {
            // если присоеденяется член клуба то стоимость равна 0
            $this->price = false;
          } else {
            $this->price = $result[CalendarHelper::getWeekdayByUnixtime(strtotime($this->date))][2][$period_id][$this->time];
          }
        } else {
          return false;
        }
      } else {
        //  если за все заплатил первый чувак
        $this->price = false;
      }
    } else {
      $error_code = lang('Not enough data', 'message_error');

      return false;
    }

    return true;
  }

  /** Получить имя присоеденившегося клиента
   *
   * @return string| null
   */
  public function getClientNameForJoin()
  {
    $client2_name = null;
    // todo пересмотреть возможность левого клиента
    //        if ($client_type == 3) {
//          $client2_name = $_SESSION['reservation_comment'];
//        }
    $client2_name = $this->current_client->name . ' ' . $this->current_client->surname;

    return $client2_name;
  }

  /** процесс отсоеденения игрока от игры
   *
   * @param $error_code
   *
   * @return bool
   */
  public function unJoin(&$error_code)
  {
    // общие проверки на доступность данных
    if ($this->checkData($error_code)) {
      if (!$this->getJoinPrice($error_code)) {
        return false;
      }
      if ($this->engine->unjoinReservation($this->main_reservation_id, 0, $this->price)) {
        $error_code = lang('fail', 'message_error');

        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

  protected function setPlayersByTime()
  {
    $this->checkSecondPlayer();
    if (!empty($this->times)) {
      $this->playersByTime[$this->times[0]] = $this->client_id;
      $this->playersByTime[$this->times[1]] = $this->second_player_id;
    }
  }

  protected function checkPlayForPLayerByClubSate($clubSateOtherPlayer)
  {
    return config('OpenType')->checkCombination(
      $this->current_client->club_state,
      $clubSateOtherPlayer,
      $this->type_id,
      $this->sport_id,
      $this->area_id,
    );
  }

  public function setStatus($status = 1)
  {
    if ($this->second_player_id === 0) {
      $status = 0;
    }

    if (defined('CONFIRMATION_OF_RESERVATIONS_VIA_TOUCH_FOR_OPEN')
      && CONFIRMATION_OF_RESERVATIONS_VIA_TOUCH_FOR_OPEN
      && (!$this->touch || strtotime(date('Y-m-d')) < strtotime($this->date))) {
      $status = 0;
    }

    parent::setStatus($status);
  }

  public function checkConfirmOrder(&$error_message = 0)
  {
    if ($this->touch && $this->open && defined('CONFIRMATION_OF_RESERVATIONS_VIA_TOUCH_FOR_OPEN')
      && CONFIRMATION_OF_RESERVATIONS_VIA_TOUCH_FOR_OPEN
      && strtotime($this->date) == strtotime(date('Y-m-d'))
      && $this->engine->getReservationData($this->area_id, $this->date . ' ' . $this->time, $reservation_data)
    ) {
      if ($reservation_data['client_id'] == $this->current_client->client_id) {
        if ($this->engine->checkTimeConfirmationOfReservations($this->time, CONFIRMATION_OF_RESERVATIONS_VIA_TOUCH_FOR_OPEN)) {
          return true;
        }
      }

      return true;
    }

    return false;
  }

  public function confirmOrder(&$error_message = 0)
  {
    if ($this->checkConfirmOrder($error_message)) {
      if ($this->engine->setStatusReservations($this->reservation_id, 1)) {
        return true;
      }
    }

    return false;
  }

  public function checkBlockedGuest()
  {
    $device_type = 'pc';
    if ($this->touch) {
      $device_type = 'touch';
    }
    $rule = $this->engine->rules->rulePriority($this->area_id, $this->type_id, $this->sport_id, $this->current_client->club_state, $device_type);

    if (isset($rule['rule_type'])
      && $rule['rule_type'] == 'blockGuest'
      && (empty($rule['weekdays']) || in_array(CalendarHelper::getWeekdayByUnixtime(strtotime($this->date)), explode(',', $rule['weekdays'])))
      && (empty($rule['time_start']) || strtotime($rule['time_start']) <= strtotime($this->time_start))
      && (empty($rule['time_finish']) || strtotime($rule['time_finish']) >= strtotime($this->time_finish))) {
      return (bool)$rule['rule_value'];
    }

    return false;
  }
}
