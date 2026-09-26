<?php

use AC\app\config\CountryConfig;
use AC\app\config\LangConfig;
use AC\core\engines\AccountsEngine;
use AC\core\engines\ExtraEngine;
use AC\core\modules\mailing\entities\enums\ModeTemplate;
use AC\core\system\db\Query;
use AC\core\system\helpers\EmailHelper;

/*
клиенты
mode
	1 - полноценный online
	2 - полноценный offline
*/

class reservation_clients
{
  
  public array $current_client_data = [];
  /**
   * @var array
   */
  protected array $cache_client_data = [];
  
  /* интерфейс */
  function initialize()
  {
    //кеш - данные клиента, строка в _assoc
    $this->cache_client_data = [];
    
    //старт сессии
    //session_start ();
    
    if (isset($_SESSION['malsch_session_client_id_' . SESSION_COURT]) && $_SESSION['malsch_session_client_id_' . SESSION_COURT] == 'bar') {
      //Авторизивался незарегистрированный тип клиента
    } elseif (isset($_SESSION['malsch_session_client_id_' . SESSION_COURT]) && (
        //если юзер залогинен пытаемся получить его данные
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
  
  function finalize()
  {
  }
  
  
  /* ПРАВКА */
  
  //добавить клиента
  function insertClient(
    $system_mode,
    $mode,
    $area_type,
    $active,
    $club_state,
    $encash,
    $login,
    $password,
    $password_confirmation,
    $number,
    $name,
    $surname,
    $birthday,
    $phone,
    $phone_mobile,
    $fax,
    $post_code,
    $city,
    $address,
    $email,
    $nds,
    $discount,
    $bank_data,
    $bank_data_n,
    $limit_day,
    $stock_id,
    $sprice_id,
    $unavailable_sports = [],
    $super = 0,
    &$data_check_results = null,
    &$new_client_id = null,
    $abo_delete = 0,
    $student = "0",
    $student_number = "",
    $firm = "",
    $refund_for_ticket = 0,
    $refund_for_paypal = 0,
    $lang = null,
    $country = null,
    $show_client_data = 1
  ) {
    //проверка данных
    if ($this->checkClientData(
      null,
      $system_mode,
      null,
      $mode,
      $active,
      $login,
      $password,
      $password_confirmation,
      $number,
      $name,
      $surname,
      $birthday,
      $phone,
      $email,
      $post_code,
      $city,
      $address,
      $bank_data,
      $bank_data_n,
      $data_check_results,
      $encash
    )) {
      $r = Service::engines();
      
      //с данными все в порядке
      $bank_sepa_mandat   = (REGISTRATION_PAYMENT_METHOD == 'GU')
        ? ' '
        : ('bank_sepa_mandat = "' . addslashes(
            $bank_data_n[2] ?? date('Y-m-d')
          ) . '",');
      $unavailable_sports = implode(';', $unavailable_sports);
      /** @var LangConfig $confLang */
      $confLang = config('lang');
      $lang     = $confLang->normalize($lang);
      /** @var CountryConfig $countryConfig */
      $countryConfig = config('country');
      $q             = 'insert into ' . Query::tableName('clients') . '
				set firm ="' . $firm . '",
				  student ="' . $student . '",
				  student_number ="' . $student_number . '",
					mode = ' . $mode . ', 
					system_mode = "' . $system_mode . '",
				  area_type = "' . $area_type . '", 
					active = "' . $active . '",
					club_state = "' . $club_state . '" , 
					encash = "' . $encash . '" , 
					nds = ' . ($nds == null ? $r->nds->getDefaultNdsId() : $nds) . ',
					discount = ' . ($discount === null ? 'NULL' : $discount) . ',
					login = ' . ($login === null ? 'NULL' : '"' . addslashes($login) . '"') . ',
					password_md5 = ' . ($password === null ? 'NULL' : '"' . md5($password) . '"') . ',
					number = "' . addslashes($number) . '",
					name = "' . addslashes($name) . '",
					surname = "' . addslashes($surname) . '",
					birthday = ' . ($birthday === null ? 'NULL' : '"' . addslashes($birthday) . '"') . ',
					phone = "' . addslashes($phone) . '",
					phone_mobile = "' . addslashes($phone_mobile) . '",
					fax = "' . addslashes($fax) . '",
					city = "' . addslashes($city) . '",
					post_code = "' . addslashes($post_code) . '",
					address = "' . addslashes($address) . '",
					email = "' . addslashes($email) . '",
					limit_day = "' . $limit_day . '",
					stock_id = "' . ($stock_id ? addslashes(serialize($stock_id)) : '') . '",
					sprice_id = "' . ($sprice_id ? addslashes(serialize($sprice_id)) : '') . '",
					registered = now(),
					account_owner = "' . addslashes($bank_data[0]) . '",
					account_number = "' . addslashes($bank_data[1]) . '",
					bank_index = "' . addslashes($bank_data[2]) . '",
					bank_name = "' . addslashes($bank_data[3]) . '", 
					' . (isset($bank_data_n[4]) ? ' sepa_type = "' . addslashes($bank_data_n[4]) . '", ' : '') . '
					' . (isset($bank_data_n[5]) ? ' sepa_standart = "' . trim(addslashes($bank_data_n[5])) . '", ' : '') . '
          bank_iban = "' . addslashes(str_replace(' ', '', $bank_data_n[0])) . '",
					bank_bic = "' . addslashes($bank_data_n[1]) . '",
					' . $bank_sepa_mandat . '
					bank_sepa_referenz = "' . addslashes(str_replace(' ', '', $bank_data_n[3])) . '",
					`super` = "' . (int)$super . '",
					unavailable_sports="' . $unavailable_sports . '",
					abo_delete="' . (int)$abo_delete . '",
					refund_for_ticket="' . (int)$refund_for_ticket . '",
					refund_for_paypal="' . (int)$refund_for_paypal . '"'
        . ($confLang->detectLanguageColumn('clients', 'lang') ? ', lang = "' . $lang . '"' : '')
        . (Service::query()::getDB()->checkField('country', 'clients') ? ', country = "' . $countryConfig->normalize($country) . '"' : '')
        . (Service::query()::getDB()->checkField('show_client_data', 'clients') ? ', show_client_data = ' . (int)$show_client_data : '');
      Query::sqlQuery($q, [], false);
      
      $new_client_id = Query::getLastId();
      
      if ($r->config['order_notify']) {
        //формирование информационного письма о новом клиенте
        $tpl_data['LOGIN']                = $login;
        $tpl_data['NAME']                 = $name;
        $tpl_data['SURNAME']              = $surname;
        $tpl_data['BIRTHDAY']             = $birthday;
        $tpl_data['PHONE']                = $phone;
        $tpl_data['PHONE_MOBILE']         = $phone_mobile;
        $tpl_data['FAX']                  = $fax;
        $tpl_data['POST_CODE']            = $post_code;
        $tpl_data['CITY']                 = $city;
        $tpl_data['ADDRESS']              = $address;
        $tpl_data['EMAIL']                = $email;
        $tpl_data['BANK_ACCOUNT_HOLDER']  = $bank_data[0];
        $tpl_data['BANK_ACCOUNT_NUMBER']  = $bank_data[1];
        $tpl_data['BANK_IDENTIFIER_CODE'] = $bank_data[2];
        $tpl_data['BANK_NAME']            = $bank_data[3];
        $tpl_data['FIRMA']                = $firm;
        $tpl_data['STATE_STUD']           = match ((int)$student) {
          1       => lang('Yes'),
          default => lang('Not'),
        };
        $tpl_data['CLUB_STATE']           = match ((int)$club_state) {
          2       => lang('Yes'),
          1       => lang('Not'),
          default => '',
        };
        //отправка письма
        Service::mailer()->dispatch(explode(',', Service::configDB('email', 'notify_email')), ModeTemplate::ADMIN->value, 'registration', $tpl_data);
        
        if ($active == 2) {
          //отправка письма
          Service::mailer()->dispatch($email, ModeTemplate::USER->value, 'activate', $tpl_data, $lang);
        }
      }
      /** todo создать функционал который автоматически проходиться по заданным областям(путям) и ищет классы событий для определенного события - например сейчас после обновления данных клиента нужно сделать еще обязательные функции которые используют другие модули */
      /** Новый функционал обновление данных клиента используемых в модуле членских взносов
       */
      if (config('membershipFees')->useMembershipFees()) {
        module('membershipFees', ['mode' => 'client_data', 'useRouting' => false, 'params' => ['client_id' => $new_client_id]])->exec('update');
      }
      
      return true;
    } else {
      //ошибка входных данных
      return false;
    }
  }
  
  //изменить клиента
  //$system_mode = 0 - клиент, 1- admin
  function changeClientData(
    $client_id,
    $system_mode,
    $mode,
    $area_type,
    $active,
    $club_state,
    $encash,
    $login,
    $password,
    $password_confirmation,
    $number,
    $name,
    $surname,
    $birthday,
    $phone,
    $phone_mobile,
    $fax,
    $post_code,
    $city,
    $address,
    $email,
    $bank_data,
    $bank_data_n,
    $nds,
    $discount,
    $limit_day,
    $stock_id,
    $sprice_id,
    &$error_code,
    &$data_check_results,
    $unavailable_sports = [],
    $super = 0,
    $abo_delete = 0,
    $student = "0",
    $student_number = "",
    $firm = "",
    $refund_for_ticket = 0,
    $refund_for_paypal = 0,
    $lang = null,
    $country = null,
    $show_client_data = 1
  
  ) {
    $unavailable_sports_data = implode(';', $unavailable_sports);
    $temp                    = Query::sqlQuery('select mode from ' . Query::tableName('clients') . ' where client_id = ' . $client_id);
    if (!empty($temp)) {
      //клиент найден
      $mode_prev = $temp[0]['mode'];
      
      $this->getClientData($client_id, $client_data);
      //проверка данных
      if ($this->checkClientData(
        $client_id,
        $system_mode,
        $mode_prev,
        $mode,
        $active,
        $login,
        $password,
        $password_confirmation,
        $number,
        $name,
        $surname,
        $birthday,
        $phone,
        $email,
        $post_code,
        $city,
        $address,
        $bank_data,
        $bank_data_n,
        $data_check_results,
        $encash,
        true
      )) {
        /** @var LangConfig $confLang */
        $confLang = config('lang');
        $lang     = $confLang->normalize($lang);
        /** @var CountryConfig $countryConfig */
        $countryConfig = config('country');
        
        //с данными все в порядке
        $q = 'update ' . Query::tableName('clients') . '
					set firm ="' . $firm . '",
					    student ="' . $student . '",
 					    student_number ="' . $student_number . '",
						mode = ' . $mode . ',
						active = ' . ($active === null ? 'NULL' : $active) . ',
						club_state = "' . $club_state . '" ,
						area_type = "' . $area_type . '" ,
						encash = "' . $encash . '" ,
						nds = ' . $nds . ',
						discount = ' . ($discount === null ? 'NULL' : $discount) . ',
						login = ' . ($login === null ? 'NULL' : '"' . addslashes($login) . '"') . ',
						password_md5 = ' . ($password === null
            ? 'NULL'
            : ($password == ''
              ? 'password_md5'
              : '"' . md5(
                $password
              ) . '"')) . ',
						number = "' . addslashes($number) . '",
						name = "' . addslashes($name) . '",
						surname = "' . addslashes($surname) . '",
						birthday = ' . ($birthday === null ? 'NULL' : '"' . addslashes($birthday) . '"') . ',
						phone = "' . addslashes($phone) . '",
						phone_mobile = "' . addslashes($phone_mobile) . '",
						fax = "' . addslashes($fax) . '",
						city = "' . addslashes($city) . '",
						post_code = "' . addslashes($post_code) . '",
						address = "' . addslashes($address) . '",
						email = "' . addslashes($email) . '",
						account_owner = "' . addslashes($bank_data[0]) . '",
						account_number = "' . addslashes($bank_data[1] ?? '') . '",
						bank_index = "' . addslashes($bank_data[2] ?? '') . '",
						bank_name = "' . addslashes($bank_data[3]) . '",
						super = "' . $super . '",
						' . (isset($bank_data_n[4]) ? ' sepa_type = "' . addslashes($bank_data_n[4]) . '", ' : '') . '
            ' . (isset($bank_data_n[5]) ? ' sepa_standart = "' . addslashes($bank_data_n[5]) . '", ' : '') . '
            bank_iban = "' . addslashes(str_replace(' ', '', $bank_data_n[0])) . '",
            bank_bic = "' . addslashes($bank_data_n[1]) . '",
            bank_sepa_mandat = ' . (!empty($bank_data_n[2]) ? '"' . addslashes($bank_data_n[2]) . '"' : 'NULL') . ',
            bank_sepa_referenz = "' . addslashes(str_replace(' ', '', $bank_data_n[3])) . '",
            unavailable_sports = "' . $unavailable_sports_data . '",
						limit_day = "' . $limit_day . '" '
          . ($stock_id === null ? '' : ', stock_id = "' . ($stock_id ? addslashes(serialize($stock_id)) : '') . '" ') .
          ($sprice_id === null
            ? ''
            : ', sprice_id = "' . ($sprice_id ? addslashes(
              serialize($sprice_id)
            ) : '') . '" ') .
          (($abo_delete !== null) ? (', abo_delete = "' . (($abo_delete) ? 1 : 0) . '"') : '') .
          (($refund_for_ticket !== null) ? (', refund_for_ticket = "' . (($refund_for_ticket) ? 1 : 0) . '"') : '') .
          (($refund_for_paypal !== null) ? (', refund_for_paypal = "' . (($refund_for_paypal) ? 1 : 0) . '"') : '') .
          ($confLang->detectLanguageColumn('clients', 'lang') ? ', lang = "' . $lang . '"' : '') .
          (Service::query()::getDB()->checkField('country', 'clients') ? ', country = "' . $countryConfig->normalize($country) . '"' : '') .
          (Service::query()::getDB()->checkField('show_client_data', 'clients') ? ', show_client_data = ' . (int)$show_client_data : '')
          . ' where client_id = ' . $client_id;
        
        Query::sqlQuery($q, [], false);
        //все OK
        $error_code = 0;
        
        if (isset($client_data)) {
          if ($active == 2 && (($client_data['active'] + 1) != $active)) {
            //отправка письма
            $tpl_data['NAME']    = $name;
            $tpl_data['SURNAME'] = $surname;
            Service::mailer()->dispatch($email, ModeTemplate::USER->value, 'activate', $tpl_data, $lang);
          }
        }
        
        /** todo создать функционал который автоматически проходиться по заданным областям(путям) и ищет классы событий для определенного события - например сейчас после обновления данных клиента нужно сделать еще обязательные функции которые используют другие модули */
        /** Новый функционал обновление данных клиента используемых в модуле членских взносов
         */
        if (config('membershipFees')->useMembershipFees()) {
          module('membershipFees', ['mode' => 'client_data', 'useRouting' => false])->exec('update');
        }
        /** Новая фича если данные клиента изменились то изменяем все счета и клиента счета для этого клиента
         * @var $a AccountsEngine
         */
        $a = getEngine('accounts');
        $a->changeClientAccountsOnCurrentMonth($client_id);
        
        
        return true;
      } else {
        //ошибка входных данных
        $error_code = 2;
        
        return false;
      }
      
      //сбрасываем кеш
      unset ($this->cache_client_data[$client_id]);
      
      return true;
    } else {
      //клиент НЕ найден
      $error_code = 1;
      
      return false;
    }
  }
  
  //изменить клиента
  function changeClientCardData($client_id, $codecard, &$error_code)
  {
    $q    = 'select mode from ' . Query::tableName('clients') . ' where client_id = ' . $client_id;
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      //клиент найден
      
      $q = 'update ' . Query::tableName('clients') . '
				    set
					    codecard = ' . $codecard . '
				    where client_id = ' . $client_id;
      Query::sqlQuery($q, [], false);
      //все OK
      $error_code = 0;
      
      return true;
    } else {
      //ошибка входных данных
      $error_code = 2;
    }
    
    return false;
  }
  
  //удалить клиента
  function removeClient($client_id)
  {
    // todo перенести по модулям и добавить запись в лог архива
    //заказы
    $q = 'delete from ' . Query::tableName('reservations') . ' where client_id = ' . $client_id;
    Query::sqlQuery($q, [], false);
    //билеты
    //получаем ID билетов клиента
    $q    = 'select ticket_id from ' . Query::tableName('tickets') . ' where client_id = ' . $client_id;
    $temp = Query::sqlQuery($q);
    //удаляем промежутки билетов
    foreach ($temp as $row) {
      $q = 'delete from ' . Query::tableName('tickets_periods') . ' where ticket_id = ' . $row['ticket_id'];
      Query::sqlQuery($q, [], false);
    }
    //удаляем сами билеты
    $q = 'delete from ' . Query::tableName('tickets') . ' where client_id = ' . $client_id;
    Query::sqlQuery($q, [], false);
    //строка самого клиента
    $q = 'delete from ' . Query::tableName('clients') . ' where client_id = ' . $client_id;
    Query::sqlQuery($q, [], false);
    
    /** todo создать функционал который автоматически проходиться по заданным областям(путям) и ищет классы событий для определенного события - например сейчас после обновления данных клиента нужно сделать еще обязательные функции которые используют другие модули */
    /** Новый функционал обновление данных клиента используемых в модуле членских взносов
     */
    if (config('membershipFees')->useMembershipFees()) {
      module('membershipFees', ['mode' => 'client_data', 'useRouting' => false])->exec('remove');
    }
    
    //сбрасываем кеш
    unset ($this->cache_client_data[$client_id]);
    
    return true;
  }
  
  //удалить клиента
  function removeBarClient($name, $surname, $in_md5 = false)
  {
    // todo перенести в Reservations и добавить запись в лог архива
    $q = 'delete from ' . Query::tableName('reservations') . ' where client_id is null and ' .
      ($in_md5
        ?
        'md5(client_name) = "' . $name . '" and md5(client_surname) = "' . $surname . '"'
        :
        'client_name = "' . addslashes($name) . '" and client_surname = "' . addslashes($surname) . '"'
      );
    Query::sqlQuery($q, [], false);
  }
  
  /**
   * Пополнить лицевой счет клиента
   *
   * Проверяет существование клиента, пополняет его лицевой счет на указанную сумму.
   * Возвращает статус операции и код ошибки.
   *
   * @param int       $client_id      ID клиента
   * @param float|int $prepayment_sum Сумма для пополнения
   * @param ?int      $error_code     Код ошибки:
   *                                  - 0: Успех
   *                                  - 1: Клиент не найден
   *                                  - 2: Проблема с суммой пополнения
   *                                  - 3: Ошибка базы данных
   *
   * @return bool true при успешном списании, false в случае ошибки
   */
  public function setClientPrepaymentSum(int $client_id, float|int $prepayment_sum, ?int &$error_code = 0): bool
  {
    $prepayment_sum = round(abs($prepayment_sum), 2);
    // Получаем текущие данные клиента
    $client = Query::sqlQuery('SELECT client_id, prepayment_sum FROM ' . Query::tableName('clients') . ' WHERE client_id = :client_id',
      [':client_id' => $client_id], true, ['onlyOne' => true]);
    
    if (!empty($client)) {
      // Выполняем пополнение счета
      $updateStmt = 'UPDATE ' . Query::tableName('clients') . ' SET prepayment_sum = prepayment_sum + :amount WHERE client_id = :client_id';
      
      $updateParams = [
        ':amount'    => $prepayment_sum,
        ':client_id' => $client_id
      ];
      
      if (Query::sqlQuery($updateStmt, $updateParams, false)) {
        $error_code = 0;
        return true;
      }
      $error_code = 3;
      
    } else {
      //клиент НЕ найден
      $error_code = 1;
    }
    
    return false;
  }
  
  /**
   * Снять средства с лицевого счета клиента
   *
   * Проверяет существование клиента, уменьшает его лицевой счет на указанную сумму.
   * Возвращает статус операции и код ошибки.
   *
   * @param int       $client_id       ID клиента
   * @param float|int $prepayment_sum  Сумма для списания
   * @param ?int      $error_code      Код ошибки:
   *                                   - 0: Успех
   *                                   - 1: Клиент не найден
   *                                   - 2: Недостаточно средств
   *                                   - 3: Ошибка базы данных
   *
   * @return bool true при успешном списании, false в случае ошибки
   */
  public function getClientPrepaymentSum(int $client_id, float|int $prepayment_sum, ?int &$error_code = 0): bool
  {
    $prepayment_sum = round(abs($prepayment_sum), 2);
    // Получаем текущие данные клиента
    $client = Query::sqlQuery('SELECT client_id, prepayment_sum FROM ' . Query::tableName('clients') . ' WHERE client_id = :client_id',
      [':client_id' => $client_id], true, ['onlyOne' => true]);
    
    if (!empty($client)) {
      // Проверка корректности суммы
      if ($prepayment_sum > $client['prepayment_sum']) {
        $error_code = 2;
        return false;
      }
      // Выполняем списание средств
      $updateStmt = 'UPDATE ' . Query::tableName('clients') . ' SET prepayment_sum = prepayment_sum - :amount WHERE client_id = :client_id';
      
      $updateParams = [
        ':amount'    => $prepayment_sum,
        ':client_id' => $client_id
      ];
      
      if (Query::sqlQuery($updateStmt, $updateParams, false)) {
        $error_code = 0;
        return true;
      }
      // Ошибка базы данных
      $error_code = 3;
    } else {
      // Клиент не найден
      $error_code = 1;
    }
    
    return false;
  }
  
  /* ПРОВЕРКА ДАННЫХ КЛИЕНТА */
  function checkClientData(
    $client_id,
    $system_mode,
    $mode_prev,
    $mode, // - 1 онлайн клиент , 2 - оффлайн клиент
    &$active,
    &$login,
    &$password,
    $password_confirmation,
    $number,
    $name,
    $surname,
    &$birthday,
    $phone,
    $email,
    $post_code,
    $city,
    $address,
    $bank_data,
    $bank_data_n,
    &$result,
    $encash = false,
    $is_change = false
  ) {
    //0[логин: 1 - короткий, 2 - уже есть]
    //1[пароль: 1 - короткий, 2 - не совпадают]
    //2[нет имени]
    //3[нет фимилии]
    //4[некорректная дата рождения]
    //5[нет телефона]
    //6[e-mail: 2 - некорректный, 3 - уже есть]
    //7[нет почтового кода]
    //8[нет города]
    //9[нет адреса]
    //10[банковские данные]
    //11[банковские данные]
    //12[банковские данные]
    //13[банковские данные]
    //14[BIC]
    //15[IBAN]
    $result = '0000000000000000';
    if ($mode == 1 && $system_mode != 1) {
      //проверки -
      //логина
      if (($login = trim($login)) && strlen($login) > 5 && strlen($login) < 21) {
        if (preg_match('/^[a-zA-Z0-9-_\p{L}]+$/u', $login)) { // буквы цифры умлауты дефис и подчеркивания
          $q    = 'select count(*) as cnt from ' . Query::tableName('clients') . ' where BINARY login = "' . addslashes($login) . '"' .
            ($client_id !== null ? ' and client_id != ' . $client_id : '');
          $temp = Query::sqlQuery($q);
          $cnt  = $temp[0]['cnt'];
          if ($cnt > 0) {
            $result[0] = 2;
          }
        } else {
          $result[0] = 3;
        }
      } else {
        if (strlen($login) < 6) {
          $result[0] = 1;
        }
        if (strlen($login) > 20) {
          $result[0] = 4;
        }
      }
      
      //пароля
      if ($client_id === null ||//новый клиент
        (//старый клиент
          ($mode_prev != 1 && $mode == 1) ||//меняем тип клиента на online ИЛИ
          ($mode == 1 && ($password != '' || $password_confirmation != ''))//online-клиент, меняем пароль
        )
      ) {
        if (strlen($password) > 5) {
          if ($password != $password_confirmation) {
            $result[1] = 2;
          }
        } else {
          $result[1] = 1;
        }
      }
      
      $check_address = true;
    } elseif ($mode == 2) {
      //для offline клиентов этих полей нет
      $login    = null;
      $password = null;
      $active   = null;
    }
    
    if ($system_mode != 1) { // если не админ то проверяем
      //провенка имени и фамилии begin - проверяем всегда
      //на длину
      if (strlen(trim($name)) < 3) {
        $result[2] = 1;
      }
      if (strlen(trim($surname)) < 3) {
        $result[3] = 1;
      }
      //провенка имени и фалилии end
      //проверка даты рождения begin
      if ($birthday != '') {
        if (preg_match('|^\d{4}-\d{1,2}-\d{1,2}$|', $birthday)) {
          $tmp = explode('-', $birthday);
          if (!checkdate($tmp[1], $tmp[2], $tmp[0])) {
            $result[4] = 1;
          }
        } else {
          $result[4] = 1;
        }
      } else {
        $birthday = null;
      }
      //проверка даты рождения end
      //проверка email. email необязателен, но если уж указан - проверяем на корректность
      if ($this->checkEmail($email)) {
        $result[6] = 1;
      }
      if ($mode != 2) {
        //проверка наличия хотя бы одного телефона
        if (trim($phone) == '') {
          $result[5] = 1;
        }
        //проверка адреса
        $result[7] = (int)(trim($post_code) == '');
        $result[8] = (int)(trim($city) == '');
        $result[9] = (int)(trim($address) == '');
        //$result[9] = 0;
        //банковские данные адреса
        if (!$is_change && REGISTRATION_PAYMENT_METHOD != 'GU' && $encash !== false && (int)$encash == 1) {
          $result[10] = (int)(trim($bank_data[0]) == '');
          $result[11] = 0;
          $result[12] = 0;
          $result[13] = (int)(trim($bank_data[3]) == '');
        } else {
          $result[10] = 0;
          $result[11] = 0;
          $result[12] = 0;
          $result[13] = 0;
        }
      }
      if ($system_mode == 1) {
        $result[14] = 0;
        $result[15] = 0;
      } else {
        if (REGISTRATION_PAYMENT_METHOD != 'GU' && $encash !== false && (int)$encash == 1) {
          $result[14] = (int)(trim($bank_data_n[0]) == '');
          $result[15] = (int)(trim($bank_data_n[1]) == '');
        } else {
          $result[14] = 0;
          $result[15] = 0;
        }
      }
    }
    
    if (REGISTRATION_PAYMENT_METHOD == 'RE_NBD') {
      $result[10] = 0;
      $result[11] = 0;
      $result[12] = 0;
      $result[13] = 0;
      $result[14] = 0;
      $result[15] = 0;
    }
    
    return $result == '00000000000000';
  }
  
  //проверить имя и фамилию bar клеиента
  function checkClientNameSurname($name, $surname, $email)
  {
    return strlen(trim($name)) >= 3 && strlen(trim($surname)) >= 3 && (strlen(trim($email)) >= 3 && $this->checkEmail($email));
  }
  
  function checkEmail($email)
  {
    return EmailHelper::checkEmail($email);
  }
  
  
  /* ВЫДАЧА */
  
  //данные клиентов заданного типа
  //mode - null без разницы
  function getClientsData(
    $mode,
    $active,
    &$clients_data,
    $order_by = 'c.surname, c.name',
    $alpha = null,
    $select = 'cnds.rate as nds_rate,c.*, ccs.title as club_state_title',
    $limit = []
  ) {
    //условия выборки
    $where = [];
    if ($mode !== null) {
      $where[] = 'c.mode in(' . $mode . ')';
    }
    if ($active !== null) {
      $where[] = 'c.active = ' . ($active + 1);
    }
    if ($alpha !== null) {
      if (is_array($alpha)) {
        $like = [];
        foreach ($alpha as $name => $value) {
          $like[] = $name . ' LIKE \'' . $value . '%\'';
        }
        $where[] = '(' . implode(' or ', $like) . ')';
      } else {
        $where[] = 'c.surname LIKE \'' . $alpha . '%\'';
      }
    }
    $where = implode(' and ', $where);
    $limit = array_pad(is_array($limit) ? $limit : [$limit], 2, null);
    $limit = !empty($limit[0]) ? ' limit ' . $limit[0] . ( !empty($limit[1]) ? ' offset ' . $limit[1] : '') : '';
    
    $q    = 'select ' . $select . ' from ' . Query::tableName('clients') . ' c 
      left join ' . Query::tableName('config_club_state') . ' ccs on c.club_state = ccs.id 
      left join ' . Query::tableName('config_nds') . ' cnds on cnds.nds_id = c.nds' .
      ($where != '' ? ' where ' . $where : '') . ' order by ' . $order_by . $limit;
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      $clients_data = [];
      //получаем строки таблицы
      foreach ($temp as $row) {
        $clients_data[] = $row;
        //в кеш инфрмации о клиентах
        $this->cache_client_data[$row['client_id']] = $row;
      }
      
      return true;
    } else {
      $clients_data = null;
      
      return false;
    }
  }
  
