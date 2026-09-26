<?php

namespace AC\core\engines;

use AC\core\modules\clients\models\ClientsModel;
use AC\core\modules\payment\payone\services\PayoneService;
use AC\core\system\db\Query;

use AC\core\system\helpers\JsonHelper;
use AC\core\system\helpers\StringHelper;
use AC\core\system\helpers\TimeHelper;
use Service;

useClass('engines\reservation.clients');

class ClientsEngine extends \reservation_clients
{
  public function __construct()
  {
    $this->initialize();
  }
  
  function initialize()
  {
    //старт сессии
    //session_start ();
    //если юзер залогинен пытаемся получить его данные
    if (isset ($_SESSION['malsch_session_client_id_' . SESSION_COURT]) && $_SESSION['malsch_session_client_id_' . SESSION_COURT] == 'bar') {
    } elseif (isset ($_SESSION['malsch_session_client_id_' . SESSION_COURT]) && (
        $this->getClientData(
          $_SESSION['malsch_session_client_id_' . SESSION_COURT],
          $this->current_client_data
        ) == false ||
        $this->current_client_data['mode'] != 1 ||
        $this->current_client_data['active'] != 1)
    ) {
      $this->logOut();
    }
  }
  
  public function insert($model)
  {
    $fields = $placeholderas = '';
    $values = [];
    
    foreach ($model->attributes() as $attribute) {
      if ($model->{$attribute} !== null) {
        $fields        .= ($fields != '' ? ', ' : '') . $attribute;
        $placeholderas .= ($placeholderas != '' ? ', ' : '') . '?';
        $values[]      = $model->$attribute;
      }
    }
    $query = 'insert into ' . Query::tableName('clients') . ' (' . $fields . ') values (' . $placeholderas . ')';
    if ($values != '' && $fields != '' && Query::sqlQuery($query, $values, false)) {
      return (int)Query::getLastId();
    }
    
    return false;
  }
  
  /**
   * @param ClientsModel $model
   *
   * @return bool
   */
  public function update($model)
  {
    $query_set = '';
    $params    = [];
    $old       = $model->getOldAttribute();
    foreach ($model->attributes() as $attribute) {
      if ($attribute != $model->getPrimaryKey() && $model->$attribute != $old[$attribute]) {
        $query_set .= ($query_set != '' ? ', ' : '') . $attribute . '=?';
        $params[]  = $model->$attribute;
      }
    }
    
    if ($query_set === '') {
      return true;
    }
    $pk = $model->getPrimaryKey();
    $params[] = $model->{$model->getPrimaryKey()};

    return Query::sqlQuery(
      'update ' . Query::tableName('clients') . ' set ' . $query_set . ' where ' . $pk . ' = ?',
      $params,
      false
    );
  }
  
  
  /** Зарегистрировать Левого клиента
   *
   * @param $name
   * @param $surname
   * @param $email
   * @param $error_code
   *
   * @return bool
   */
  public function loginBar($name, $surname, $email, &$error_code, $pay_pal = true)
  {
    $_SESSION['email']   = $email;
    $_SESSION['name']    = StringHelper::shield($name);
    $_SESSION['surname'] = StringHelper::shield($surname);
    if ($this->checkClientNameSurname($name, $surname, $email, $error_code)) {
      $_SESSION['malsch_session_client_id_' . SESSION_COURT] = 'bar';
      $_SESSION['reservation_comment']                       = StringHelper::shield($name . ' ' . $surname);
      $_SESSION['paypal_true']                               = $pay_pal;
      
      return true;
    }
    
    return false;
  }
  
  public function isBarPayPal()
  {
    if ($_SESSION['paypal_true']) {
      return true;
    }
    
    return false;
  }
  
  /** Проверка на Левого клиента
   *
   * @return bool
   */
  public function isBar()
  {
    if ($_SESSION['malsch_session_client_id_' . SESSION_COURT] == 'bar') {
      return true;
    }
    
    return false;
  }
  
