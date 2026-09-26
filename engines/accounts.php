<?php

use AC\app\entities\enums\AccountType;
use AC\core\engines\Engines;
use AC\core\modules\accounts\engines\OnlinePaymentInvoiceInstallEngine;
use AC\core\modules\config\models\ConfigClubStateModel;
use AC\core\modules\mailing\entities\dto\LetterTemplateDto;
use AC\core\modules\mailing\entities\enums\ModeTemplate;
use AC\core\system\db\Query;
use AC\core\system\helpers\JsonHelper;

class accounts
{
  public $table;
  /**
   * @var string
   */
  public $table_text_config;
  /**
   * @var string
   */
  public $table_clients;
  protected string $table_other_reservations;
  protected string $table_ticket_reservations;
  protected string $table_ticket_light_reservations;
  protected string $table_reservations;
  protected string $table_reservations_prepayment;
  protected string $table_confirmation_delete;
  protected string $table_delete;
  protected string $table_cl;

  function initialize()
  {
    $this->table                           = Query::tableName('accounts');
    $this->table_text_config               = Query::tableName('accounts_text_config');
    $this->table_clients                   = Query::tableName('accounts_clients');
    $this->table_other_reservations        = Query::tableName('accounts_reservations_others');
    $this->table_ticket_reservations       = Query::tableName('accounts_reservations_tickets');
    $this->table_ticket_light_reservations = Query::tableName('accounts_reservations_light_tickets');
    $this->table_reservations              = Query::tableName('accounts_reservations');
    $this->table_reservations_prepayment   = Query::tableName('accounts_reservations_prepayment');
    $this->table_confirmation_delete       = Query::tableName('accounts_confirmation_delete');
    $this->table_delete                    = Query::tableName('accounts_deleted');
    $this->table_cl                        = Query::tableName('clients');
  }

  //задать настройки системы
  function setConfig(/* ... */)
  {
    //параметры функции
    $aliases2store = [
      'account_mail',
      'account_attachment',
      //      'name_bank',
      //      'bank_code',
      //      'bank_number',
      'sepa_name',
      'sepa_iban',
      'sepa_bic',
      'sepa_direct_debit',
      'mail_for_duplicate_account',
    ];
    $q             = 'update ' . Query::tableName('config') . ' 
				set value = :index 
				where alias =:alias';
    foreach ($aliases2store as $index => $alias) {
      Query::sqlQuery($q, ['index' => func_get_arg($index), 'alias' => $alias], false);
    }
  }

  /**
   * Включить или отключить создание счетов после успешной онлайн-оплаты.
   */
  public function setOnlinePaymentInvoiceUse(bool $use): bool
  {
    if ($use) {
      (new OnlinePaymentInvoiceInstallEngine())->ensureInstalled();
    }

    $configEngine = getEngine('config', false);
    $configEngine->getConfigByType('account');

    return $configEngine->saveItem('online_payment_invoice_use', (string)(int)$use, 'account');
  }

  function setLetterConfig($subject, $content): bool
  {
    $template = LetterTemplateDto::fromArray(
      [
        'subject' => $subject,
        'content' => $content,
        'alias'   => 'account_file_send',
        'lang'    => config('lang')->getDefault(),
        'mode'    => ModeTemplate::SERVICE->value,
      ]
    );

    return Service::mailer()->getTemplatesEngine()->setTemplate($template);
  }

  function insertAccountClient(
    $sys_client_id,
    $name,
    $surname,
    $address,
    $post_code,
    $city,
    $email,
    $number,
    $sepa_data,
    $bank_data,
  ) {
    // Проверяем есть ли такой клиент в таблице счетов и не изменились ли у него данные да-хорошо\нет-добавляем
    $q = 'SELECT * FROM ' . $this->table_clients . '
								 WHERE sys_client_id = "' . $sys_client_id . '"
								 AND binary name = "' . $name . '"
								 AND binary surname = "' . $surname . '"
								 AND binary address = "' . $address . '"
								 AND binary post_code = "' . $post_code . '"
                 AND binary email = "' . $email . '"
								 AND binary city = "' . $city . '"
								 AND binary number = "' . $number . '"
								 AND binary sepa_iban = "' . $sepa_data[0] . '"
								 AND binary sepa_bic = "' . $sepa_data[1] . '"
								 AND binary sepa_mndtid = "' . $sepa_data[2] . '"
								 AND binary sepa_dtofsgntr = "' . $sepa_data[3] . '"
								 AND binary account_owner = "' . $bank_data[0] . '"
								 AND binary account_number = "' . $bank_data[1] . '"
								 AND binary bank_index = "' . $bank_data[2] . '"
								 AND binary bank_name = "' . $bank_data[3] . '"
								 AND outdated = "0"';

    $temp = Query::sqlQuery($q);

    if (empty($temp)) {
      //проверяем был ли этот чувак вообще раньше или у него просто были изменены данные
      //если данные были просто изменены то помечаем этих типков как старые
      $temp2 = Query::sqlQuery('SELECT * FROM ' . $this->table_clients . ' WHERE sys_client_id = "' . $sys_client_id . '"');
      if (!empty($temp2)) {
        $q = 'UPDATE ' . $this->table_clients . ' set outdated = "1" WHERE sys_client_id = :sys_client_id';
        Query::sqlQuery($q, ['sys_client_id' => $sys_client_id], false);
      }

      $q = 'insert into ' . $this->table_clients . ' set
								sys_client_id = :sys_client_id,
								name = :name,
								surname = :surname,
								address = :address,
								post_code = :post_code,
								city = :city,
								email = :email,
								number = :number, 
								sepa_iban = :sepa_iban,
								sepa_bic = :sepa_bic,
								sepa_mndtid = :sepa_mndtid,
								sepa_dtofsgntr = :sepa_dtofsgntr,
								account_owner = :account_owner,
								account_number = :account_number,
								bank_index = :bank_index,
								bank_name = :bank_name';

      $params = [
        'sys_client_id'  => $sys_client_id,
        'name'           => $name,
        'surname'        => $surname,
        'address'        => $address,
        'post_code'      => $post_code,
        'city'           => $city,
        'email'          => $email,
        'number'         => $number,
        'sepa_iban'      => $sepa_data[0],
        'sepa_bic'       => $sepa_data[1],
        'sepa_mndtid'    => $sepa_data[2],
        'sepa_dtofsgntr' => (empty($sepa_data[3]) ? null : $sepa_data[3]),
        'account_owner'  => $bank_data[0],
        'account_number' => $bank_data[1],
        'bank_index'     => $bank_data[2],
        'bank_name'      => $bank_data[3],
      ];

      if (Query::sqlQuery($q, $params, false)) {
        return Query::getLastId();
      }
    } else {
      if ($item = $temp[0]) {
        return $item['client_id'];
      }
    }

    return false;
  }


