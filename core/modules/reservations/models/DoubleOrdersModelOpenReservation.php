<?php

namespace AC\core\modules\reservations\models;

use AC\core\modules\clients\models\ClientsModel;
use AC\core\modules\config\models\ConfigClubStateModel;
use AC\core\modules\reservations\config\ReservationsConfig;
use AC\core\system\helpers\TimeHelper;
use Service;


/**
 *  Открытые корты возможность игры 4 игроками
 *  если ни кто из дополнительных игроков не выбран то они добавляются как гости
 *  и к сумме игры добавляется цена одного гостя,
 *  если гостей нет, то к сумме за игру добавляется цена для первого добавленного игрока
 *
 * Class DoubleOrdersModelOpenReservation
 */
class DoubleOrdersModelOpenReservation extends OrdersModelOpenReservation
{
  /**
   * @var int Тип игры 1 - одиночна (2 игрока), 2 - двойная (4 игрока).
   *          По умолчанию одиночная игра
   */
  public $type_reservation = 1;
  /**
   * @var array Массив дополнительных игроков
   *    ['id'] - ид клиента или guest если играет гость
   *    ['name'] - имя игрока
   *    ['price'] - стоимость одного периода для этого игрока
   *    ['email'] - email этого игрока
   */
  public $street_friends = array();

  /**
   * @var bool Есть ли гость хоть один в игроках
   */
  public $checkGuest = false;

  public $countPlayersByClubSate = array();

  /**
   *  Количество периодов для двойной игры
   *  по умолчанию берем из константы DOUBLE_PLAYERS_TIME_COUNT
   * @var int
   */
  protected $numberOfPeriods;

  /** Распределение промежутков времени по номеру игрока к которому он принадлежит
   * @var array
   */
  protected array $timePeriodsForPlayers = [];