  /** Получение имени клиента
   *
   * @return string
   */
  public function getClientName($full = false, $client_id = null)
  {
    if ($this->isBar()) {
      return $_SESSION['name'];
    } elseif (isset($this->current_client_data['name'])) {
      return $this->current_client_data['name'];
    }
    
    return "";
  }
  
  /** Задать текущего клиента по его ид
   *
   * @param $client_id
   */
  public function setCurrentClient($client_id)
  {
    $this->getClientData($client_id, $this->current_client_data);
  }
  
  /** Проверить заполненость полей
   *
   * @param $name
   * @param $surname
   *
   * @param $email
   *
   * @param $error_code
   *
   * @return bool
   */
  function checkClientNameSurname($name, $surname, $email = null, &$error_code = 0)
  {
    if ($name == null || strlen(trim($name)) < 3) {
      $error_code = lang('Enter the name more than 3 characters', 'message_error');
      
      return false;
    }
    
    if ($surname == null || strlen(trim($surname)) < 3) {
      $error_code = lang('Enter the surname more than 3 characters', 'message_error');
      
      return false;
    }
    if (!$this->checkEmail($email)) {
      $error_code = lang('You have entered an incorrect e-mail.', 'message_error');
      
      return false;
    }
    
    return true;
  }
  
  /** Выйти
   * @return bool
   */
  function logOut()
  {
    unset($_SESSION['name']);
    unset($_SESSION['email']);
    unset($_SESSION['surname']);
    unset($_SESSION['paypal_true']);
    unset ($_SESSION['reservation_comment']);
    unset ($_SESSION['malsch_session_client_id_' . SESSION_COURT]);
    unset ($this->current_client_data);
    unset ($this->cache_client_data);
    PayoneService::session()->unsetValidKeys();
    
    return true;
  }
  
  /**
   * todo переделать возможно она не нужна и пользоваться функцией ниже
   */
  public function getClientsByAreaType($area_type)
  {
    $where = 'c.area_type ';
    if (is_array($area_type)) {
      $where .= 'in (';
      $i     = 0;
      foreach ($area_type as $type) {
        $where .= ($i === 0 ? '' : ', ') . "'" . $type . "'";
        $i++;
      }
      $where .= ')';
    } else {
      $where .= "='" . $area_type . "'";
    }
    $q      = 'select * from ' . Query::tableName('clients') . ' c where ' . $where . ' and active = 2 and mode = 1 order by surname, name';
    $temp   = Query::sqlQuery($q);
    $result = [];
    if (!empty($temp)) {
      foreach ($temp as $row) {
        if (!empty($row['unavailable_sports'])) {
          $row['unavailable_sports'] = explode(';', $row['unavailable_sports']);
        } else {
          $row['unavailable_sports'] = null;
        }
        $result[$row['client_id']] = (object)$row;
      }
    }
    
    return $result;
  }
  