  function getMaxMinDate($type, $archive = false)
  {
    $useGroupType = in_array($type, [0, 1]);

    $q = 'SELECT MAX(ac.date_start) as max_date, MIN(ac.date_start) as min_date 
        FROM ' . $this->table . ' ac'
      . ($useGroupType ? '	LEFT JOIN ' . Query::tableName('areas') . ' a ON ac.area_id = a.area_id
					LEFT JOIN ' . Query::tableName('areas_types') . ' at ON a.type_id = at.type_id' : '')

      . ' WHERE ac.account_type = "' . $type . '"'
      . ($useGroupType ? Service::engines()->areas->sqlCheckGroupType('at') : '')
      . ' and ac.archives="' . (int)$archive . '" and ac.deleted="0"';

    return Query::sqlQuery($q, [], true, ['onlyOne' => true]);
  }

//################## СЧЕТА ##########################	
  //добавить
  function insertAccount(
    $sys_client_id,
    $name,
    $surname,
    $address,
    $post_code,
    $city,
    $email,
    $number,
    $date_start,
    $date_finish,
    $text_config,
    $nds,
    $sepa_data,
    $bank_data,
    $area_type_id = false,
  ) {
    if ($client_id = $this->insertAccountClient(
      $sys_client_id,
      $name,
      $surname,
      $address,
      $post_code,
      $city,
      $email,
      $number,
      $sepa_data,
      $bank_data
    )) {
      if (!Query::sqlQuery(
        'select count(a.account_id) as count from ' . $this->table . ' as a 
              inner join ' . Query::tableName('accounts_clients') . ' as aoac on a.client_id = aoac.client_id and aoac.sys_client_id = ' . $sys_client_id . '
              inner join ' . Query::tableName('areas') . ' as ar on a.area_id = ar.area_id and ar.type_id = ' . $area_type_id . '
              where  a.date_start = \'' . $date_start . '\' and a.account_type = "0" and a.deleted = "0"',
        [],
        true,
        ['onlyOne' => true]
      )['count']) {
        $q      = 'insert into ' . $this->table . ' set
							client_id = :client_id,
							account_type = :account_type,
							number = :number,
							date_start = :date_start, 
							date_finish = :date_finish, 
							date_creation = now(), 
							' . (isset($sepa_data[4]) ? 'sepa_type= :sepa_type, ' : '') . '
							' . (isset($sepa_data[5]) ? 'sepa_standart= :sepa_standart, ' : '') . '
							text_config = "' . $text_config . '",
							nds = "' . $nds . '"';
        $params = [
          'client_id'     => $client_id,
          'account_type'  => "0",
          'number'        => $this->generateAccountNumber(0),
          'date_start'    => $date_start,
          'date_finish'   => $date_finish,
          'sepa_type'     => (isset($sepa_data[4]) ? $sepa_data[4] : ''),
          'sepa_standart' => (isset($sepa_data[5]) ? $sepa_data[5] : ''),
        ];

        if (Query::sqlQuery($q, $params, false)) {
          $account_id = Query::getLastId();
          if ($this->insertReservationsAccount($account_id, $sys_client_id, $date_start, $date_finish, $area_type_id)) {
            return true;
          }
        }
      }
    }


    return false;
  }

  function insertReservationsAccount($account_id, $client_id, $date_start, $date_finish, $type = false)
  {
    if (!empty($type)) {
      $str = 'a.type_id="' . $type . '" and';
    } else {
      $str = '';
    }
    $reservationsTable  = Query::tableName('reservations');
    $groupReservationId = 'COALESCE(NULLIF(r.main_reservation_id, 0), r.reservation_id)';
    $groupCondition     = '(group_reservation.reservation_id = ' . $groupReservationId
      . ' OR group_reservation.main_reservation_id = ' . $groupReservationId . ')';
    $groupStart         = '(SELECT MIN(group_reservation.start) FROM ' . $reservationsTable
      . ' group_reservation WHERE ' . $groupCondition . ')';
    $groupFinish        = '(SELECT MAX(group_reservation.finish) FROM ' . $reservationsTable
      . ' group_reservation WHERE ' . $groupCondition . ')';
    $q  = 'insert ' . $this->table_reservations . ' (account_id, type, area, date, time_start, time_finish, price, light_price, heating_price, net_price, sys_reservation_id)
      select "' . $account_id . '", 
        concat(t.title, " - ", asp.title) as type_title, 
        a.title as area_title, 
        DATE_FORMAT(r.start, \'%Y-%m-%d\'), 
        TIME_FORMAT(COALESCE(' . $groupStart . ', r.start), \'%T\'),
        TIME_FORMAT(COALESCE(' . $groupFinish . ', r.finish), \'%T\'),
        r.price, 
        IF(r.light_state=\'1\', r.light_price, 0.00), 
        IF(r.heating_state=\'1\', r.heating_price, 0.00), 
        IF(r.net_state=\'1\', r.net_price, 0.00),
        r.reservation_id  
      from ' . $reservationsTable . ' r
      right join ' . Query::tableName('areas') . ' a ON r.area_id = a.area_id
      right join ' . Query::tableName('areas_types') . ' t ON a.type_id = t.type_id
      right join ' . Query::tableName('areas_sports') . ' asp ON a.sport_id = asp.sport_id
      where ' . $str . ' (r.client_id = "' . $client_id . '") and r.encash="1" and r.status="1" AND DATE_FORMAT(r.start, \'%Y-%m\') = "' . date(
        'Y-m',
        strtotime($date_start)
      ) . '"'
      . getEngine('specPrices')->sqlCheckDoNotShowOnAccount('r');
    $q2 = 'UPDATE ' . $this->table . ' SET area_id=(
      select  min(a.area_id) from ' . Query::tableName('areas') . ' a where a.type_id="' . $type . '")
      WHERE account_id=' . $account_id;
    if (Query::sqlQuery($q, [], false)) {
      if (Query::sqlQuery($q2, [], false)) {
        return true;
      }
    }

    return false;
  }

  //--------------- ВЫВОД------------------------
  function getAccounts($archives = false, $year_select = false, $type = false, $month_select = false)
  {
    $typeStr = ($type) ? ('asys.type_id="' . $type . '" and') : '';

    $q   = 'SELECT clsys.club_state as club_state,a.number as a_number, a.*, c.*, c.number as client_number, cl.number as cl_number, (SUM(r.price) + SUM(r.light_price)+ SUM(r.heating_price)) as sum FROM ' . $this->table . ' a
								LEFT JOIN ' . $this->table_clients . ' c ON a.client_id = c.client_id
								LEFT JOIN ' . $this->table_reservations . ' r ON a.account_id = r.account_id 
								LEFT JOIN ' . $this->table_cl . ' cl ON c.client_id = cl.client_id 
								LEFT JOIN ' . Query::tableName('areas') . ' asys ON asys.area_id = a.area_id
								LEFT JOIN ' . Query::tableName('clients') . ' clsys ON clsys.client_id = c.sys_client_id
								WHERE  ' . $typeStr . ' a.account_type = "0" /*AND a.deleted="0"*/ AND a.archives = "' . (!$archives ? '0' : '1') . ' " ' .
      ($year_select ? 'AND YEAR(a.date_start) = "' . $year_select . '"' : '') .
      ($month_select ? ' AND MONTH(a.date_start) = "' . $month_select . '"' : '') . '
								GROUP BY a.account_id';
    $out = Query::sqlQuery($q);
    if (!empty($out)) {
      foreach ($out as $key => $item) {
        if ($type == 2 && $item['sum'] == 0) {
          unset($out[$key]);
        }
      }

      return $out;
    } else {
      return false;
    }
  }

  /**
   * Получить действующие или архивные счета онлайн-оплаты.
   *
   * @return array|false
   */
  public function getOnlinePaymentAccounts(bool $archives = false, ?int $year = null, ?int $month = null): array|false
  {
    $conditions = [
      'a.account_type = :account_type',
      'a.archives = :archives',
    ];
    $params = [
      'account_type'          => AccountType::OnlinePayment->value,
      'archives'              => (int)$archives,
      'non_member_club_state' => ConfigClubStateModel::getMark('no_club_rate')?->id ?? 1,
    ];

    if ($year !== null) {
      $conditions[] = 'YEAR(a.date_start) = :year';
      $params['year'] = $year;
    }
    if ($month !== null) {
      $conditions[] = 'MONTH(a.date_start) = :month';
      $params['month'] = $month;
    }

    $accounts = Query::sqlQuery(
      'SELECT
        CASE
          WHEN account_client.sys_client_id IS NULL THEN :non_member_club_state
          ELSE client.club_state
        END AS club_state,
        a.number AS a_number,
        a.*,
        account_client.*,
        account_client.number AS client_number,
        client.number AS cl_number,
        COALESCE(reservation_totals.sum, 0) AS sum
      FROM ' . $this->table . ' a
      LEFT JOIN ' . $this->table_clients . ' account_client ON account_client.client_id = a.client_id
      LEFT JOIN ' . $this->table_cl . ' client ON client.client_id = account_client.sys_client_id
      LEFT JOIN (
        SELECT
          account_id,
          SUM(
            COALESCE(price, 0)
            + COALESCE(light_price, 0)
            + COALESCE(heating_price, 0)
          ) AS sum
        FROM ' . $this->table_reservations . '
        GROUP BY account_id
      ) reservation_totals ON reservation_totals.account_id = a.account_id
      WHERE ' . implode(' AND ', $conditions) . '
      ORDER BY a.date_start DESC, a.account_id DESC',
      $params
    );

    return !empty($accounts) ? $accounts : false;
  }

  /**
   * Получить онлайн-счета со снимками строк для просмотра и печати.
   *
   * @param list<int> $accountIds
   * @return array|false
   */
  public function getOnlinePaymentAccountsById(array $accountIds): array|false
  {
    $accountIds = array_values(array_unique(array_filter(
      array_map('intval', $accountIds),
      static fn(int $accountId): bool => $accountId > 0
    )));
    if ($accountIds === []) {
      return false;
    }

    $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
    $rows = Query::sqlQuery(
      'SELECT
        CASE
          WHEN account_client.sys_client_id IS NULL THEN ?
          ELSE client.club_state
        END AS club_state,
        a.number AS a_number,
        a.*,
        account_client.*,
        account_client.number AS client_number,
        client.firm,
        client.number AS cl_number,
        client.bank_sepa_referenz,
        COALESCE(client.email, account_client.email) AS sys_email,
        account_reservation.*
      FROM ' . $this->table . ' a
      LEFT JOIN ' . $this->table_clients . ' account_client ON account_client.client_id = a.client_id
      LEFT JOIN ' . $this->table_cl . ' client ON client.client_id = account_client.sys_client_id
      LEFT JOIN ' . $this->table_reservations . ' account_reservation ON account_reservation.account_id = a.account_id
      WHERE a.account_type = ? AND a.account_id IN (' . $placeholders . ')
      ORDER BY a.account_id, account_reservation.date, account_reservation.time_start',
      [ConfigClubStateModel::getMark('no_club_rate')?->id ?? 1, AccountType::OnlinePayment->value, ...$accountIds]
    );

    if (empty($rows)) {
      return false;
    }

    $accounts = [];
    foreach ($rows as $row) {
      $accountId = (int)$row['account_id'];
      if (!isset($accounts[$accountId])) {
        $accounts[$accountId] = [
          'a_number'             => $row['a_number'],
          'firm'                 => $row['firm'],
          'a_date_start'         => $row['date_creation'] ?: $row['date_start'],
          'date_start'           => $row['date_start'],
          'date_finish'          => $row['date_finish'],
          'date_creation'        => $row['date_creation'] ?: $row['date_start'],
          'execution'            => $row['execution'],
          'send'                 => $row['send'],
          'archives'             => $row['archives'],
          'name'                 => $row['name'],
          'surname'              => $row['surname'],
          'address'              => $row['address'],
          'post_code'            => $row['post_code'],
          'city'                 => $row['city'],
          'email'                => $row['email'],
          'client_number'        => $row['client_number'],
          'cl_number'            => $row['cl_number'],
          'account_owner'        => $row['account_owner'],
          'account_number'       => $row['account_number'],
          'bank_index'           => $row['bank_index'],
          'bank_name'            => $row['bank_name'],
          'text_config'          => $row['text_config'],
          'price_info'           => $row['price_info'],
          'nds'                  => $row['nds'],
          'sepa_type'            => $row['sepa_type'],
          'sepa_standart'        => $row['sepa_standart'],
          'sepa_iban'            => $row['sepa_iban'],
          'sepa_bic'             => $row['sepa_bic'],
          'sepa_mndtid'          => $row['sepa_mndtid'],
          'sepa_dtofsgntr'       => $row['sepa_dtofsgntr'],
          'bank_sepa_referenz'   => $row['bank_sepa_referenz'],
          'sys_email'            => $row['sys_email'],
          'text_fields'          => JsonHelper::decode($row['text_fields'], true) ?: [],
          'reservations'         => [],
        ];
      }

      if ($row['reservation_id'] !== null) {
        $reservationId = (int)$row['reservation_id'];
        $accounts[$accountId]['reservations'][$reservationId] = [
          'type'          => $row['type'],
          'area'          => $row['area'],
          'date'          => $row['date'],
          'time_start'    => $row['time_start'],
          'time_finish'   => $row['time_finish'],
          'price'         => $row['price'],
          'light_price'   => $row['light_price'],
          'heating_price' => $row['heating_price'],
          'net_price'     => $row['net_price'],
          'guest'         => 0,
          'comment'       => $row['comment'] ?? '',
          'street_friends' => [],
        ];
      }
    }

    return $accounts;
  }

  function getSecondPlayer($account_list, &$out)
  {
    $str_account_list = (is_array($account_list)) ? implode(",", $account_list) : $account_list;
    $q                = 'SELECT accounts.account_id,
        clients.sys_client_id 	as main_client_id,
        accounts.date_start 	as account_start,
        accounts.date_finish 	as account_finish,
        clmain.name as main_client_name,
        clmain.surname as main_client_surname,
        resmain.reservation_id 	as main_reservation_id,
        res.reservation_id 	as second_reservation_id, 
        acord.reservation_id 	as account_reservation_id,
        resmain.area_id 	as area_id,
        resmain.start 		as main_start,
        resmain.finish 		as main_finish,
        IF(cl.name is not null, cl.name, res.client_name) as second_name,
        IF(cl.surname is not null, cl.surname, res.client_surname) as second_surname,
        res.client_name,
        res.client_surname,
        cl.* 
        FROM ' . Query::tableName('accounts') . ' as accounts 
        INNER JOIN ' . Query::tableName('accounts_clients') . ' as clients on clients.client_id=accounts.client_id 
        INNER JOIN ' . Query::tableName('reservations') . ' resmain ON clients.sys_client_id=resmain.client_id 
        LEFT JOIN ' . Query::tableName('reservations') . ' res ON res.main_reservation_id=resmain.reservation_id 
        LEFT JOIN ' . Query::tableName('clients') . ' cl ON cl.client_id=res.client_id 
        LEFT JOIN ' . Query::tableName('clients') . ' clmain ON clmain.client_id=resmain.client_id 
        LEFT JOIN ' . Query::tableName('accounts_reservations') . ' as acord 
            ON DATE_FORMAT(resmain.start,\'%Y-%m-%d\')=acord.date and 
            acord.account_id=accounts.account_id and 
            TIME_FORMAT(resmain.start,\'%H:%i:%S\')=TIME_FORMAT(acord.time_start, \'%H:%i:%S\') 
        WHERE 	resmain.finish<= accounts.date_finish and 
            resmain.start>=accounts.date_start  and
            accounts.account_id in (' . $str_account_list . ') and 
            resmain.status="1" ORDER BY resmain.start';
    $temp             = Query::sqlQuery($q);
    if (!empty($temp)) {
      foreach ($temp as $item) {
        if (empty($out[$item['account_id']]['main_client_id'])) {
          $out[$item['account_id']]['main_client_id']      = $item['main_client_id'];
          $out[$item['account_id']]['main_client_name']    = $item['main_client_name'];
          $out[$item['account_id']]['main_client_surname'] = $item['main_client_surname'];
          $out[$item['account_id']]['account_start']       = $item['account_start'];
          $out[$item['account_id']]['account_finish']      = $item['account_finish'];
        }
        unset($item['account_start']);
        unset($item['account_finish']);
        unset($item['main_client_name']);
        unset($item['main_client_surname']);
        unset($item['main_client_id']);
        if (!empty($item['account_id']) && !empty($out[$item['account_id']]['reservations'][$item['account_reservation_id']])) {
          $out[$item['account_id']]['reservations'][$item['account_reservation_id']]['second'] = $item;
        }
      }
    }

    return count($temp);
  }

  function getAccountsById($accounts_id = [], $type = false, $additionalFields = [])
  {
    $typeStr = ($type) ? ('asys.type_id="' . $type . '" and') : '';
    $q       = 'select cl.firm as firm,a.number as a_number, a.*, c.*, c.number as client_number, cl.number as cl_number, 
                    cl.bank_sepa_referenz, ar.*,  if(asys.type_id = 2 and r.client_id is null , 1, 0) as guest, r.street_friends as street_friends, cl.email as sys_email'
      . (isset($additionalFields['sql']['select']) ? ', ' . implode(',', (array)$additionalFields['sql']['select']) : '')
      . '
       from ' . $this->table . ' a  
        LEFT JOIN ' . $this->table_clients . ' c ON a.client_id = c.client_id 
        LEFT JOIN ' . $this->table_reservations . ' ar ON a.account_id = ar.account_id 
        LEFT JOIN ' . $this->table_cl . ' cl ON cl.client_id = c.sys_client_id 
        LEFT JOIN ' . Query::tableName('reservations') . ' r ON ar.sys_reservation_id = r.reservation_id
        LEFT JOIN ' . Query::tableName('areas') . ' asys ON asys.area_id = r.area_id
       WHERE ' . $typeStr . ' a.account_id IN (' . join(
        ',',
        $accounts_id
      ) . ') and r.main_reservation_id is null ORDER BY a.account_id, `date` ASC, `time_start` ASC';

    $temp         = Query::sqlQuery($q);
    $account_list = [];
    if (!empty($temp)) {
      foreach ($temp as $item) {
        $out[$item['account_id']]['a_number']                                                = $item['a_number'];
        $out[$item['account_id']]['firm']                                                    = $item['firm'];
        $out[$item['account_id']]['a_date_start']                                            = $item['date_creation'] ?: $item['date_start'];
        $out[$item['account_id']]['date_start']                                              = $item['date_start'];
        $out[$item['account_id']]['date_finish']                                             = $item['date_finish'];
        $out[$item['account_id']]['date_creation']                                           = $item['date_creation'] ?: $item['date_start'];
        $out[$item['account_id']]['execution']                                               = $item['execution'];
        $out[$item['account_id']]['send']                                                    = $item['send'];
        $out[$item['account_id']]['archives']                                                = $item['archives'];
        $out[$item['account_id']]['name']                                                    = $item['name'];
        $out[$item['account_id']]['surname']                                                 = $item['surname'];
        $out[$item['account_id']]['address']                                                 = $item['address'];
        $out[$item['account_id']]['post_code']                                               = $item['post_code'];
        $out[$item['account_id']]['city']                                                    = $item['city'];
        $out[$item['account_id']]['email']                                                   = $item['email'];
        $out[$item['account_id']]['client_number']                                           = $item['client_number'];
        $out[$item['account_id']]['cl_number']                                               = $item['cl_number'];
        $out[$item['account_id']]['account_owner']                                           = $item['account_owner'];
        $out[$item['account_id']]['account_number']                                          = $item['account_number'];
        $out[$item['account_id']]['bank_index']                                              = $item['bank_index'];
        $out[$item['account_id']]['bank_name']                                               = $item['bank_name'];
        $out[$item['account_id']]['text_config']                                             = $item['text_config'];
        $out[$item['account_id']]['nds']                                                     = $item['nds'];
        $out[$item['account_id']]['sepa_type']                                               = $item['sepa_type'];
        $out[$item['account_id']]['sepa_standart']                                           = $item['sepa_standart'];
        $out[$item['account_id']]['sepa_iban']                                               = $item['sepa_iban'];
        $out[$item['account_id']]['sepa_bic']                                                = $item['sepa_bic'];
        $out[$item['account_id']]['sepa_mndtid']                                             = $item['sepa_mndtid'];
        $out[$item['account_id']]['sepa_dtofsgntr']                                          = $item['sepa_dtofsgntr'];
        $out[$item['account_id']]['bank_sepa_referenz']                                      = $item['bank_sepa_referenz'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['type']           = $item['type'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['area']           = $item['area'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['date']           = $item['date'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['time_start']     = $item['time_start'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['time_finish']    = $item['time_finish'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['price']          = $item['price'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['light_price']    = $item['light_price'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['heating_price']  = $item['heating_price'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['net_price']      = $item['net_price'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['guest']          = $item['guest'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['comment']        = $item['comment'] ?? '';
        $out[$item['account_id']]['sys_email']                                               = $item['sys_email'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['street_friends'] = unserialize($item['street_friends'] ?? '',
          ['allowed_classes' => false]);
        $out[$item['account_id']]['text_fields']                                             = JsonHelper::decode($item['text_fields'], true) ?: [];
        $account_list[$item['account_id']]                                                   = $item['account_id'];
      }
      $this->getSecondPlayer($account_list, $out);

      return $out;
    } else {
      return false;
    }
  }

//################ АБОНЕМЕНТЫ #########################

  function insertAboAccount(
    $sys_client_id,
    $name,
    $surname,
    $address,
    $post_code,
    $city,
    $email,
    $number,
    $time_start,
    $time_finish,
    $text_config,
    $weekdays,
    $area_type,
    $area,
    $nds,
    $ticket_id,
    $abo_sum,
    $sepa_data,
    $bank_data,
    $count_game,
    $price_info,
    $info,
    $periods = [],
    $area_id = false,
  ) {
    if ($client_id = $this->insertAccountClient(
      $sys_client_id,
      $name,
      $surname,
      $address,
      $post_code,
      $city,
      $email,
      $number,
      $sepa_data,
      $bank_data
    )) {
      if (!Query::sqlQuery(
        'select count(a.account_id) as count from ' . $this->table . ' as a 
              where a.account_type = "1" and a.deleted = "0" and ticket_id = "' . $ticket_id . '"',
        [],
        true,
        ['onlyOne' => true]
      )['count']) {
        $q = 'insert into ' . $this->table . ' set
							client_id = :client_id,
							account_type = :account_type,
							number = :number,
                            date_start = :date_start,
							date_finish = :date_finish, 
							date_creation = now(), 
							' . (isset($sepa_data[4]) ? 'sepa_type= :sepa_type, ' : '') . '
							' . (isset($sepa_data[5]) ? 'sepa_standart= :sepa_standart, ' : '') . '
							nds = :nds,
							abo_sum = :abo_sum,
							weekdays = :weekdays, 
							text_config = :text_config, 
							count_game = :count_game, 
							price_info = :price_info, 
                            info = :info,
							ticket_id = :ticket_id, 
							area_id= :area_id';

        $params = [
          'client_id'     => $client_id,
          'account_type'  => "1",
          'number'        => $this->generateAccountNumber(1),
          'date_start'    => date('Y-m-d') . ' ' . $time_start,
          'date_finish'   => date('Y-m-d') . ' ' . $time_finish,
          'nds'           => $nds,
          'abo_sum'       => $abo_sum,
          'weekdays'      => $weekdays,
          'text_config'   => $text_config,
          'count_game'    => $count_game,
          'price_info'    => $price_info,
          'info'          => $info,
          'ticket_id'     => $ticket_id,
          'area_id'       => (($area_id) ? $area_id : null),
          'sepa_type'     => (isset($sepa_data[4]) ? $sepa_data[4] : ''),
          'sepa_standart' => (isset($sepa_data[5]) ? $sepa_data[5] : ''),
        ];

        if (Query::sqlQuery($q, $params, false)) {
          $account_id = Query::getLastId();
          if ($this->insertAboReservationsAccount($account_id, $periods, $area_type, $area)) {
            return true;
          }
        }
      }
    }

    return false;
  }

  function insertAboFitAccount(
    $sys_client_id,
    $name,
    $surname,
    $adres,
    $post_code,
    $city,
    $email,
    $number,
    $time_start,
    $time_finish,
    $text_config,
    $weekdays,
    $area_type,
    $area,
    $nds,
    $ticket_id,
    $check_payd,
    $abo_sum,
    $sepa_data,
    $bank_data,
    $count_game,
    $price_info,
    $info,
    $start_fit,
    $finish_fit,
    $area_id = null,
  ) {
    if ($check_payd) {
      $new_check_payd = $check_payd + 1;
    } else {
      $new_check_payd = 1;
    }

    if ($client_id = $this->insertAccountClient($sys_client_id, $name, $surname, $adres, $post_code, $city, $email,
      $number, $sepa_data, $bank_data)) {
      $q = 'insert into ' . $this->table . ' set
							client_id = "' . $client_id . '",
							account_type = "1",
							number = "' . $this->generateAccountNumber(1) . '",
              date_start = "' . date('Y-m-d', strtotime($start_fit)) . ' ' . $time_start . '",
							date_finish = "' . date('Y-m-d', strtotime($finish_fit)) . ' ' . $time_finish . '", 
							' . (isset($sepa_data[4]) ? 'sepa_type="' . $sepa_data[4] . '", ' : '') . '
							' . (isset($sepa_data[5]) ? 'sepa_standart="' . $sepa_data[5] . '", ' : '') . '
							nds = "' . $nds . '",
							abo_sum = "' . $abo_sum . '",
							weekdays = "' . $weekdays . '", 
							text_config = "' . $text_config . '", 
							count_game = "' . addslashes($count_game) . '", 
							price_info = "' . addslashes($price_info) . '", 
                            info = "' . addslashes($info) . '",
							ticket_id = "' . $ticket_id . '", 
              area_id = "' . $area_id . '"';

      if (Query::sqlQuery($q, [], false)) {
        $account_id = Query::getLastId();
        if ($this->insertAboFitReservationsAccount($account_id, $abo_sum, $area_type, $area, $start_fit, $finish_fit)) {
          if ($new_check_payd) {
            Query::sqlQuery('update ' . Query::tableName('tickets') . ' set check_payd="' . $new_check_payd . '" where ticket_id = "' . $ticket_id . '"',
              [], false);
          }

          return true;
        }
      }
    }

    return false;
  }

  function insertAboReservationsAccount($account_id, $periods, $area_type, $area)
  {
    if (is_array($periods)) {
      $q = 'INSERT ' . $this->table_ticket_reservations . ' (account_id, type, area, date_start, date_finish, price) VALUES (:account_id, :area_type, :area, :start, :finish, :sum_price)';
      foreach ($periods as $p) {
        $params = [
          'account_id' => $account_id,
          'area_type'  => $area_type,
          'area'       => $area,
          'start'      => $p['start'],
          'finish'     => $p['finish'],
          'sum_price'  => $p['sum_price'],
        ];
        if (!Query::sqlQuery($q, $params, false)) {
          return false;
        }
      }

      return true;
    }

    return false;
  }

  function insertAboFitReservationsAccount($account_id, $abo_sum, $area_type, $area, $start_fit, $finish_fit)
  {
    $tmp = '("' . $account_id . '", "' . addslashes($area_type) . '", "' . addslashes($area) . '", "' . $start_fit . '", "' . $finish_fit . '", "' . $abo_sum . '")';
    if (Query::sqlQuery('INSERT ' . $this->table_ticket_reservations . ' (account_id, type, area, date_start, date_finish, price) VALUES ' . $tmp, [],
      false)) {
      return true;
    }

    return false;
  }

  function insertLightAboReservationsAccount($account_id, $type, $periods)
  {
    if (is_array($periods)) {
      $q = 'INSERT ' . $this->table_ticket_light_reservations . ' (account_id, type, date_start, time_start, price, area_type, area) VALUES (:account_id, :type, :date_start, :time_start, :price, :area_type, :area)';
      foreach ($periods as $p) {
        $params = [
          'account_id' => $account_id,
          'type'       => $type,
          'date_start' => $p[0],
          'time_start' => $p[1],
          'price'      => $p[2],
          'area_type'  => $p[3],
          'area'       => $p[4],
        ];
        if (!Query::sqlQuery($q, $params, false)) {
          return false;
        }
      }

      return true;
    }

    return false;
  }

  //--------------- ВЫВОД------------------------
  function getAboAccounts($archives = false, $year_select = false, $month = false, $type = false)
  {
    $typeStr = ($type) ? ('asys.type_id="' . $type . '" and') : '';

    $q    = 'SELECT cl.club_state as club_state,asys.type_id as area_type_id,a.number as a_number, a.date_start as a_date_start, a.*, 
       TIME_FORMAT(a.date_start, \'%H:%i\') as time_start, TIME_FORMAT(a.date_finish, \'%H:%i\') as time_finish, 
       c.*, c.number as client_number, cl.bank_sepa_referenz, 
       r.*, rl.reservation_id AS light_reservation_id, rl.date_start AS light_date_start, rl.time_start AS light_time_start, rl.price AS light_price 
       FROM ' . $this->table . ' a
      LEFT JOIN ' . $this->table_clients . ' c ON a.client_id = c.client_id
      LEFT JOIN ' . $this->table_ticket_reservations . ' r ON a.account_id = r.account_id
      LEFT JOIN ' . $this->table_ticket_light_reservations . ' rl ON a.account_id = rl.account_id
      LEFT JOIN ' . $this->table_cl . ' cl ON cl.client_id = c.sys_client_id 
      LEFT JOIN ' . Query::tableName('areas') . ' asys ON asys.area_id = a.area_id  
      LEFT JOIN ' . Query::tableName('areas_types') . ' at ON asys.type_id = at.type_id  
      WHERE ' . $typeStr . ' a.account_type = "1" /*AND a.deleted="0"*/ AND a.archives = "' . (!$archives ? '0' : '1') . '"'
      . Service::engines()->areas->sqlCheckGroupType('at')
      . ($year_select ? ' AND YEAR(a.date_start) = "' . $year_select . '"' : '')
      . ($month ? ' AND month(a.date_start) = "' . $month . '"' : '');
    $temp = Query::sqlQuery($q);

    if (!empty($temp)) {
      foreach ($temp as $item) {
        $out[$item['account_id']]['club_state']                                           = $item['club_state'];
        $out[$item['account_id']]['a_number']                                             = $item['a_number'];
        $out[$item['account_id']]['sys_client_id']                                        = $item['sys_client_id'];
        $out[$item['account_id']]['a_date_start']                                         = $item['a_date_start'];
        $out[$item['account_id']]['date_start']                                           = $item['date_start'];
        $out[$item['account_id']]['time_start']                                           = $item['time_start'];
        $out[$item['account_id']]['time_finish']                                          = $item['time_finish'];
        $out[$item['account_id']]['weekdays']                                             = $item['weekdays'];
        $out[$item['account_id']]['execution']                                            = $item['execution'];
        $out[$item['account_id']]['send']                                                 = $item['send'];
        $out[$item['account_id']]['archives']                                             = $item['archives'];
        $out[$item['account_id']]['deleted']                                              = $item['deleted'];
        $out[$item['account_id']]['name']                                                 = $item['name'];
        $out[$item['account_id']]['surname']                                              = $item['surname'];
        $out[$item['account_id']]['address']                                              = $item['address'];
        $out[$item['account_id']]['post_code']                                            = $item['post_code'];
        $out[$item['account_id']]['city']                                                 = $item['city'];
        $out[$item['account_id']]['email']                                                = $item['email'];
        $out[$item['account_id']]['price_info']                                           = $item['price_info'];
        $out[$item['account_id']]['count_game']                                           = $item['count_game'];
        $out[$item['account_id']]['client_number']                                        = $item['client_number'];
        $out[$item['account_id']]['account_owner']                                        = $item['account_owner'];
        $out[$item['account_id']]['account_number']                                       = $item['account_number'];
        $out[$item['account_id']]['bank_index']                                           = $item['bank_index'];
        $out[$item['account_id']]['bank_name']                                            = $item['bank_name'];
        $out[$item['account_id']]['type']                                                 = $item['type'];
        $out[$item['account_id']]['area']                                                 = $item['area'];
        $out[$item['account_id']]['abo_sum']                                              = $item['abo_sum'];
        $out[$item['account_id']]['ticket_id']                                            = $item['ticket_id'];
        $out[$item['account_id']]['nds']                                                  = $item['nds'];
        $out[$item['account_id']]['sepa_type']                                            = $item['sepa_type'];
        $out[$item['account_id']]['sepa_standart']                                        = $item['sepa_standart'];
        $out[$item['account_id']]['sepa_iban']                                            = $item['sepa_iban'];
        $out[$item['account_id']]['sepa_bic']                                             = $item['sepa_bic'];
        $out[$item['account_id']]['sepa_mndtid']                                          = $item['sepa_mndtid'];
        $out[$item['account_id']]['sepa_dtofsgntr']                                       = $item['sepa_dtofsgntr'];
        $out[$item['account_id']]['bank_sepa_referenz']                                   = $item['bank_sepa_referenz'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['date_start']  = $item['date_start'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['date_finish'] = $item['date_finish'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['price']       = $item['price'];
        if (!empty($item['light_reservation_id'])) {
          $out[$item['account_id']]['light_reservations'][$item['light_reservation_id']]['date_start'] = $item['light_date_start'];
          $out[$item['account_id']]['light_reservations'][$item['light_reservation_id']]['time_start'] = $item['light_time_start'];
          $out[$item['account_id']]['light_reservations'][$item['light_reservation_id']]['price']      = $item['light_price'];
        }
      }

      return $out;
    } else {
      return false;
    }
  }

  function getAboAccountsById($accounts_id = [], $type = false)
  {
    $typeStr = ($type) ? ('asys.type_id="' . $type . '" and') : '';

    $q    = 'SELECT cl.firm as firm,a.number as a_number, a.date_start as a_date_start, a.*, TIME_FORMAT(a.date_start, \'%H:%i\') as time_start, 
       TIME_FORMAT(a.date_finish, \'%H:%i\') as    time_finish, c.*, c.number as client_number, cl.number as cl_number, a.ticket_id, cl.bank_sepa_referenz, r.*, 
       rl.type as light_type, rl.reservation_id AS light_reservation_id, rl.date_start AS light_date_start, rl.time_start AS light_time_start, rl.price AS light_price,    
       rl.area_type as light_area_type, rl.area as light_area, cl.email as sys_email 
    FROM ' . $this->table . ' a
								LEFT JOIN ' . $this->table_clients . ' c ON a.client_id = c.client_id
								LEFT JOIN ' . $this->table_ticket_reservations . ' r ON a.account_id = r.account_id
								LEFT JOIN ' . $this->table_ticket_light_reservations . ' rl ON a.account_id = rl.account_id
								LEFT JOIN ' . $this->table_cl . ' cl ON cl.client_id = c.sys_client_id 
								LEFT JOIN ' . Query::tableName('areas') . ' asys ON asys.area_id = a.area_id  
								WHERE ' . $typeStr . ' a.account_id IN (' . join(',', $accounts_id) . ')';
    $temp = Query::sqlQuery($q);

    if (!empty($temp)) {
      foreach ($temp as $item) {
        $out[$item['account_id']]['a_number']                                                                             = $item['a_number'];
        $out[$item['account_id']]['firm']                                                                                 = $item['firm'];
        $out[$item['account_id']]['a_date_start']                                                                         = $item['date_creation']
          ?: $item['date_start'];
        $out[$item['account_id']]['firm']                                                                                 = $item['firm'];
        $out[$item['account_id']]['a_date_start']                                                                         = $item['a_date_start'];
        $out[$item['account_id']]['date_start']                                                                           = $item['date_start'];
        $out[$item['account_id']]['date_creation']                                                                        = $item['date_creation'];
        $out[$item['account_id']]['name']                                                                                 = $item['name'];
        $out[$item['account_id']]['surname']                                                                              = $item['surname'];
        $out[$item['account_id']]['address']                                                                              = $item['address'];
        $out[$item['account_id']]['post_code']                                                                            = $item['post_code'];
        $out[$item['account_id']]['city']                                                                                 = $item['city'];
        $out[$item['account_id']]['email']                                                                                = $item['email'];
        $out[$item['account_id']]['client_number']                                                                        = $item['client_number'];
        $out[$item['account_id']]['cl_number']                                                                            = $item['cl_number'];
        $out[$item['account_id']]['account_owner']                                                                        = $item['account_owner'];
        $out[$item['account_id']]['account_number']                                                                       = $item['account_number'];
        $out[$item['account_id']]['bank_index']                                                                           = $item['bank_index'];
        $out[$item['account_id']]['bank_name']                                                                            = $item['bank_name'];
        $out[$item['account_id']]['nds']                                                                                  = $item['nds'];
        $out[$item['account_id']]['abo_sum']                                                                              = $item['abo_sum'];
        $out[$item['account_id']]['text_config']                                                                          = $item['text_config'];
        $out[$item['account_id']]['price_info']                                                                           = $item['price_info'];
        $out[$item['account_id']]['count_game']                                                                           = $item['count_game'];
        $out[$item['account_id']]['info']                                                                                 = $item['info'];
        $out[$item['account_id']]['ticket_id']                                                                            = $item['ticket_id'];
        $out[$item['account_id']]['sepa_type']                                                                            = $item['sepa_type'];
        $out[$item['account_id']]['sepa_standart']                                                                        = $item['sepa_standart'];
        $out[$item['account_id']]['sepa_iban']                                                                            = $item['sepa_iban'];
        $out[$item['account_id']]['sepa_bic']                                                                             = $item['sepa_bic'];
        $out[$item['account_id']]['sepa_mndtid']                                                                          = $item['sepa_mndtid'];
        $out[$item['account_id']]['sepa_dtofsgntr']                                                                       = $item['sepa_dtofsgntr'];
        $out[$item['account_id']]['bank_sepa_referenz']                                                                   = $item['bank_sepa_referenz'];
        $out[$item['account_id']]['reservations'][$item['account_id']]['time_start']                                      = $item['time_start'];
        $out[$item['account_id']]['reservations'][$item['account_id']]['time_finish']                                     = $item['time_finish'];
        $out[$item['account_id']]['reservations'][$item['account_id']]['weekdays']                                        = $item['weekdays'];
        $out[$item['account_id']]['reservations'][$item['account_id']]['type']                                            = $item['type'];
        $out[$item['account_id']]['reservations'][$item['account_id']]['area']                                            = $item['area'];
        $out[$item['account_id']]['reservations'][$item['account_id']]['periods'][$item['reservation_id']]['date_start']  = $item['date_start'];
        $out[$item['account_id']]['reservations'][$item['account_id']]['periods'][$item['reservation_id']]['date_finish'] = $item['date_finish'];
        $out[$item['account_id']]['reservations'][$item['account_id']]['periods'][$item['reservation_id']]['price']       = $item['price'];
        if (!empty($item['light_reservation_id'])) {
          $out[$item['account_id']]['light_reservations'][$item['light_reservation_id']]['type']       = $item['light_type'];
          $out[$item['account_id']]['light_reservations'][$item['light_reservation_id']]['date_start'] = $item['light_date_start'];
          $out[$item['account_id']]['light_reservations'][$item['light_reservation_id']]['time_start'] = $item['light_time_start'];
          $out[$item['account_id']]['light_reservations'][$item['light_reservation_id']]['price']      = $item['light_price'];
          $out[$item['account_id']]['light_reservations'][$item['light_reservation_id']]['area_type']  = $item['light_area_type'];
          $out[$item['account_id']]['light_reservations'][$item['light_reservation_id']]['area']       = $item['light_area'];
        }
        $out[$item['account_id']]['text_fields'] = JsonHelper::decode($item['text_fields'], true) ?: [];
        $out[$item['account_id']]['sys_email']   = $item['sys_email'];
      }

      return $out;
    } else {
      return false;
    }
  }

//################## ДОПОЛНИТЕЛЬНЫЕ СЧЕТА ##########################
  //добавить
  function insertOtherAccount(
    $sys_client_id,
    $name,
    $surname,
    $address,
    $post_code,
    $city,
    $email,
    $number,
    $date_start,
    $date_finish,
    $text_config,
    $nds,
    $sepa_data,
    $bank_data,
    $reservations = [],
    $area_id = false,
  ) {
    if ($client_id = $this->insertAccountClient(
      $sys_client_id,
      $name,
      $surname,
      $address,
      $post_code,
      $city,
      $email,
      $number,
      $sepa_data,
      $bank_data
    )
    ) {
      $q = 'insert into ' . $this->table . ' set
						client_id = "' . $client_id . '",
						account_type = "2",
						number = "' . $this->generateAccountNumber(2) . '",
						date_start = "' . $date_start . '", 
						date_finish = ' . ($date_finish == null ? 'NULL' : '"' . $date_finish . '"') . ',
						date_creation = now(), 
						' . (isset($sepa_data[4]) ? 'sepa_type="' . $sepa_data[4] . '", ' : '') . '
						' . (isset($sepa_data[5]) ? 'sepa_standart="' . $sepa_data[5] . '", ' : '') . '
						text_config = "' . $text_config . '",
						nds = "' . $nds . '"';


      if (Query::sqlQuery($q, [], false)) {
        $account_id = Query::getLastId();
        if ($this->insertReservationsOtherAccount(
          $account_id,
          $reservations
        )
        ) {
          return true;
        }
      }
    }

    return false;
  }

  function insertReservationsOtherAccount($account_id, $reservations = [])
  {
    if (is_array($reservations)) {
      $q = 'INSERT ' . $this->table_other_reservations . ' (account_id, title, count, price) VALUES ( :account_id, :title, :count, :price)';
      foreach ($reservations as $r) {
        $params = [
          'account_id' => $account_id,
          'title'      => $r['title'],
          'count'      => $r['count'],
          'price'      => $r['price'],
        ];
        if (!Query::sqlQuery($q, $params, false)) {
          return false;
        }
      }

      return true;
    }

    return false;
  }

  //--------------- ВЫВОД------------------------
  function getOtherAccounts($archives = false, $year_select = false, $month_select = false)
  {
    $q   = 'SELECT clsys.club_state as club_state ,a.number as a_number, a.*, c.*, SUM(r.count*r.price) as sum FROM ' . $this->table . ' a
								LEFT JOIN ' . $this->table_clients . ' c ON a.client_id = c.client_id
								LEFT JOIN ' . $this->table_other_reservations . ' r ON a.account_id = r.account_id 
								LEFT JOIN ' . Query::tableName('areas') . ' asys ON asys.area_id = a.area_id 
								LEFT JOIN ' . Query::tableName('clients') . ' clsys ON clsys.client_id = c.sys_client_id
								WHERE  a.account_type = "2" /*AND a.deleted="0"*/ AND a.archives = "' . (!$archives ? '0' : '1') . '" ' .
      ($year_select ? 'AND YEAR(a.date_start) = "' . $year_select . '"' : '') .
      ($month_select ? ' AND MONTH(a.date_start) = "' . $month_select . '"' : '')
      . '
								GROUP BY a.account_id';
    $out = Query::sqlQuery($q);
    if (!empty($out)) {
      return $out;
    } else {
      return false;
    }
  }

  /**
   * Получить месяцы и годы, в которых существуют счета указанного типа.
   */
  public function getMonthByYearDate(AccountType $accountType, bool $archive = true, ?int $typeCourt = null): array
  {
    $out = $months = [];
    if (!$archive) {
      $out['all'] = [];
    }
    $q = 'SELECT year(a.date_start) as year, month(a.date_start) as month
            FROM ' . $this->table . ' as a ' .
      ($typeCourt ? 'INNER JOIN ' . Query::tableName('areas') . ' asys ON asys.area_id = a.area_id AND asys.type_id = ' . (int)$typeCourt : '') .
      ' WHERE a.account_type = "' . $accountType->value . '" and a.archives = "' . (int)$archive . '"' .
      ($accountType === AccountType::PrivateAccount ? ' AND (a.ticket_id IS NULL OR a.ticket_id = 0)' : '') .
      ' group by year(a.date_start), month(a.date_start)
            order by year(a.date_start) desc, month(a.date_start) desc';
    if ($rows = Query::sqlQuery($q)) {
      foreach ($rows as $row) {
        if (!in_array($row['month'], $out[$row['year']] ?: [])) {
          $out[$row['year']][] = $row['month'];
        }
        if (!in_array($row['month'], $months)) {
          $months[] = $row['month'];
        }
      }
      if (!$archive) {
        $out['all'] = $months;
      }

      return $out;
    }

    return [];
  }


  function getOtherAccountsById($accounts_id = [], $type = false)
  {
    $typeStr = ($type) ? ('asys.type_id="' . $type . '" and') : '';
    $q       = 'select cl.firm as firm,a.number as a_number, a.*, c.*, c.number as client_number, cl.number as cl_number, cl.bank_sepa_referenz, ar.*,
        cl.email as sys_email 
        from ' . $this->table . ' a
        LEFT JOIN ' . $this->table_clients . ' c ON a.client_id = c.client_id 
        LEFT JOIN ' . $this->table_other_reservations . ' ar ON a.account_id = ar.account_id 
        LEFT JOIN ' . $this->table_cl . ' cl ON cl.client_id = c.sys_client_id 
        LEFT JOIN ' . Query::tableName('areas') . ' asys ON asys.area_id = a.area_id 
        WHERE ' . $typeStr . ' a.account_id IN (' . join(
        ',',
        $accounts_id
      ) . ')';

    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      foreach ($temp as $item) {
        $out[$item['account_id']]['a_number']                                       = $item['a_number'];
        $out[$item['account_id']]['firm']                                           = $item['firm'];
        $out[$item['account_id']]['date_start']                                     = $item['date_start'];
        $out[$item['account_id']]['a_date_start']                                   = $item['date_creation'] ?: $item['date_start'];
        $out[$item['account_id']]['date_finish']                                    = $item['date_finish'];
        $out[$item['account_id']]['date_creation']                                  = $item['date_creation'] ?: $item['date_start'];
        $out[$item['account_id']]['execution']                                      = $item['execution'];
        $out[$item['account_id']]['send']                                           = $item['send'];
        $out[$item['account_id']]['archives']                                       = $item['archives'];
        $out[$item['account_id']]['name']                                           = $item['name'];
        $out[$item['account_id']]['surname']                                        = $item['surname'];
        $out[$item['account_id']]['address']                                        = $item['address'];
        $out[$item['account_id']]['post_code']                                      = $item['post_code'];
        $out[$item['account_id']]['city']                                           = $item['city'];
        $out[$item['account_id']]['email']                                          = $item['email'];
        $out[$item['account_id']]['client_number']                                  = $item['client_number'];
        $out[$item['account_id']]['cl_number']                                      = $item['cl_number'];
        $out[$item['account_id']]['account_owner']                                  = $item['account_owner'];
        $out[$item['account_id']]['account_number']                                 = $item['account_number'];
        $out[$item['account_id']]['bank_index']                                     = $item['bank_index'];
        $out[$item['account_id']]['bank_name']                                      = $item['bank_name'];
        $out[$item['account_id']]['nds']                                            = $item['nds'];
        $out[$item['account_id']]['sepa_type']                                      = $item['sepa_type'];
        $out[$item['account_id']]['sepa_standart']                                  = $item['sepa_standart'];
        $out[$item['account_id']]['sepa_iban']                                      = $item['sepa_iban'];
        $out[$item['account_id']]['sepa_bic']                                       = $item['sepa_bic'];
        $out[$item['account_id']]['sepa_mndtid']                                    = $item['sepa_mndtid'];
        $out[$item['account_id']]['sepa_dtofsgntr']                                 = $item['sepa_dtofsgntr'];
        $out[$item['account_id']]['text_config']                                    = $item['text_config'];
        $out[$item['account_id']]['bank_sepa_referenz']                             = $item['bank_sepa_referenz'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['title'] = $item['title'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['count'] = $item['count'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['price'] = $item['price'];
        $out[$item['account_id']]['text_fields']                                    = JsonHelper::decode($item['text_fields'], true) ?: [];
        $out[$item['account_id']]['sys_email']                                      = $item['sys_email'];
      }

      return $out;
    } else {
      return false;
    }
  }

//################## СЧЕТА ПРЕДОПЛАТЫ ##########################
  //добавить
  function insertPrepaymentAccount(
    $sys_client_id,
    $name,
    $surname,
    $address,
    $post_code,
    $city,
    $email,
    $number,
    $date_start,
    $text_config,
    $nds,
    $sepa_data,
    $bank_data,
    $price,
    $price_info = false,
    $ticket_id = null,
  ) {
    if ($client_id = $this->insertAccountClient(
      $sys_client_id,
      $name,
      $surname,
      $address,
      $post_code,
      $city,
      $email,
      $number,
      $sepa_data,
      $bank_data
    )) {
      $q = 'insert into ' . $this->table . ' set
							client_id = :client_id,
							account_type = :account_type,
							number = :number,
							date_start = :date_start,
							date_creation = now(), 
							text_config = :text_config,'
        . 'price_info = :price_info ,'
        . (isset($sepa_data[4]) ? ' sepa_type= :sepa_type, ' : '')
        . (isset($sepa_data[5]) ? ' sepa_standart= :sepa_standart, ' : '')
        . ' ticket_id= :ticket_id, '
        . '	nds = "' . $nds . '"';

      $params = [
        'client_id'     => $client_id,
        'account_type'  => "3",
        'number'        => $this->generateAccountNumber(3),
        'date_start'    => $date_start,
        'text_config'   => $text_config,
        'price_info'    => ($price_info ?: ''),
        'sepa_type'     => (isset($sepa_data[4]) ? $sepa_data[4] : ''),
        'sepa_standart' => (isset($sepa_data[5]) ? $sepa_data[5] : ''),
        'ticket_id'     => ($ticket_id !== null ? $ticket_id : null),
      ];
      if (Query::sqlQuery($q, $params, false)) {
        $account_id = Query::getLastId();
        if ($this->insertPrepaymentReservationsAccount($account_id, $price)) {
          return $account_id;
        }
      }
    }

    return false;
  }

  function insertPrepaymentReservationsAccount($account_id, $price)
  {
    $q      = 'insert ' . $this->table_reservations_prepayment . ' set account_id= :account_id, price = :price';
    $params = ['account_id' => $account_id, 'price' => $price];
    if (Query::sqlQuery($q, $params, false)) {
      return true;
    }

    return false;
  }

  function closedPrepaymentAccount($account_id)
  {
    $q    = 'SELECT closed FROM ' . $this->table . ' where account_id = "' . $account_id . '"';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      $item = $temp[0];
      if ($item['closed'] === '0') {
        $q = 'update ' . $this->table . ' set closed= :closed where account_id = :account_id';
        if (Query::sqlQuery($q, ['closed' => '1', 'account_id' => $account_id], false)) {
          return true;
        }
      }
    }

    return false;
  }

  //--------------- ВЫВОД------------------------

  /**
   * Получает список предоплаченных аккаунтов.
   *
   * @param bool     $archives    Включать архивные аккаунты?
   * @param bool|int $year_select Фильтр по году (если указан)
   *
   * @return array|bool Массив данных или false при отсутствии результатов
   */
  function getPrepaymentAccounts($archives = false, $year_select = false, $month_select = false)
  {
    $q   = 'SELECT
             clsys.club_state as club_state,
             a.number as a_number,
             a.*,
             c.*,
             SUM(r.price) as sum
         FROM ' . $this->table . ' a
         LEFT JOIN ' . $this->table_clients . ' c ON a.client_id = c.client_id
         LEFT JOIN ' . $this->table_reservations_prepayment . ' r ON a.account_id = r.account_id
         LEFT JOIN ' . Query::tableName('clients') . ' clsys ON clsys.client_id = c.sys_client_id
         WHERE
             a.account_type = "3"
             AND a.archives = "' . (!$archives ? '0' : '1') . '"
             AND (a.ticket_id IS NULL OR a.ticket_id = 0)
             ' . ($year_select ? 'AND YEAR(a.date_start) = "' . $year_select . '"' : '')
      . ($month_select ? ' AND MONTH(a.date_start) = "' . $month_select . '"' : '')
      . '
         GROUP BY a.account_id';
    $out = Query::sqlQuery($q);

    return !empty($out) ? $out : false;
  }


  function getPrepaymentAccountsById($accounts_id = [])
  {
    $q = 'select cl.firm,a.number as a_number, a.*, c.*, c.number as client_number, cl.number as cl_number, cl.bank_sepa_referenz, ar.reservation_id, ar.price,
                cl.email as sys_email 
            from ' . $this->table . ' a  
            LEFT JOIN ' . $this->table_clients . ' c ON a.client_id = c.client_id 
            LEFT JOIN ' . $this->table_reservations_prepayment . ' ar ON a.account_id = ar.account_id 
            LEFT JOIN ' . $this->table_cl . ' cl ON cl.client_id = c.sys_client_id WHERE a.account_id IN (' . join(
        ',',
        $accounts_id
      ) . ')';

    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      foreach ($temp as $item) {
        $out[$item['account_id']]['a_number']                                       = $item['a_number'];
        $out[$item['account_id']]['firm']                                           = $item['firm'];
        $out[$item['account_id']]['date_start']                                     = $item['date_start'];
        $out[$item['account_id']]['a_date_start']                                   = $item['date_creation'] ?: $item['date_start'];
        $out[$item['account_id']]['date_finish']                                    = $item['date_finish'];
        $out[$item['account_id']]['date_creation']                                  = $item['date_creation'] ?: $item['date_start'];
        $out[$item['account_id']]['execution']                                      = $item['execution'];
        $out[$item['account_id']]['send']                                           = $item['send'];
        $out[$item['account_id']]['archives']                                       = $item['archives'];
        $out[$item['account_id']]['name']                                           = $item['name'];
        $out[$item['account_id']]['surname']                                        = $item['surname'];
        $out[$item['account_id']]['address']                                        = $item['address'];
        $out[$item['account_id']]['post_code']                                      = $item['post_code'];
        $out[$item['account_id']]['city']                                           = $item['city'];
        $out[$item['account_id']]['email']                                          = $item['email'];
        $out[$item['account_id']]['client_number']                                  = $item['client_number'];
        $out[$item['account_id']]['cl_number']                                      = $item['cl_number'];
        $out[$item['account_id']]['account_owner']                                  = $item['account_owner'];
        $out[$item['account_id']]['account_number']                                 = $item['account_number'];
        $out[$item['account_id']]['bank_index']                                     = $item['bank_index'];
        $out[$item['account_id']]['bank_name']                                      = $item['bank_name'];
        $out[$item['account_id']]['text_config']                                    = $item['text_config'];
        $out[$item['account_id']]['price_info']                                     = $item['price_info'];
        $out[$item['account_id']]['nds']                                            = $item['nds'];
        $out[$item['account_id']]['sepa_type']                                      = $item['sepa_type'];
        $out[$item['account_id']]['sepa_standart']                                  = $item['sepa_standart'];
        $out[$item['account_id']]['sepa_iban']                                      = $item['sepa_iban'];
        $out[$item['account_id']]['sepa_bic']                                       = $item['sepa_bic'];
        $out[$item['account_id']]['sepa_mndtid']                                    = $item['sepa_mndtid'];
        $out[$item['account_id']]['sepa_dtofsgntr']                                 = $item['sepa_dtofsgntr'];
        $out[$item['account_id']]['bank_sepa_referenz']                             = $item['bank_sepa_referenz'];
        $out[$item['account_id']]['reservations'][$item['reservation_id']]['price'] = $item['price'];
        $out[$item['account_id']]['text_fields']                                    = JsonHelper::decode($item['text_fields'], true) ?: [];
        $out[$item['account_id']]['sys_email']                                      = $item['sys_email'];
      }

      return $out;
    } else {
      return false;
    }
  }

  function getPrepaymentAccountsByClientId($client_id, $date_creation_start = null, $date_creation_finish = null)
  {
    $q    = 'select a.number as a_number, a.*, c.name, c.surname, c.address, c.post_code, c.city, c.email, c.number as client_number, cl.bank_sepa_referenz, SUM(ar.price) as sum from ' . $this->table . ' a
                                        LEFT JOIN ' . $this->table_clients . ' c ON a.client_id = c.client_id 
                                        LEFT JOIN ' . $this->table_reservations_prepayment . ' ar ON a.account_id = ar.account_id 
										LEFT JOIN ' . $this->table_cl . ' cl ON cl.client_id = c.sys_client_id 
                                        WHERE account_type="3" and c.sys_client_id = "' . $client_id . '"'
      . ($date_creation_start
        ? ' AND ((a.date_creation is null AND a.date_start >= "' . $date_creation_start . '") OR a.date_creation >= "' . $date_creation_start . '")'
        : '')
      . ($date_creation_finish
        ? ' AND ((a.date_creation is null AND a.date_start <= "' . $date_creation_finish . '") OR a.date_creation <= "' . $date_creation_finish . '")'
        : '')
      . ' GROUP BY a.account_id ';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      foreach ($temp as $item) {
        $out[$item['account_id']]['a_number']           = $item['a_number'];
        $out[$item['account_id']]['date_creation']      = $item['date_creation'] ?: $item['date_start'];
        $out[$item['account_id']]['date_start']         = $item['date_start'];
        $out[$item['account_id']]['date_finish']        = $item['date_finish'];
        $out[$item['account_id']]['execution']          = $item['execution'];
        $out[$item['account_id']]['send']               = $item['send'];
        $out[$item['account_id']]['archives']           = $item['archives'];
        $out[$item['account_id']]['name']               = $item['name'];
        $out[$item['account_id']]['surname']            = $item['surname'];
        $out[$item['account_id']]['address']            = $item['address'];
        $out[$item['account_id']]['post_code']          = $item['post_code'];
        $out[$item['account_id']]['city']               = $item['city'];
        $out[$item['account_id']]['email']              = $item['email'];
        $out[$item['account_id']]['client_number']      = $item['client_number'];
        $out[$item['account_id']]['account_owner']      = $item['account_owner'];
        $out[$item['account_id']]['account_number']     = $item['account_number'];
        $out[$item['account_id']]['bank_index']         = $item['bank_index'];
        $out[$item['account_id']]['bank_name']          = $item['bank_name'];
        $out[$item['account_id']]['text_config']        = $item['text_config'];
        $out[$item['account_id']]['price_info']         = $item['price_info'];
        $out[$item['account_id']]['bank_sepa_referenz'] = $item['bank_sepa_referenz'];
        $out[$item['account_id']]['nds']                = $item['nds'];
        $out[$item['account_id']]['sum']                = $item['sum'];
        $out[$item['account_id']]['type_code']          = match (true) {
          $item['ticket_id'] > 0                         => 'ticket_removed',
          str_starts_with($item['price_info'], 'paypal') => 'paypal_deposit',
          default                                        => 'invoice_deposit'
        };
      }

      return $out;
    }

    return false;
  }

  public function getReportPrepaymentAccountsByClientId($client_id)
  {
    $q
      = 'select a.number as a_number, a.*, c.name, c.surname, c.address, c.post_code, c.city, c.email, c.number as client_number, cl.bank_sepa_referenz, SUM(ar.price) as sum from ' . $this->table . ' a  
                                        LEFT JOIN ' . $this->table_clients . ' c ON a.client_id = c.client_id 
                                        LEFT JOIN ' . $this->table_reservations_prepayment . ' ar ON a.account_id = ar.account_id 
										LEFT JOIN ' . $this->table_cl . ' cl ON cl.client_id = c.sys_client_id 
                                        WHERE account_type="3" and c.sys_client_id = "' . $client_id . '" GROUP BY a.account_id DESC';

    $temp = Query::sqlQuery($q);

    if (!empty($temp)) {
      $out = [];
      foreach ($temp as $item) {
        $out[$item['account_id']]['a_number']           = $item['a_number'];
        $out[$item['account_id']]['date_start']         = $item['date_start'];
        $out[$item['account_id']]['date_finish']        = $item['date_finish'];
        $out[$item['account_id']]['execution']          = $item['execution'];
        $out[$item['account_id']]['send']               = $item['send'];
        $out[$item['account_id']]['archives']           = $item['archives'];
        $out[$item['account_id']]['name']               = $item['name'];
        $out[$item['account_id']]['surname']            = $item['surname'];
        $out[$item['account_id']]['adres']              = $item['adres'];
        $out[$item['account_id']]['post_code']          = $item['post_code'];
        $out[$item['account_id']]['city']               = $item['city'];
        $out[$item['account_id']]['email']              = $item['email'];
        $out[$item['account_id']]['client_number']      = $item['client_number'];
        $out[$item['account_id']]['account_owner']      = $item['account_owner'];
        $out[$item['account_id']]['account_number']     = $item['account_number'];
        $out[$item['account_id']]['bank_index']         = $item['bank_index'];
        $out[$item['account_id']]['bank_name']          = $item['bank_name'];
        $out[$item['account_id']]['text_config']        = $item['text_config'];
        $out[$item['account_id']]['price_info']         = $item['price_info'];
        $out[$item['account_id']]['bank_sepa_referenz'] = $item['bank_sepa_referenz'];
        $out[$item['account_id']]['nds']                = $item['nds'];
        $out[$item['account_id']]['sum']                = $item['sum'];
      }

      return $out;
    } else {
      return false;
    }
  }

  //список счетов для предоплаты
  // формат массив клиент_id => счет_id = статус (закрыт или открыт)
  // закрыт - все пипец ему присвоили уже сумму, можно только просмотреть
  // открыт - нужно закрыть :):)
  function getActivePrepaymentAccountsForClients()
  {
    $q    = 'SELECT c.sys_client_id AS client_id, a.account_id, a.closed FROM ' . $this->table . ' a
    INNER JOIN ' . $this->table_clients . ' c ON a.client_id = c.client_id' .
//    ' INNER JOIN ' . $this->table_reservations_prepayment . ' ap ON a.account_id = ap.account_id AND ABS(ap.price) > 0' .
      ' WHERE
    (a.execution = "1" OR a.send = "1") AND deleted = "0" AND a.account_type = "3" AND (a.ticket_id IS NULL OR a.ticket_id = 0)';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      foreach ($temp as $item) {
        $out[$item['client_id']][$item['account_id']] = $item['closed'];
      }

      return $out;
    }

    return false;
  }


//#################### ОБЩИЕ ФУНЦКЦИИ ДЛЯ СЧЕТОВ #########################
  function generateAccountNumber($account_type)
  {
    $q    = 'SELECT MAX(number) as number FROM ' . $this->table . ' WHERE account_type = "' . $account_type . '"';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      if ($item = $temp[0]) {
        return ((int)$item['number'] + 1);
      }
    }

    return false;
  }

  function markExecution($accounts_id = [])
  {
    $q = 'UPDATE ' . $this->table . ' SET execution = "1" WHERE account_id IN (' . join(
        ',',
        $accounts_id
      ) . ')';
    if (Query::sqlQuery($q, [], false)) {
      return true;
    }

    return false;
  }

  function markSend($accounts_id = [])
  {
    $q = 'UPDATE ' . $this->table . ' SET execution = "1", send = "1" WHERE account_id IN (' . join(
        ',',
        $accounts_id
      ) . ')';
    if (Query::sqlQuery($q, [], false)) {
      return true;
    }

    return false;
  }

  function sendInArchives($accounts_id = [])
  {
    $q = 'UPDATE ' . $this->table . ' SET archives = "1" WHERE account_id IN (' . join(
        ',',
        $accounts_id
      ) . ') AND execution="1"';
    if (Query::sqlQuery($q, [], false)) {
      return true;
    }

    return false;
  }

  public function getArrayListTableAccounts()
  {
    return [
      $this->table                           => 'accounts',
      $this->table_reservations              => 'accounts_reservations',
      $this->table_ticket_reservations       => 'accounts_reservations_tickets',
      $this->table_reservations_prepayment   => 'accounts_reservations_prepayment',
      $this->table_ticket_light_reservations => 'accounts_reservations_light_tickets',
      $this->table_other_reservations        => 'accounts_reservations_others',
      $this->table_other_reservations        => 'accounts_reservations_others',
    ];
  }

  function saveDeleteAccount($account_id)
  {
    $arrayTable = $this->getArrayListTableAccounts();
    $links      = [];
    foreach ($arrayTable as $key => $item) {
      $linkTemp     = Query::sqlQuery('Select  * FROM ' . $key . ' WHERE account_id = "' . $account_id . '"');
      $links[$item] = $linkTemp;
    }
    $links["date_create"]      = date('Y-m-d H:i:s');
    $links['accounts_clients'] = Query::sqlQuery(
      'Select * FROM ' . $this->table_clients . ' WHERE client_id = (Select  client_id FROM ' . $this->table . ' WHERE account_id = "' . $account_id . '")'
    );
    $result                    = [];
    foreach ($links as $key => $link) {
      if (is_array($link) && !empty($link)) {
        $arrTemp = [];
        foreach ($link as $item) {
          $arrTemp[] = $item;
        }
        $result[':' . $key] = json_encode($arrTemp);
      } else {
        $result[':' . $key] = '';
      }

      if (is_string($link)) {
        $result[':' . $key] = $link;
      }
    }
    $query = 'insert into ' . $this->table_delete . ' set account_id="' . $account_id . '", ';
    foreach ($result as $key => $val) {
      $query .= (str_replace(':', '', $key) . '=' . $key . ',');
    }

    return Query::sqlQuery(trim($query, ', '), $result, false);
  }

  function deleteAccount($account_id, bool $checkArchives = true)
  {
    Query::sqlQuery('UPDATE ' . $this->table . ' SET deleted = "1"' . (!$checkArchives ? ', archives="1"' : '') . ' WHERE account_id = "' . $account_id . '"'
      . ($checkArchives ? ' AND archives="1"' : ''), [], false);
    if (Query::sqlQuery(
      'insert into ' . $this->table_confirmation_delete . ' set delete_account_id = "' . $account_id . '", date="' . date(
        'Y-m-d'
      ) . '"',
      [],
      false
    )) {
      $this->saveDeleteAccount($account_id);

      return true;
    }

    return false;
  }


//################ ТЕКСТОВЫЕ БЛОКИ #########################
  function getTextBlocks()
  {
    return Query::sqlQuery('SELECT * FROM ' . $this->table_text_config . ' ORDER BY title');
  }

  function getTextBlock($config_id)
  {
    if ($config_id) {
      $temp = Query::sqlQuery('SELECT * FROM ' . $this->table_text_config . ' WHERE config_id="' . $config_id . '"');
      if (!empty($temp)) {
        return $temp[0];
      }
    }

    return false;
  }

  function insertTextBlock($title, $footer)
  {
    $q = 'INSERT ' . $this->table_text_config . ' SET title= :title, footer= :footer';

    return Query::sqlQuery($q, ['title' => $title, 'footer' => $footer], false);
  }

  function changeTextBlock($config_id, $title, $footer)
  {
    $q = 'UPDATE ' . $this->table_text_config . ' SET title= :title, footer= :footer  WHERE config_id= :config_id';

    return Query::sqlQuery($q, ['title' => $title, 'footer' => $footer, 'config_id' => $config_id], false);
  }

  function removeTextBlock($config_id)
  {
    $q = 'DELETE FROM ' . $this->table_text_config . ' WHERE config_id= :config_id';

    return Query::sqlQuery($q, ['config_id' => $config_id], false);
  }

//--------------- ВЫВОД------------------------
  function getClientData($client_id)
  {
    return Query::sqlQuery('SELECT * FROM ' . $this->table_clients . ' WHERE client_id = "' . $client_id . '"', [], true, ['onlyOne' => true]) ?? [];
  }


  function getConfirmationDeleteAccount($account_id)
  {
    return Query::sqlQuery(
      'select * from ' . $this->table_confirmation_delete . ' WHERE delete_account_id="' . $account_id . '"', [], true, ['onlyOne' => true]) ?? [];
  }


//################ ДОПОЛНИТЕЛЬНЫЕ СЧЕТА #########################

  /** Поменять данные клиента для счетов для текущего месяца
   *
   * @param $client_id
   *
   * @return bool
   */
  public function changeClientAccountsOnCurrentMonth($client_id)
  {
    $r           = new Engines();
    $client_data = [];
    $r->clients->getClientData($client_id, $client_data, true);

    $client_insert = [
      'sys_client_id' => $client_id,
      'name'          => $client_data['name'],
      'surname'       => $client_data['surname'],
      'address'       => $client_data['address'],
      'post_code'     => $client_data['post_code'],
      'city'          => $client_data['city'],
      'email'         => $client_data['email'],
      'number'        => $client_data['number'],
      'sepa_data'     => [
        $client_data['bank_iban'],
        $client_data['bank_bic'],
        $client_data['bank_sepa_referenz'],
        $client_data['bank_sepa_mandat'],
        $client_data['sepa_type'],
        $client_data['sepa_standart'],
      ],
      'bank_data'     => [
        $client_data['account_owner'],
        $client_data['account_number'],
        $client_data['bank_index'],
        $client_data['bank_name'],
      ],
    ];
    $query         = 'select a.* from ' . $this->table . ' a
              inner join  ' . $this->table_clients . ' ac on ac.client_id=a.client_id
               WHERE ac.sys_client_id="' . $client_id . '" and a.archives = "0" and a.deleted = "0"';
    $accounts      = Query::sqlQuery($query);
    if (!empty($accounts)) {
      if ($client_account_id = $this->insertAccountClient(
        $client_insert['sys_client_id'],
        $client_insert['name'],
        $client_insert['surname'],
        $client_insert['address'],
        $client_insert['post_code'],
        $client_insert['city'],
        $client_insert['email'],
        $client_insert['number'],
        $client_insert['sepa_data'],
        $client_insert['bank_data']
      )) {
        $query = 'update ' . $this->table . ' set client_id = :client_account_id where account_id = :account_id';
        foreach ($accounts as $account) {
          Query::sqlQuery($query, ['client_account_id' => $client_account_id, 'account_id' => $account['account_id']], false);
        }

        return true;
      }
    }

    return false;
  }

  public function getAllDataAccountByTypeAndId($account_id, $type)
  {
    $result['account_data'] = Query::sqlQuery('select * from ' . Query::tableName('accounts') . ' where account_id=' . $account_id, [], true,
      ['onlyOne' => true]);

    if ($result['account_data']['client_id'] != null) {
      $result['account_client_data'] = Query::sqlQuery('select * from ' . Query::tableName('accounts_clients') . ' where client_id=' . $result['account_data']['client_id'],
        [], true, ['onlyOne' => true]);
    }
    switch ($type) {
      case 2:
      case 6:
        $result['account_additional_data']['reservations_tickets'][]       = Query::sqlQuery('select * from ' . Query::tableName('accounts_reservations_tickets') . ' where account_id=' . $account_id);
        $result['account_additional_data']['reservations_light_tickets'][] = Query::sqlQuery('select * from ' . Query::tableName('accounts_reservations_light_tickets') . ' where account_id=' . $account_id);
        break;
    }

    return $result;
  }

  private function deleteAllDataAccountAfterAddArchive($account_id, $type)
  {
    switch ($type) {
      case 2:
      case 6:
        Query::sqlQuery('delete from ' . Query::tableName('accounts_reservations_tickets') . ' where account_id=' . $account_id, [], false);
        Query::sqlQuery('delete from ' . Query::tableName('accounts_reservations_light_tickets') . ' where account_id=' . $account_id, [], false);
        break;
    }
    Query::sqlQuery('delete from ' . Query::tableName('accounts') . ' where account_id=' . $account_id, [], false);
  }

  public function addToArchiveAccounts($accounts, $type)
  {
    foreach ($accounts as $account) {
      $result = $this->getAllDataAccountByTypeAndId($account, $type);
      Query::sqlQuery('insert into ' . Query::tableName('accounts_archive') . ' (`old_account_id`, date_delete, account_data, account_client_data, account_additional_data) values ("' . $account . '","' . date('Y-m-d H:i:s') . '", \'' . json_encode($result['account_data']) . '\', \'' . json_encode($result['account_client_data']) . '\', \'' . json_encode($result['account_additional_data']) . '\')',
        [], false);
      $this->deleteAllDataAccountAfterAddArchive($account, $type);
    }

    return true;
  }

  public function getNumberByAccountId($account_id): string
  {
    return Query::sqlQuery('select number from ' . Query::tableName('accounts') . ' where account_id=:account_id', [':account_id' => $account_id],
      true,
      ['onlyOne' => true])['number'] ?? '';
  }


  public function checkPrepaymentAccountByReference(string $reference)
  {
    $params = ["paypal|{$reference}%"];

    return Query::sqlQuery(
      'SELECT a.account_id, arp.price FROM ' . $this->table . ' AS a' .
      ' INNER JOIN ' . $this->table_reservations_prepayment . ' AS arp ON a.account_id = arp.account_id' .
      ' WHERE a.account_type = "3" AND a.price_info LIKE ? AND a.deleted = "0"',
      $params,
      true,
      ['onlyOne' => true]
    );
  }

  public function closedPrepaymentAccountByReference(string $reference): bool
  {
    $params = [
      "paypal|{$reference}%",
    ];
    if (Query::sqlQuery('update ' . $this->table . ' set closed="1" where account_type = "3" AND price_info LIKE ?', $params, false)) {
      return true;
    }

    return false;
  }

  public function checkStatusPaymentPrepaymentAccountByReference(string $reference): ?string
  {
    $params = ["paypal|{$reference}%"];
    $query  = 'SELECT account_id, price_info, date_creation, execution, send, archives, deleted, closed
       FROM ' . $this->table . ' WHERE account_type = "3" AND price_info LIKE ?';
    [$accountId, $priceInfo, $dateCreation, $execution, $send, $archives, $deleted, $closed] = Query::sqlQuery($query, $params, true,
      ['onlyOne' => true, 'style' => PDO::FETCH_NUM]);
    if ($accountId && $priceInfo) {
      return match (true) {
        !$deleted && $closed  => 'OK',    // оплата прошла
        !$deleted && !$closed => 'WAIT',  // счет есть, но не закрыт
        $deleted && $archives => 'FALSE', // счет удален - оплата с ошибкой
        default               => null     //счета еще нет - ожидание оплаты
      };
    }

    return null;
  }

  function getPrepaymentAccountPriceInfo($account_id)
  {
    $row = Query::sqlQuery(
      'SELECT price_info FROM ' . $this->table . ' WHERE account_id = :account_id AND account_type = "3"',
      ['account_id' => (int)$account_id],
      true,
      ['onlyOne' => true]
    );

    return $row['price_info'] ?? null;
  }

  /**
   * Обновить `price_info` у предоплатного счёта (например, дописать TRANSACTIONID после DoExpressCheckoutPayment).
   */
  function updatePrepaymentAccountPriceInfo($account_id, $price_info)
  {
    return (bool)Query::sqlQuery(
      'UPDATE ' . $this->table . ' SET price_info = :price_info WHERE account_id = :account_id AND account_type = "3"',
      ['price_info' => (string)$price_info, 'account_id' => (int)$account_id],
      false
    );
  }
}