  public function setTypeReservation()
  {
    $this->type_reservation = Service::request()->_('type_reservation', $this->type_reservation);
    if (!empty($this->date) && !empty($this->time)) {
      $this->engine->getReservationType(
        date('Y-m-d H:i:s', strtotime($this->date . ' ' . $this->time)),
        $this->area_id,
        $this->type_reservation
      );
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getOrder(&$error_code)
  {
    if ($content = parent::getOrder($error_code)) {
      $content['use_double'] = $this->checkMaxNumberPeriodsForDoublePlay();
      // получить клиентов которые имеют доступ к открытым кортам
      $count_game = $this->engine->clients->getQuantityReservationsClients(
        $this->type_id,
        $this->sport_id,
        date('Y-m-d H:i')
      );
      foreach (
        $this->engine->clients->getClientsAllowedPlayArea(
          $this->type_id,
          $this->sport_id,
          $this->date
        ) as $client_id => $client
      ) {
        if (($client->super == '1') || ($client_id != $this->current_client->client_id && (!isset($count_game[$client_id])
              || !$this->max_count_reservation || (isset($count_game[$client_id])
                && $this->max_count_reservation && $count_game[$client_id] < $this->max_count_reservation
              )
            )
            && $this->checkPlayForPLayerByClubSate($client->club_state)
            && $this->checkCountGameToday($client_id)
          )) {
          $content['clients'][$client_id] = $client;
        }
      }

      return $content;
    }

    return false;
  }

  /**
   *  Проверить выбран ли второй игрок.
   *  Здесь логика такая , если даже никто не выбран то игрок играет с гостем
   *
   * {@inheritDoc}
   */
  public function checkSecondPlayer()
  {
    $this->renderFriendsPlayer();
    // имя для второго бронирования назначаем первого добавленного игрока
    $this->client2_name = isset($this->street_friends[2]) ? $this->street_friends[2]['name'] : null;
    // если никто не выбран, то игрок играет с гостем
    if ($this->checkGuest && !$this->second_player_id) {
      $this->second_player_id = 'guest';
    }

    return true;
  }

  /**
   * Определить друзей основного игрока (с кем он играет)
   */
  public function renderFriendsPlayer()
  {
    $friends  = Service::request()->_('friends', array());
    $names    = Service::request()->_('guest_name');
    $surnames = Service::request()->_('guest_surname');
    foreach ($friends as $key => $friend) {
      if ($this->type_reservation * 2 >= $key) {
        $id = $friend == 'guest' || $friend == 'guest-child' ? null : (int)$friend;
        if ($id === null) {
          $this->checkGuest = true;
        }
        if ($key == 2) {
          $this->second_player_id = $friend;
        }
        $client                     = new ClientsModel($id);
        $name                       = ($id !== null ? $client->name : $names[$key]);
        $surname                    = $id !== null ? $client->surname : $surnames[$key];
        $prefix                     = $id === null && empty($name) && empty($surname) ? ' ' . $key : null;
        $this->street_friends[$key] = array(
          'id'         => $friend == 'guest' || $friend == 'guest-child' ? 'guest' : $id,
          'name'       => addslashes($friend == 'guest-child' ? lang('Guest 2 (child)', 'show_order') : $this->generatePlayerName($name, $surname,
            $prefix)),
          'club_state' => $friend == 'guest' || $friend == 'guest-child' ? 0 : $client->club_state,
          'email'      => addslashes($client->email),
          'mode'       => !empty($client->mode) ? $client->mode : 3
        );
      }
    }

    $this->setCountPlayers();
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
    $rule = $this->engine->rules->rulePriority(
      $this->area_id,
      $this->type_id,
      $this->sport_id,
      $this->current_client->club_state,
      $device_type
    );
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
            $max_count_reservation = isset($rule['reservation_limit']) ? $rule['reservation_limit'] : $this->max_count_reservation;
            //если будет ограничение по конкретной площадке, то функция не сработает. Надо добавить area_id
            $count = $countReservation ?: 0;
            $count_game        = $count + count(
                $this->engine->getStreetFriendsReservationsById(
                  $this->current_client->client_id,
                  date('Y-m-d H:i'),
                  false,
                  (!empty($rule['type_id']) && ($this->areas->type_id == $rule['type_id'])) ? $rule['type_id'] : false,
                  (!empty($rule['sport_id']) && ($this->areas->sport_id == $rule['sport_id'])) ? $rule['sport_id'] : false
                )['main']
              );
            $flg_friends_count = true;
            //проверка друзей на количество игр
            foreach ($this->street_friends as $friend) {
              $this->engine->clients->getClientData(
                $friend['id'],
                $friend_data
              );
              if (isset($friend_data['super']) && $friend_data['super'] == 1 || $friend['id'] === 'guest') {
                continue;
              }

              $this->engine->getCountReservationByClient(
                $friend['id'],
                date('Y-m-d H:i'),
                false,
                $countFriendReservation,
                $this->date . ' ' . $this->time_finish,
                $this->type_id,
                isset($rule['sport_id']) ? $rule['sport_id'] : null
              );

              $reservations_data_fr = $this->engine->getStreetFriendsReservationsById(
                $friend['id'],
                date('Y-m-d H:i'),
                false,
                (!empty($rule['type_id']) && ($this->areas->type_id == $rule['type_id'])) ? $rule['type_id'] : false,
                (!empty($rule['sport_id']) && ($this->areas->sport_id == $rule['sport_id'])) ? $rule['sport_id'] : false
              );
              $flg_friends_count    = ((count($reservations_data_fr['main']) + $countFriendReservation) < $max_count_reservation);

              if (!$flg_friends_count) {
                break;
              }
            }
            //проверка ограничений по кол-ву заказов
            //Sie haben Limit der Buchungen erreicht!
            if ($this->admin
              || $this->current_client->super == 1
              || !$max_count_reservation
              || ($max_count_reservation && $count_game < $max_count_reservation && $flg_friends_count)
            ) {
              if ($this->checkOrderOfTimeRange($error_code)) {
                return true;
              }
            } else {
              $error_code = lang('You have reached your limit of bookings!', 'message_error');
            }
          }
        }
      }
    }

    return false;
  }

  public function setOrderPrice($ajax = false)
  {
    parent::setOrderPrice($ajax);
    $openTypeAsClose = config('OpenType')->checkOpenTypeAsClose($this->type_id, $this->sport_id, $this->area_id);
    if (config('OpenType')->isPricingSystem('price_for_player', $this->type_id, $this->sport_id, $this->area_id)) {
      $this->price = $this->setOrderPriceForPlayers();
    } elseif ($this->type_reservation == 2 && config('DoubleGame')->priceCountPlayers($this->type_id, $this->sport_id, $this->area_id) > 2) {
      $this->price = 0;
      $clients     = $this->playersByTime;
      if ($openTypeAsClose) {
        $clients = [$this->client_id];
        foreach ($this->street_friends as $friend) {
          $clients[] = $friend['id'];
        }
      }
      $guest = false;
      $t     = 0;
      $priceCountPlayers = config('DoubleGame')->priceCountPlayers($this->type_id, $this->sport_id, $this->area_id);
      for ($i = 0; $i < $priceCountPlayers; $i++) {
        if (count($this->times) <= $t) {
          $t = 0;
        }
        $keyClient = $openTypeAsClose ? $i : ($this->times[$i] ?? null);
        $currentClient = $this->chargeForAllPlayers() ? $this->timePeriodsForPlayers[$i]['id'] : $clients[$keyClient];
        $currentTime = $this->chargeForAllPlayers() ? $this->timePeriodsForPlayers[$i]['time'] : $this->times[$t];
        if (isset($currentTime) && isset($currentClient)) {
          if ($clients[$keyClient] === 'guest') {
            $guest = true;
          }
          $price = $this->getOrderPriceByClientId($currentClient, $currentTime);
          if ($openTypeAsClose
            && config('DoubleGame')->useMaximumPeriodValue($this->type_id, $this->sport_id, $this->area_id)
            && $this->getNumberOfPeriods() > config('DoubleGame')->getNumberOfPeriods($this->type_id, $this->sport_id, $this->area_id)) {
            $price += ($price / 2) * ($this->getNumberOfPeriods() - config('DoubleGame')->getNumberOfPeriods($this->type_id, $this->sport_id, $this->area_id));
          }
          $price = round(max($price, 0), 2);
          $this->sum_price_array[$this->times[$t]]['sum_price'] = $price;
          $this->price                                          += $price;
        }
        $t++;
      }
      //  если в друзьях был хоть один гость но он не попал в список клиентов которые платят за него тоже платим
      if (defined('ALWAYS_PAY_AT_LEAST_FOR_ONE_GUEST') && ALWAYS_PAY_AT_LEAST_FOR_ONE_GUEST && !$guest && $this->checkGuest) {
        $this->price += $this->getOrderPriceByClientId('guest', $this->times[0]);
      }
    }

    return $this->price + $this->state_sum_price['all']['sum_price'];
  }

  public function setOrderPriceForPlayers()
  {
    $this->price = 0;
    $prices      = $this->engine->areas->getPricesForPlayers(
      $this->type_id,
      $this->sport_id,
      $this->area_id,
      "$this->type_reservation",
      $this->current_client->club_state
    );
    if ($this->type_reservation == 2
      && $this->isDoubleCountOtherPlayersOnly()
    ) {
      $this->price += (float)$prices[$this->type_reservation][$this->current_client->club_state][$this->current_client->club_state][1]['price'];
    }
    foreach ($this->countPlayersByClubSate as $club_sate => $count) {
      $this->price += (float)$prices[$this->type_reservation][$this->current_client->club_state][$club_sate][$count]['price'];
    }

    return $this->price;
  }

  protected function isDoubleCountOtherPlayersOnly(): bool
  {
    return config('OpenType')->isDoubleCountOtherPlayersOnly($this->type_id, $this->sport_id, $this->area_id);
  }

  public function renderPricesBlock()
  {
    $result                = parent::renderPricesBlock();
    $result['time_start']  = $this->time_start;
    $result['time_finish'] = $this->time_finish;

    return $result;
  }

  public function checkMaxNumberPeriodsForDoublePlay(): bool
  {
    for ($i = 1; $i < $this->getNumberOfPeriods(); $i++) {
      if (!$this->engine->checkAreaDateTimeAvailable(
        $this->area_id,
        $this->client_id,
        $this->date,
        date('H:i', strtotime($this->date . ' ' . $this->time . ' + ' . ($this->areas->period * $i) . ' minutes')),
        $error_code
      )
      ) {
        return false;
      }
    }

    return true;
  }

  public function getTimes()
  {
    parent::getTimes();
    $this->setTypeReservation();
    $this->setNumberOfPeriods(Service::request()->_('numberOfPeriods', config('DoubleGame')->getMaxNumberOfPeriods($this->type_id, $this->sport_id, $this->area_id)));
    if ($this->type_reservation == 2 && $this->getNumberOfPeriods() > 2) {
      if ($this->areas->period == '15') {
        $start = 3;
      } else {
        $start = 1;
      }
      for ($i = $start; $i < $this->getNumberOfPeriods() - 1; $i++) {
        $time2             = TimeHelper::convertMinutes2MySQLTime(TimeHelper::convertMySQLTimeToMinutes($this->times[$i]) + $this->areas->period);
        $this->times[]     = $time2;
        $this->time_finish = TimeHelper::convertMinutes2MySQLTime(TimeHelper::convertMySQLTimeToMinutes($time2) + $this->areas->period);
      }
    }

    return $this->times;
  }

  /**
   * @return int
   */
  public function getNumberOfPeriods(): int
  {
    return $this->numberOfPeriods ?? config('DoubleGame')->getMaxNumberOfPeriods($this->type_id, $this->sport_id, $this->area_id);
  }

  /**
   * @param int $numberOfPeriods
   */
  public function setNumberOfPeriods(int $numberOfPeriods): void
  {
    $this->numberOfPeriods = $numberOfPeriods;
  }

  /** Задать клиентов по промежуткам
   * @return void
   */
  protected function setPlayersByTime(): void
  {
    parent::setPlayersByTime();
    if ($this->type_reservation == 2
      && ($this->getNumberOfPeriods() > 2 || $this->chargeForAllPlayers())) {
      $t = 0;
      $priceCountPlayers = config('DoubleGame')->priceCountPlayers($this->type_id, $this->sport_id, $this->area_id);
      for($i = 0; $i < ($this->chargeForAllPlayers() ? $priceCountPlayers : count($this->times)); $i++ ) {
        if (count($this->times) <= $t) {
          $t = 0;
        }
        $id = $i === 0 ? $this->client_id : $this->street_friends[$i + 1]['id'];
        if($this->getNumberOfPeriods() > $i) {
          $this->playersByTime[$this->times[$t]] = $id;
        }
        $this->timePeriodsForPlayers[$i] = ['id' => $id, 'time' => $this->times[$t]];
        $t++;
      }
    }
  }

  protected function getStateLHN($states = array(), $times = array(), $disable = 0, $weekday = null)
  {
    $maxCountPeriods = config('DoubleGame')->getMaxNumberOfPeriods($this->type_id, $this->sport_id, $this->area_id);
    $times           = array();
    if ($maxCountPeriods > 2 && count($this->times) < $maxCountPeriods) {
      $time = $this->times[count($this->times) - 1];
      if ($this->areas->period == '15') {
        $start = 4;
      } else {
        $start = 2;
      }
      for ($i = $start; $i < $maxCountPeriods; $i++) {
        $time    = TimeHelper::convertMinutes2MySQLTime(TimeHelper::convertMySQLTimeToMinutes($time) + $this->areas->period);
        $times[] = $time;
      }
    }
    return empty($times)
      ? parent::getStateLHN(array(), array(), 0, $weekday)
      : parent::getStateLHN(parent::getStateLHN(array(), array(), 0, $weekday), $times, 1, $weekday);
  }

  protected function setCountPlayers()
  {
    $clubStates = ConfigClubStateModel::getMarks(true, 'mark');
    foreach ($this->street_friends as $friend) {
      if ($friend['club_state'] !== null) {
        $club_state = $friend['club_state'];
        if (config('OpenType')->isGuestAsNoMember($this->type_id, $this->sport_id, $this->area_id)
          && $club_state == $clubStates['guest']->id) { // условие если приравниваем гостя к нечлену
          $club_state = $clubStates['no_club_rate']->id;
        }
        if (!isset($this->countPlayersByClubSate[$club_state])) {
          $this->countPlayersByClubSate[$club_state] = 0;
        }
        $this->countPlayersByClubSate[$club_state]++;
      }
    }
    /** Условие если главный - нечлен или гость, и есть в других игроках гость или нечлен, то цену берем только за них */
    if (config('OpenType')->isMainNoMemberPriceForNoMember($this->type_id, $this->sport_id, $this->area_id)
      && ($this->current_client->club_state == $clubStates['no_club_rate']->id || $this->current_client->club_state == $clubStates['guest']->id)
      && (isset($this->countPlayersByClubSate[$clubStates['no_club_rate']->id]) || isset($this->countPlayersByClubSate[$clubStates['guest']->id]))
    ) {
      foreach ($this->countPlayersByClubSate as $club_state => $count) {
        if ($club_state != $clubStates['no_club_rate']->id
          && $club_state != $clubStates['guest']->id) {
          unset($this->countPlayersByClubSate[$club_state]);
        }
      }
    }
  }

  /** Брать цену со всех игроков
   * @return bool
   */
  public function chargeForAllPlayers(): bool
  {
    return config('OpenType')->isPricingSystem('standard_for_players', $this->type_id, $this->sport_id, $this->area_id);
  }
}