  public function getClientsAllowedPlayArea($type_id, $sport_id, $date)
  {
    $result    = [];
    $useSeason = (bool)Service::configDB('registration', 'use_season_in_choosing_type_sport');
    if ($useSeason) {
      $season_id = module('areas')->useModel()->getEngine()->getPeriodByDate(date('Y-m-d', strtotime($date)));
    }
    
    $clients = Query::sqlQuery(
      'select client_id, name, surname,  mode, system_mode, super, club_state, unavailable_sports  
           from ' . Query::tableName('clients') . '  
           where (super = \'1\' or area_type in (\'0\', \'' . $type_id . '\')) and active = 2 and mode = 1 and (unavailable_sports is null OR unavailable_sports NOT LIKE \'%' . ($type_id . '_' . $sport_id . ($useSeason ? '_' . $season_id : '')) . '%\') 
           order by surname, name'
    );
    foreach ($clients as $client) {
      $client['unavailable_sports'] = empty($client['unavailable_sports']) ? [] : explode(';', $client['unavailable_sports']);
      $result[$client['client_id']] = (object)$client;
    }
    
    return $result;
  }
  
  public function getLoginByClientIds($client_id = [])
  {
    $where = '';
    if (!empty($client_id)) {
      $where .= ' where client_id in (' . implode(', ', $client_id) . ')';
    }
    $temp = Query::sqlQuery('select client_id, login, name, surname from ' . Query::tableName('clients') . ' ' . $where);
    
    $result = [];
    if (!empty($temp)) {
      foreach ($temp as $row) {
        $result[$row['client_id']] = (object)$row;
      }
    }
    
    return $result;
  }
  
  public function getQuantityReservationsClients($type_id, $sport_id, $date_start = null, $date_finish = null)
  {
    $result       = [];
    $reservations = Query::sqlQuery(
      'select r.client_id, r.street_friends from ' . Query::tableName('reservations') . ' r 
            left join ' . Query::tableName('areas') . ' a on r.area_id = a.area_id 
            where r.client_id is not null and r.main_reservation_id is null and a.type_id =' . $type_id . ' and a.sport_id=' . $sport_id
      . ($date_start !== null ? ' AND start>="' . $date_start . '"' : '')
      . ($date_finish !== null ? ' AND start<="' . $date_finish . '"' : '')
      . 'ORDER BY r.client_id'
    );
    foreach ($reservations as $reservation) {
      if (!isset($result[$reservation['client_id']])) {
        $result[$reservation['client_id']] = 0;
      }
      $result[$reservation['client_id']]++;
      if ($reservation['street_friends']) {
        foreach (unserialize($reservation['street_friends'], ['allowed_classes' => false]) as $friend) {
          if ($friend['id']) {
            $result[$friend['id']]++;
          }
        }
      }
    }
    
    return $result;
  }
  
  public function getRegistrationFields($key = 'id', $object = false)
  {
    $result = [];
    foreach (Query::sqlQuery('select * from ' . Query::tableName('registration_fields') . ' order by `group`, `sort`') as $field) {
      if ($this->checkShowRegistrationFields($field)) {
        if (defined('USE_FIRM_FIELD_REGISTRATION_AS_SELECT') && USE_FIRM_FIELD_REGISTRATION_AS_SELECT and $field['name'] == 'firm') {
          $field['type']   = 'select';
          $field['values'] = explode(';', USE_FIRM_FIELD_REGISTRATION_AS_SELECT);
        }
        $item = $field;
        if ($object) {
          $item = (object)$item;
        }
        if ($key == 'id' || $key == 'name') {
          $result[$field[$key]] = $item;
        } elseif ($key == 'group') {
          $result[$field[$key]][$field['name']] = $item;
        } else {
          $result[] = $item;
        }
      }
    }
    
    return $result;
  }
  
  protected function checkShowRegistrationFields($field)
  {
    if (($field['group'] == 'bank_data' || $field['name'] == 'encash_invoice') && !config('payment')->useInvoicePayment()) {
      return false;
    }
    
    if ($field['group'] == 'sepa_data' && !config('account')->useSEPA()) {
      return false;
    }
    
    return true;
  }
  
  public function updateRegistrationFields($field)
  {
    return Query::sqlQuery(
      'update ' . Query::tableName(
        'registration_fields'
      ) . ' set `show` ="' . $field->show . '", `required`="' . $field->required . '" where id=' . $field->id,
      [],
      false
    );
  }
  
  public function changeParameterForAll($field, $value)
  {
    Query::sqlQuery(
      'update ' . Query::tableName('clients') . ' set ' . $field . '="' . $value . '" where client_id is not null',
      [],
      false
    );
  }
  
  /** Доступные площадки
   * рассматриваются все варианты недоступных и формируется список активных.
   *
   * @param array $unavailable_sports
   * @param bool  $new
   *
   * @return array
   */
  
  public function availableSportByTypeBySeason(array $unavailable_sports = [], bool $new = true): array
  {
    static $sport_by_type_by_season = [];
    $out       = [];
    $useSeason = (bool)Service::configDB('registration', 'use_season_in_choosing_type_sport');
    if (empty($sport_by_type_by_season)) {
      $sport_by_type_by_season = module('areas')->useModel()->getSportByTypeBySeason($useSeason);
    }
    $defaultActiveSBT          = JsonHelper::decode(Service::configDB('registration', 'default_values_in_selecting_sport_type'));
    $define_unavailable_sports = defined('UNAVAILABLE_SPORTS') && UNAVAILABLE_SPORTS ? explode(';', UNAVAILABLE_SPORTS) : [];
    foreach ($sport_by_type_by_season as $key => $item) {
      $out[$key] = [
        'key'        => $item->key,
        'season_key' => $item->season_key,
        'title'      => $item->title_site_url,
        'active'     =>
          ($new && ((empty($defaultActiveSBT) && !in_array($item->season_key, $define_unavailable_sports) && !in_array($item->key,
                  $define_unavailable_sports))
              || (!empty($defaultActiveSBT) && (in_array($item->season_key, $defaultActiveSBT) || in_array($item->key, $defaultActiveSBT)))))
          || (!$new && (($useSeason && !in_array($item->season_key, $unavailable_sports) && !in_array($item->key,
                  $unavailable_sports)) || (!$useSeason && !in_array($item->key, $unavailable_sports))))
      ];
    }
    
    return $out;
  }
  
  public function checkClientAllowedPlayAreaForInaccessibility($client, $date, $type_id, $sport_id)
  {
    static $season_id, $_date;
    if (empty($client['unavailable_sports'])) {
      return true;
    }
    $useSeason = (bool)Service::configDB('registration', 'use_season_in_choosing_type_sport');
    if ($useSeason && (!isset($season_id) || $_date !== $date)) {
      $_date     = $date;
      $season_id = module('areas')->useModel()->getEngine()->getPeriodByDate(date('Y-m-d', strtotime($date)));
    }
    $unavailable_sports = !is_array($client['unavailable_sports'])
      ? explode(';', $client['unavailable_sports'] ?? '') : $client['unavailable_sports'];
    $checkKey           = $type_id . '_' . $sport_id . ($useSeason ? '_' . $season_id : '');
    if (!$client['super'] && in_array($checkKey, $unavailable_sports)) {
      return false;
    }
    
    return true;
  }
  
  public function isClient($client_id): bool
  {
    
    return isset($this->cache_client_data[$client_id]) || Query::sqlQuery(
        'select count(client_id) as count from ' . Query::tableName('clients') . ' where client_id =:client_id',
        [':client_id' => $client_id], true, ['onlyOne' => true])['count'] > 0;
  }
  
  public function getListNamesClients(?string $alfa = null, ?int $client_id = null, ?int $substring = null, $clients = []): array
  {
    $clients = !empty($clients) ? $clients : [];
    $param   = $where = [];
    if ($alfa) {
      $param   = [$alfa . '%', $alfa . '%'];
      $where[] = '(c.surname LIKE ? or c.name LIKE ?) ';
    }
    if ($client_id) {
      $param[] = $client_id;
      $where[] = 'c.client_id = ?';
    }
    $q = 'select client_id, CONCAT(surname, " ", name) as name
            from ' . Query::tableName('clients') . ' c
            where c.active = 2 ' . (!empty($where) ? ' and ' . implode(' and ', $where) : '')
      . ' order by c.surname, c.name';
    if ($clients_data = Query::sqlQuery($q, $param)) {
      foreach ($clients_data as $client) {
        $clients[$client['client_id']] = trim($substring ? StringHelper::cropStr($client['name'], $substring) : $client['name']);
      }
    }
    
    return $clients;
  }
}