  //данные клиентов заданного типа
  //mode - null без разницы
  //пробничек выбираем всех оффлайновых клиентов независимо от active
  function getClientsDataWithIf($mode, $active, &$clients_data, $order_by = 'surname, name')
  {
    //условия выборки
    $where = '';
    $act   = '';
    if ($active !== null) {
      $act = 'and active = ' . ($active + 1);
    }
    
    if ($mode !== null) {
      if ($mode == 1) {
        $where .= 'mode=1 ' . $act . ' ';
      } else {
        $where .= 'mode=2 ';
      }
    } else {
      $where .= '(mode=1 ' . $act . ') or mode=2 ';
    }
    
    $q    = 'select * from ' . Query::tableName('clients') . ' ' . ($where != '' ? 'where ' . $where : '') . 'order by ' . $order_by;
    $temp = Query::sqlQuery($q);
    
    if (!empty($temp)) {
      $clients_data = [];
      //получаем строки таблицы
      foreach ($temp as $row) {
        $clients_data[] = $row;
        //в кеш инфрмации о клиентах
        $this->cache_client_data[$row['client_id']] = $row;
      }
      
      return true;
    } else {
      $clients_data = null;
      
      return false;
    }
  }
  
  /**
   * Получить данные клиентов для формирования индивидуальных счетов
   * использовать эту функцию только для списка клиентов, по которым формируются индивидуальные счета
   * @param        $c_date
   * @param        $clients_data
   * @param string $order_by
   * @param        $type
   * @return bool
   */
  public function getClientsDataByOrder($c_date, $type, &$clients_data, string $order_by = 'c.surname, c.name'): bool
  {
    $start_date = date('Y-m-01', strtotime($c_date));
    $end_date   = date('Y-m-01', strtotime($c_date . ' +1 month'));
    
    $q = "SELECT a.account_id, c.client_id, c.name, c.surname
            FROM " . Query::tableName('clients') . " c
		        INNER JOIN " . Query::tableName('reservations') . " r ON c.client_id = r.client_id "
      // @todo: Если будет доработка на возможность нескольких спец цен (что нонсенс) то нужно доработать проверку и из таблицы reservation_data
      . getEngine('specPrices')->sqlCheckDoNotShowOnAccount('r')
      . (Service::engines()->getTypeAliasByTypeId($type) == 'open'
        ? "\n AND (r.price > 0 OR r.light_price > 0 OR r.heating_price > 0 OR r.net_price > 0)"
        : '')
      . "\n INNER JOIN " . Query::tableName('areas') . " ar ON ar.area_id = r.area_id
           INNER JOIN " . Query::tableName('areas_types') . " at ON ar.type_id = at.type_id
           LEFT JOIN " . Query::tableName('accounts_clients') . " ac ON ac.sys_client_id = r.client_id
           LEFT JOIN " . Query::tableName('accounts')
      . " a ON a.date_start >= ?
                 AND a.date_start < ?
                 AND a.account_type = '0'
                 AND a.deleted = '0'
                 AND a.client_id = ac.client_id
                 AND '" . $type . "' = (SELECT type_id
                                        FROM " . Query::tableName('areas') . "
                                        WHERE area_id = a.area_id)
        WHERE
				  r.encash ='1'
				  AND  r.main_client_id is null
				  AND r.start >= ?
          AND r.start < ?
          AND ar.type_id='" . $type . "'"
      . Service::engines()->areas->sqlCheckGroupType('at')
      . " GROUP BY a.account_id DESC, c.`client_id` " . ($order_by ? ", $order_by  ORDER BY $order_by" : '');
    if ($temp = Query::sqlQuery($q, [$start_date, $end_date, $start_date, $end_date])) {
      $tmp = [];
      
      $clients_data = array_values(array_reduce(
        $temp,
        static function ($carry, $row) use (&$tmp) {
          $cid             = $row['client_id'];
          $aid             = $row['account_id'];
          $tmp[$cid][$aid] = $row;
          if (isset($tmp[$cid][null]) && count($tmp[$cid]) === 1) {
            $carry[$cid] = $tmp[$cid][null];
          } else {
            unset($carry[$cid]);
          }
          
          return $carry;
        },
        []
      ));
      
      return true;
    }
    $clients_data = null;
    
    return false;
  }
  
  /**
   * Получить данные клиентов для формирования счетов по абонементам
   * использовать эту функцию только для списка клиентов, по которым формируются счета по абонементам
   * @param $clients_data
   * @param $type
   * @return bool
   */
  function getClientsDataByAbo(&$clients_data, $type = false)
  {
    $strType = '';
    if (!empty($type)) {
      $strType = ' AND at.type_id="' . $type . '" ';
    }
    $q    = 'SELECT ac.ticket_id acid,c.client_id, c.name, c.surname, t.ticket_id, t.time_start, t.time_finish, t.weekdays, a.area_id, a.title as area, at.title as type, MAX(tp.finish) as ticket_finish_date
					FROM ' . Query::tableName('clients') . ' c
					LEFT JOIN ' . Query::tableName('tickets') . ' t ON c.client_id = t.client_id
					LEFT JOIN ' . Query::tableName('tickets_periods') . ' tp ON t.ticket_id = tp.ticket_id
					LEFT JOIN ' . Query::tableName('areas') . ' a ON t.area_id = a.area_id
					LEFT JOIN ' . Query::tableName('areas_types') . ' at ON a.type_id = at.type_id
					LEFT JOIN ' . Query::tableName('accounts') . ' ac ON ac.ticket_id = t.ticket_id AND ac.account_type="1"
					WHERE t.ticket_id IS NOT NULL ' . $strType . ' AND (ac.ticket_id IS NULL OR (SELECT COUNT(*) FROM ' . Query::tableName(
        'accounts'
      ) . ' ar WHERE ar.ticket_id=t.ticket_id AND ar.deleted="0")<1) AND tp.ticket_id IS NOT NULL'
      . Service::engines()->areas->sqlCheckGroupType('at')
      . ' GROUP BY t.ticket_id  ORDER BY c.surname';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      $clients_data = [];
      //получаем строки таблицы
      foreach ($temp as $row) {
        $clients_data[$row['client_id']]['name']                                            = $row['name'];
        $clients_data[$row['client_id']]['surname']                                         = $row['surname'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['type']               = $row['type'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['area']               = $row['area'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['area_id']            = $row['area_id'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['ticket_finish_date'] = $row['ticket_finish_date'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['time_start']         = $row['time_start'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['time_finish']        = $row['time_finish'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['weekdays']           = $row['weekdays'];
      }
      
      return true;
    } else {
      $clients_data = null;
      
      return false;
    }
  }
  
  
  //данные клиентов заданного типа
  //mode - null без разницы
  function getBarClients(&$clients_data)
  {
    $q    = 'select client_name, client_surname, count(*) as cnt, min(ordered) as min_ordered from ' . Query::tableName(
        'reservations'
      ) . ' where client_id is null group by concat(client_surname,client_name) order by client_surname, client_name';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      $clients_data = [];
      //получаем строки таблицы
      foreach ($temp as $row) {
        $clients_data[] = [$row['client_name'], $row['client_surname'], $row['cnt'], $row['min_ordered']];
      }
      
      return true;
    } else {
      $clients_data = null;
      
      return false;
    }
  }
  
  /**
   *  Получить данные клиента по его id
   *
   * @param      $client_id
   * @param      $client_data
   * @param bool $change
   *
   * @return bool
   */
  public function getClientData($client_id, &$client_data, $change = false)
  {
    $client_data = [];
    
    if (isset ($this->cache_client_data[$client_id]) && !$change) {
      //данные есть в кеше
      $client_data = $this->cache_client_data[$client_id];
      
      return true;
    } else {
      //в кеше нет
      $q    = 'select c.*, n.rate as nds_rate, d.retail as discount_retail, d.ticket as discount_ticket
        from ' . Query::tableName('clients') . ' c
        JOIN ' . Query::tableName('config_nds') . ' n
        left join ' . Query::tableName('config_discount') . ' d ON d.discount_id = c.discount
        where c.client_id = "' . $client_id . '" AND IF((c.nds IS NULL OR  c.nds=0), n.set_default="1", n.nds_id = c.nds)';
      $temp = Query::sqlQuery($q);
      if (!empty($temp)) {
        //получаем строку таблицы
        $client_data = $temp[0];
        //вытаскиваем леттеркоды
        $client_data['stock_id']  = $client_data['stock_id'] ? Service::cast('array')->get($client_data['stock_id']) : [];
        $client_data['sprice_id'] = $client_data['sprice_id'] ? Service::cast('array')->get($client_data['sprice_id']) : [];
        $extraEngine              = new ExtraEngine();
        $client_data['extra']     = $extraEngine->getExtraByClubState($client_data['club_state']);
        //прочие данные
        $this->cache_client_data[$client_data['client_id']] = $client_data;
        
        return true;
      }
      
      return false;
    }
  }
  
  //данные нескольких клиентов по ID
  function getClientsDataById($clients_id = [], &$client_data = [])
  {
    $client_data = [];
    if (!empty($clients_id)) {
      $q = 'select c.*, n.rate as nds_rate, d.retail as discount_retail, d.ticket as discount_ticket, d.dimension as discount_dimension
								from ' . Query::tableName('clients') . ' c join ' . Query::tableName('config_nds') . ' n
								left join ' . Query::tableName('config_discount') . ' d ON d.discount_id = c.discount 
								where c.client_id IN (' . join(
          ',',
          $clients_id
        ) . ') AND IF(c.nds IS NULL, n.set_default="1", n.nds_id = c.nds)';
      if ($client_data = Query::sqlQuery($q)) {
        if (Query::getDB()->checkTable('client_data') && $clientDataOptionally = getEngine('clientData', false)->getClientsData($clients_id)) {
          foreach ($client_data as $key => $client) {
            if (isset($clientDataOptionally[$client['client_id']])) {
              foreach ($clientDataOptionally[$client['client_id']] as $data) {
                $client_data[$key][$data['name']] = $data['value'];
              }
            }
          }
        }
        //прочие данные
        return true;
      }
    }
    return false;
  }
  
  //Взять первые буквы фамилий людей
  function getFirstAlphaSurname($mode, $active)
  {
    //условия выборки
    $where = '';
    if ($mode !== null) {
      $where .= 'mode in(' . $mode . ') and ';
    }
    if ($active !== null) {
      $where .= 'active = ' . ($active + 1) . ' and ';
    }
    
    $q    = 'SELECT DISTINCT UPPER(LEFT(surname, 1)) AS alpha FROM ' . Query::tableName('clients') . ' ' . ($where != '' ? 'where ' . substr(
          $where,
          0,
          -4
        ) : '') . ' ORDER BY alpha';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      //получаем строки таблицы
      foreach ($temp as $row) {
        $clients_data[] = $row['alpha'];
      }
      
      return $clients_data;
    }
    
    return false;
  }
  
  /* АВТОРИЗАЦИЯ */
  
  //проверка авторизации
  function checkAuthorization()
  {
    if (isset ($_SESSION['malsch_session_client_id_' . SESSION_COURT])) {
      return true;
    } else {
      return false;
    }
  }
  
  //проверить имя, фамилию и email bar клеиента
  function checkBarClientData($name, $surname, $email)
  {
    return strlen(trim($name)) >= 3 && strlen(trim($surname)) >= 3 && strlen(trim($email)) >= 3 && (preg_match(
        '/^[a-zA-Z0-9\-\_\.]+\@[a-zA-Z0-9\-\_\.]+\.[a-zA-Z]{2,4}$/',
        $email
      ));
  }
  
  //Проверяем является ли авторизованный пользователь - чужим
  function checkAlienClient($type_return = false)
  {
    if ($_SESSION['malsch_session_client_id'] == $_SESSION['reservation_comment']) {
      if ($type_return) {
        return $id_alien = $_SESSION['reservation_comment'];
      } else {
        return true;
      }
    } else {
      return false;
    }
  }
  
  
  //вход
  public function login($login, $password, &$error_code = 0): bool
  {
    $q = 'select * from ' . Query::tableName('clients') . '
			where login = ? and mode = 1 and active = 2';
//    $q = 'select * from ' . Query::tableName('clients') . '
//			where BINARY login = :login and mode = 1 and active = 2';
    if (($client = Query::sqlQuery($q, [addslashes($login)], true, ['onlyOne' => true])) && $client['password_md5'] === md5($password)) {
      //клиента нашли
      //данные о текущем залогиненом клиенте
      $this->current_client_data = $client;
      //в кеш
      $this->cache_client_data[$this->current_client_data['client_id']] = $this->current_client_data;
      
      //в вессию
      $_SESSION['malsch_session_client_id_' . SESSION_COURT] = $this->current_client_data['client_id'];
      
      return true;
    }
    $error_code = 1;
    //нет такого клиента
    return false;
  }
  
  //вход
  function loginCard($codecard)
  {
    $q    = 'select * from ' . Query::tableName('clients') . '
			where codecard = "' . (int)$codecard . '" and mode = 1 and active = 2';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      //клиента нашли
      //данные о текущем залогиненом клиенте
      $this->current_client_data = $temp[0];
      //в кеш
      $this->cache_client_data[$this->current_client_data['client_id']] = $this->current_client_data;
      //в вессию
      $_SESSION['malsch_session_client_id_' . SESSION_COURT] = $this->current_client_data['client_id'];
      
      return true;
    } else {
      //нет такого клиента
      return false;
    }
  }
  
  //выход
  function logOut()
  {
    $_SESSION['malsch_session_client_id_' . SESSION_COURT] = null;
    unset ($_SESSION['malsch_session_client_id_' . SESSION_COURT]);
    unset ($this->current_client_data);
    
    return true;
  }
  
  //данные клиента по логину
  public function getClientDataByLogin($login, &$client_data = []): bool
  {
    $q           = "select * from " . Query::tableName('clients') . " where binary login = :login";
    $client_data = Query::sqlQuery($q, [':login' => $login], true, ['onlyOne' => true]);
    if (!empty($client_data)) {
      
      return true;
    }
    
    return false;
  }
  
  //изменить пароль клиента
  function changeClientPassword($client_id, $password)
  {
    $query = 'update ' . Query::tableName('clients') . '
					set password_md5 = ' . ($password === null
        ? 'NULL'
        : ($password == ''
          ? 'password_md5'
          : '"' . md5(
            $password
          ) . '"')) . '
					where client_id = ' . $client_id;
    if (Query::sqlQuery($query, [], false)) {
      //все OK
      $error_code = 0;
      
      return true;
    }
    
    //echo '<p>'.mysql_error().'</p>';
    return false;
  }
  
  function getClientsDataByAboFit(&$clients_data, $clients_id = false)
  {
    
    $q = 'SELECT c.*, n.rate as nds_rate, 
       t.ticket_id, t.time_start, t.time_finish, t.weekdays, t.price_for_month, t.check_payd, t.count_month, 
       a.area_id, a.title as area, at.title as type, 
       tp.finish as ticket_finish_date, tp.start as ticket_start_date 
					FROM ' . Query::tableName('clients') . ' c
					     JOIN ' . Query::tableName('config_nds') . ' n 
					LEFT JOIN ' . Query::tableName('tickets') . ' t ON c.client_id = t.client_id
					LEFT JOIN ' . Query::tableName('tickets_periods') . ' tp ON t.ticket_id = tp.ticket_id
					LEFT JOIN ' . Query::tableName('areas') . ' a ON t.area_id = a.area_id
					LEFT JOIN ' . Query::tableName('areas_types') . ' at ON a.type_id = at.type_id
					WHERE a.area_id is not null AND IF((c.nds IS NULL OR  c.nds=0), n.set_default="1", n.nds_id = c.nds)'
      . Service::engines()->areas->sqlCheckGroupType('at')
      // . ($clients_id !== false ? 'and c.client_id in('. implode(',', $clients_id) .')' : '' ) .
//					' and (tp.finish is null || timestamp(date_format(tp.finish, "%Y-%m-01")) >= timestamp(date_add(date_format(tp.start, "%Y-%m-01"), interval if(t.check_payd is null, 0, t.check_payd) month))) ' . // проверка на дату окончания абониемента
      . ' ORDER BY c.surname, c.name';
    
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      $clients_data = [];
      //получаем строки таблицы
      foreach ($temp as $row) {
        $clients_data[$row['client_id']]                                                    = $row;
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['type']               = $row['type'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['area']               = $row['area'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['nds_rate']           = $row['nds_rate'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['area_id']            = $row['area_id'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['ticket_finish_date'] = $row['ticket_finish_date'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['ticket_start_date']  = $row['ticket_start_date'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['time_start']         = $row['time_start'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['time_finish']        = $row['time_finish'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['weekdays']           = $row['weekdays'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['price_for_month']    = $row['price_for_month'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['check_payd']         = $row['check_payd'];
        $clients_data[$row['client_id']]['ticket'][$row['ticket_id']]['count_month']        = $row['count_month'];
      }
      
      //print_r($clients_data);
      return true;
    } else {
      $clients_data = null;
      
      return false;
    }
  }
  
  
  /** Preference
   *  новое поле в таблице с клиентами.
   *  Формат хранения json.
   * @todo вынести в отдельный класс - для админов тоже есть такое поле, возможно стоит сделать общий способ его обновления и создания.
   *
   */
  public function getPreference($client_id, $key)
  {
    $preferences = $this->preferencesClient($client_id);
    
    return isset($preferences[$key]) ? $preferences[$key] : null;
  }
  
  public function setPreference($client_id, $key, $value)
  {
    $preferences       = $this->preferencesClient($client_id);
    $preferences[$key] = $value;
    
    return $this->setPreferencesClient($client_id, $preferences);
  }
  
  public function delPreference($client_id, $key)
  {
    $preferences = $this->preferencesClient($client_id);
    if (isset($preferences[$key])) {
      unset($preferences[$key]);
    }
    
    return $this->setPreferencesClient($client_id, $preferences);
  }
  
  protected function preferencesClient($client_id)
  {
    return json_decode(Query::sqlQuery('select preferences from ' . Query::tableName('clients') . ' where client_id="' . $client_id . '"',
      [],
      true,
      ['onlyOne' => true])['preferences'], true);
  }
  
  protected function setPreferencesClient($client_id, $preferences = [])
  {
    return Query::sqlQuery('update ' . Query::tableName('clients') . ' set preferences=\'' . json_encode($preferences) . '\' where client_id=\'' . $client_id . '\'');
  }
  
  public function blockingCouponInput($client_id)
  {
    if (($blockTime = $this->getPreference($client_id, 'blockingCoupon')) && $blockTime >= strtotime(date('Y-m-d'))) {
      return true;
    } elseif ($blockTime && $blockTime < strtotime(date('Y-m-d'))) {
      Service::session()->delete('cnt');
      $this->delPreference($client_id, 'blockingCoupon');
    }
    
    return false;
  }
  
  public function setBlockingCouponInput($client_id)
  {
    $this->setPreference($client_id, 'blockingCoupon', mktime(0, 0, 0, date('m'), (date('d') + 1)));
  }
  
  public function getClientsDataCount(array $mode = [1], ?int $active = 1, ?string $alpha = null): int
  {
    $params = $where = [];
    //условия выборки
    if ($mode !== null) {
      $params[] = implode(',', $mode);
      $where[] = 'mode in(?)';
    }
    if ($active !== null) {
      $params[] = (string)$active;
      $where[] = 'active = ?';
    }
    if ($alpha !== null) {
      $params[] = $alpha . '%';
      $where[] = 'surname LIKE ?';
    }
    $q = 'select count(*) as cnt from ' . Query::tableName('clients')
      . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '');
    if ($row = Query::sqlQuery($q, $params, true, ['onlyOne' => true])) {
      return $row['cnt'];
    }
    
    return 0;
  }
}