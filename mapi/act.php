<?php

namespace AC\mapi;

use AC\core\system\db\CompareDataDB;
use AC\core\system\db\CompareDB;
use AC\core\system\db\DB;
use AC\core\system\db\GenerateQueryDB;
use AC\core\system\helpers\TextHelper;
use AC\mapi\act\repositories\WorkWith;
use Service;

define("AC\mapi\pathRepositories", pathAs(__DIR__ . '/act/repositories/'));
error_reporting(E_ALL);
mb_internal_encoding("UTF-8");
define('DB_ROOT', true);
define('USE_FULL_STRUCTURE', true);

class act
{
  public function run()
  {
    $action = Service::request()->_get('action');
    switch ($action) {
      case 'compareDBRun':
        $this->compareDB(true);
        break;
      default:
        if ($action && method_exists($this, $action)) {
          $this->$action();
        } elseif ($action && $this->workWith()?->hasRepository($action)) {
          if ($repository = $this->workWith()?->getRepository($action)) {
            if (Service::request()->checkGet('run')) {
              $repository->run();;
            } elseif (Service::request()->checkGet('view')) {
              $repository->view();
            } elseif (Service::request()->checkGet('asCsv')) {
              $repository->asCsv(Service::request()->_get('asCsv', 'view'));
            } elseif (Service::request()->checkGet('asJson')) {
              $repository->asJson(Service::request()->_get('asJson', 'view'));
            }
          }
        } else {
          $this->compareDB();
        }
        break;
    }

    return '';
  }

  private function workWith(): WorkWith
  {
    return new WorkWith();
  }

  protected function runA()
  {
    $db         = DB::instance();
    $clients    = [];
    $clientData = $db->fulfillRequestToDataBase('select cd.*, c.surname, c.email from ' . $db->generateTableName('client_data') . ' cd'
      . ' left join ' . $db->generateTableName('clients') . ' c on c.client_id=cd.client_id'
      . ' where cd.name="id" or cd.name="client_id" or cd.name="typeBlock" or cd.name="name" or cd.name="value"', [], true);
    foreach ($clientData as $clientDatum) {
      $clients[$clientDatum['client_id']]['clientData'][] = $clientDatum['id'];
    }
    $accounts_clients_data = $db->fulfillRequestToDataBase(
      'select ac.client_id, ac.surname, ac.email, a.account_id, c.client_id as sys_client_id from ' . $db->generateTableName('accounts_clients') . ' ac'
      . ' left join ' . $db->generateTableName('accounts') . ' a on ac.client_id = a.client_id'
      . ' inner join ' . $db->generateTableName('clients') . ' c on ac.surname = c.surname and ac.email = c.email'
      . ' where sys_client_id=0', [], true);

    foreach ($accounts_clients_data as $accounts_clients_datum) {
//      $clients[$accounts_clients_datum['sys_client_id']]['accounts'][] = [$accounts_clients_datum['client_id'], $accounts_clients_datum['account_id']];
      $clients[$accounts_clients_datum['sys_client_id']]['accounts']['clients'][]  = $accounts_clients_datum['client_id'];
      $clients[$accounts_clients_datum['sys_client_id']]['accounts']['accounts'][] = $accounts_clients_datum['account_id'];
    }

    if (Service::request()->checkGet('run')) {
      foreach ($clients as $client) {
        if (isset($client['clientData']) && in_array(Service::request()->_get('run'), ['all', 'clientData'])) {
          $db->fulfillRequestToDataBase('delete from ' . $db->generateTableName('client_data') . ' where id in (' . implode(', ',
              $client['clientData']) . ')');
        }
        if (isset($client['accounts']) && in_array(Service::request()->_get('run'), ['all', 'accounts'])) {
          $db->fulfillRequestToDataBase('delete from ' . $db->generateTableName('accounts') . ' where account_id in (' . implode(', ',
              $client['accounts']['accounts']) . ')');
          $db->fulfillRequestToDataBase('delete from ' . $db->generateTableName('accounts_clients') . ' where client_id in (' . implode(', ',
              $client['accounts']['clients']) . ')');
        }
      }
    }
    if (Service::request()->checkGet('view')) {
//      Debug($clients);
    }
  }

  protected function checkFakeAccount()
  {
    $base = DB::instance('base');
    $res  = [];
    foreach ($base->getDbo()->showDataBase() as $dbName) {
      if (!in_array($dbName, ['db_test', 'db_old'])) {
        foreach ($base->getDbo()->getFullTableName('config', $dbName, true) as $tableName) {
          $tableName = '`' . $dbName . '`.`' . $tableName . '`';
          if ($base->getDbo()->query('SHOW COLUMNS FROM ' . $tableName . ' LIKE \'alias\'')
            && $base->getDbo()->query('SHOW COLUMNS FROM ' . $tableName . ' LIKE \'value\'')
            && $r = $base->getDbo()->query('select * from ' . $tableName . ' where (alias ="name_bank" and value="STRYZHAK DENYS") or (alias="sepa_name"  and value="STRYZHAK DENYS") or value="GB41CLJU00997188462118" or value="CLJUGB21"')) {
            $res[$tableName] = $r;
          }
        }
      }
    }
    Debug($res);

  }

  protected function webIO()
  {
    $light = Service::engines()->light;

//    http://tavs.dyndns.org:86/output7?PW=&
//  	http://tavs.dyndns.org:86/output?PW=&

    $st = $light->sendWebIO('http://tavs.dyndns.org:86');
//   $st = explode(';', $st);
//    Debug()::dvD($st);

    for ($i = 0; $i < 12; $i++) {
      $state = false;
      if ((hexdec($st[1]) & pow(2, $i)) == pow(2, $i)) {
        $state = true;
      }

//      Debug()::dvD($i, $state);
    }

  }

  protected function checkBin()
  {
    $db          = DB::instance();
    $dbTest      = DB::instance('test', true, ['dbname' => 'test', 'prefixTable' => DB_TABLE_PREFIX,]);
    $clientsTest = $dbTest->getFullDataInTable('clients', 'client_id');
    $clients     = $db->getFullDataInTable('clients', 'client_id');
    $arr1        = $arr2 = $arr3 = $arr4 = [];

    foreach ($clients as $idClient => $client) {
      if (in_array($idClient, array_keys($clientsTest))) {
        if ($clientsTest[$idClient]->bank_bic != $client->bank_bic && !empty($clientsTest[$idClient]->bank_bic) && strlen($clientsTest[$idClient]->bank_bic) == 11) {
          $arr1[$idClient] = [
            'login'    => $client->login,
            'name'     => $client->name,
            'surname'  => $client->surname,
            'bic'      => $client->bank_bic,
            'bicLocal' => $clientsTest[$idClient]->bank_bic,
          ];
        } elseif (empty($client->bank_bic)) {
          $arr2[$idClient] = [
            'login'    => $client->login,
            'name'     => $client->name,
            'surname'  => $client->surname,
            'bic'      => $client->bank_bic,
            'bicLocal' => $clientsTest[$idClient]->bank_bic,
          ];
        } elseif (!empty($client->bank_bic) && strlen($client->bank_bic) < 11) {
          $arr3[$idClient] = [
            'login'    => $client->login,
            'name'     => $client->name,
            'surname'  => $client->surname,
            'bic'      => $client->bank_bic,
            'bicLocal' => $clientsTest[$idClient]->bank_bic,
          ];
        } else {
          $arr4[$idClient] = [
            'login'    => $client->login,
            'name'     => $client->name,
            'surname'  => $client->surname,
            'bic'      => $client->bank_bic,
            'bicLocal' => $clientsTest[$idClient]->bank_bic,
          ];
        }
        unset($clients[$idClient]);
        unset($clientsTest[$idClient]);
      } else {
        if (empty($client->bank_bic)) {
          $arr2[$idClient] = [
            'login'    => $client->login,
            'name'     => $client->name,
            'surname'  => $client->surname,
            'bic'      => $client->bank_bic,
            'bicLocal' => $clientsTest[$idClient]->bank_bic ?? '',
          ];
        } elseif (!empty($client->bank_bic) && strlen($client->bank_bic) < 11) {
          $arr3[$idClient] = [
            'login'    => $client->login,
            'name'     => $client->name,
            'surname'  => $client->surname,
            'bic'      => $client->bank_bic,
            'bicLocal' => $clientsTest[$idClient]->bank_bic ?? '',
          ];
        } else {
          $arr4[$idClient] = [
            'login'    => $client->login,
            'name'     => $client->name,
            'surname'  => $client->surname,
            'bic'      => $client->bank_bic,
            'bicLocal' => $clientsTest[$idClient]->bank_bic ?? '',
          ];
        }
        unset($clients[$idClient]);
      }
    }

//    Debug(count($arr1), count($arr2), count($arr3), count($arr4));
//    Dbg($arr1, $arr2, $arr3, $arr4);
    $fields = ['login' => 'Benutzername', 'name' => 'Vorname', 'surname' => 'Familienname', 'bic' => 'BIC', 'bicLocal' => 'BIC Local'];
//    echo Debug()::eTv($arr2, $fields);
//    echo Debug()::eTv($arr3, $fields);

    if (Service::request()->_get('update')) {
      $query = '';
      foreach ($arr1 as $clientId => $item) {
        $query .= 'update ' . $db->generateTableName('clients') . ' set bank_bic = "' . $item['bicLocal'] . '" where client_id = ' . $clientId . ";\n</br>";
//        $db->fulfillRequestToDataBase($query);
      }
      echo $query;
    }
//    $db = DB::instance();
//    for ($area_id=1;$area_id<=28;$area_id++) {
//      for($weekday = 0;$weekday < 7; $weekday++) {
//        $start = "08:00";
//        $finish = "19:00";
//        if($area_id > 23) {
//          $finish = "20:00";
//        }
//        $query = 'replace '.$db->generateTableName('areas_timetables').' set area_id = "'.$area_id.'", weekday = "'.$weekday.'", start= "'.$start.'", finish= "'.$finish.'"';
//        $db->fulfillRequestToDataBase($query);
//      }
//    }
  }

  /**
   *  Создаем новую базу данных
   */
  protected function createDB(): void
  {
    $dbname       = Service::request()->_('dbname');
    $prefixTable  = Service::request()->_('prefixTable');
    $prefixDBName = Service::request()->_('prefixDBName', 'at');

    /**
     * @var DB $base
     */
    $base        = DB::instance('base', true);
    $newBaseName = $prefixDBName . '_' . $dbname;
    if (!$dbname && !$prefixTable) {
      $config      = config('DB')->getConfig('site');
      $newBaseName = $config['dbname'];
      $prefixTable = $config['prefixTable'];
    }
    if ($base->createDB($newBaseName)) {
      $base->setGrantByDBName($newBaseName, null);
      $newDB = DB::instance(
        'newDB',
        true,
        [
          'dbname'      => $newBaseName,
          'prefixTable' => $prefixTable ?? $newBaseName . '_',
        ]
      );
      if ((bool)Service::request()->_('copyDB', 1)) {
        $this->copyDB($base, $newDB, [], $query, true);
      }
    }
  }

  protected function copyDB(DB $sourceDB = null, DB $receiverDB = null, $config = [], &$query = [], $run = false): void
  {
    $sourceDB   = $sourceDB ?? DB::instance('base', true);
    $receiverDB = $receiverDB ?? DB::instance('site', true);
    $compare    = CompareDB::compareTwoDB($receiverDB, $sourceDB, $config);
    $gQDBS      = new GenerateQueryDB($receiverDB);
    $query      = [];
    if (!empty($compare)) {
      foreach ($compare as $tableName => $compareTable) {
        if ($res = $gQDBS->renderCompareOnTableName($tableName, $compareTable)) {
          $query[] = $res;
        }
        /** Если создается таблица и есть данные на базе в этой таблице, производиться копирование таблицы */
        if (isset($compareTable->create_table) && $data = $sourceDB->getDataTableByFieldsAndValues($tableName)) {
          if (!empty($data['values'])) {
            $query[] = $gQDBS->generateInsertData($tableName, $data);
          }
        }
      }
    }
    if ($run) {
      $receiverDB->executingAllQuery($query);
      $receiverDB->instanceStructure();
      $query = [];
    }
  }

  /**  Сравнить базу с базовой и найти отличия выводятся на экран
   *  возможности конфига:
   *  'dropColumn' - удалить столбцы из таблицы рабочей базы
   *    может принимать несколько вариантов :
   *      (bool) - проходиться по всем таблицам и удаляет все поля, которых нет в базовой версии
   *      (array) - одномерный массив с названиями таблиц без префиксов проходиться только по этим таблицам
   *      (array[array]) - двумерный массив с названиями таблиц без префиксов и внутри перечисленны поля, которые нужно удалить в данной таблице
   *     все это работает если есть несоответствие столбцов. Если есть столбец в базе, то ни чего не происходит
   * Дополнительные активации
   * run - запустить
   * view - просмотр
   * users - запросы обновление и добавление дефолтных админов
   */
  protected function compareDB($run = false): void
  {
    $query              = [];
    $resTables          = ['reservations', 'reservations_tmp_paypal'];
    $base               = DB::instance('base', true, [
      'dbname'      => Service::request()->_get('dbBaseName') ?? 'at_base_active_court',
      'prefixTable' => Service::request()->_get('dbBaseName') ? Service::request()->_get('dbBaseName') . '_' : 'at_base_active_court_',
    ]);
    $site               = DB::instance();
    $statusReservations = [];
    foreach (['reservations', 'reservations_tmp_paypal'] as $table) {
      foreach (['payment_state', 'status'] as $field) {
        $statusReservations[$table][$field] = $site->getDbo()->checkField($field, $table);
      }
    }
    $gQDBS = new GenerateQueryDB($site);
    $this->copyDB(
      $base,
      $site,
      [
        'dropColumn'    => ['config'],
        'replaceColumn' => [
          'all' => [
            'door_code' => ['doorcodes'],
            'address'   => ['adres', 'adress'],
          ],
        ],
      ],
      $query,
      $run
    );
    /** Проверка по выбранным таблицам, идет сравнение данных,
     *  по их PrimaryKeys и если на base есть строки которых нет на данном сайте
     *  то выдаются запросы для их добавления, на примере таблицы config
     */
    foreach (['config', 'config_text', 'registration_fields', 'letters_templates'] as $tableName) {
      if ($tableName != 'config') {
        $compareData = CompareDataDB::compareTableSetBase($tableName, $site, $base);
      } else {
        $checkPrimaryKeys = 0;
        foreach (array_keys($base->getPrimaryKeys($tableName)) as $primaryKey) {
          if (in_array($primaryKey, $site->getFields($tableName))) {
            $checkPrimaryKeys++;
          }
        }
        if ($checkPrimaryKeys == count(array_keys($base->getPrimaryKeys($tableName)))) {
          $compareData = CompareDataDB::compareTableSetBase($tableName, $site, $base);
        } else {
          $query[]                         = $gQDBS->generateTruncateTable($tableName);
          $data                            = $site->getFullDataInTable($tableName, 'alias');
          $compareData['insert']['fields'] = $base->getFields($tableName);
          foreach ($base->getFullDataInTable($tableName) as $row) {
            if (isset($data[$row->alias])) {
              $row->value = $data[$row->alias]->value;
            }
            if (isset($data[$row->alias . '_' . $row->type])) {
              $row->value = $data[$row->alias . '_' . $row->type]->value;
            }
            $compareData['insert']['values'][] = $row;
          }
        }
      }
      if (isset($compareData['insert']) && !empty($compareData['insert']['values'])) {
        $query[] = $gQDBS->generateInsertData($tableName, $compareData['insert']);
      }
    }

    /** Соотносим статусы бронировании */
//    foreach ($statusReservations as $table => $fields) {
//      if ($fields['payment_state'] && !$fields['status']) {
//        $query[] = 'UPDATE ' . $site->generateTableName($table) . ' SET `status` = `payment_state` WHERE `payment_state` != `status`;';
//      }
//      if ($fields['status'] && !$fields['payment_state']) {
//        $query[] = 'UPDATE ' . $site->generateTableName($table) . ' SET `payment_state` = status` WHERE `status` != `payment_state`;';
//      }
//    }

    /**
     * Форматируем шаблоны писем в html вид
     *
     */
    if (Service::request()->checkGet('letter') && isset($site->getStructure()['letters_templates'])) {
      $primaryKeys = array_keys($site->getPrimaryKeys('letters_templates'));
      foreach ($site->getFullDataInTable('letters_templates') as $letter_template) {
        $content = TextHelper::autoParagraph($letter_template->content);
        if (trim($letter_template->content) != trim($content)) {
          $where = [];
          foreach ($primaryKeys as $alias) {
            $where[$alias] = $letter_template->{$alias};
          }
          $query[] = $gQDBS->generateUpdateData('letters_templates', ['where' => $where, 'set' => ['content' => $content]]);
        }
      }
    }
    if (Service::request()->_get('users')) {
      $this->workWith()?->getRepository('defaultUsers')?->query($query);
    }
    $this->workWith()?->getRepository('holidays', ['process' => false])?->updateSundayPrices()?->query($query);

    if ($run || Service::request()->checkGet('run')) {
      $site->executingAllQuery($query);
    } elseif (Service::request()->checkGet('view')) {
      Debug()::dE($query);
    }
  }

  protected function renamePrefix(): void
  {
    $db = DB::instance();
    if ($prefix = Service::request()->_get('prefix', false)) {
      foreach ($db->getNameTables() as $nameTable) {

        $db->getDbo()->query(
          'RENAME TABLE ' . $db->generateTableName($nameTable) . ' to ' . $db->generateTableName(
            $nameTable,
            $prefix
          ) . ';'
        );
      }
    }
  }

  /**
   * Изменить пароль adminvs для всех баз данных
   */
  protected function changePasswordAdminVs(): void
  {
    $base = DB::instance('base');
    foreach ($base->getDbo()->showDataBase() as $dbName) {
      if (str_starts_with($dbName, 'at_')) {
        foreach ($base->getDbo()->getFullTableName('users', $dbName) as $tableName) {
          if (($col = $base->getDbo()->query('SHOW COLUMNS FROM `' . $dbName . '`.`' . $tableName . '` LIKE \'user_id\'')) && count($col)) {
            $r = $base->getDbo()->query('select user_id from `' . $dbName . '`.`' . $tableName . '` where login=\'ADVSMaster!\'');
            if (is_array($r) && !count($r)) {
              $base->getDbo()->query("ALTER TABLE `" . $dbName . "`.`" . $tableName . "` CHANGE `rights` `rights` enum('-100','-1','1','2','3') DEFAULT '1' NOT NULL, ADD COLUMN `language` varchar(4) DEFAULT 'de' NULL AFTER `default`;",
                [], false);
//              Debug("ALTER TABLE `" . $dbName . "`.`" . $tableName . "` CHANGE `rights` `rights` enum('-100','-1','1','2','3') DEFAULT '1' NOT NULL, ADD COLUMN `language` varchar(4) DEFAULT 'de' NULL AFTER `default`;");
              $query = 'INSERT INTO `' . $dbName . '`.`' . $tableName . '` (`rights`, `login`, `password`, `name`, `code`, `default`) VALUES (\'-1\', \'ADVSMaster!\', \'' . md5('sA644gfjH!&5D5h3?') . '\', \'AdminVS\', \'fm\', \'1\')';
              if ($base->getDbo()->query($query, [], false)) {
                $color = 'green';
              } else {
                $color = 'red';
              }
              echo('<p style="color: white;background-color: ' . $color . '">Base: ' . $dbName . ' Table: ' . $tableName . '<br> Query: ' . $query . '</p>');
            }
          }
        }
      }

    }

//    if (Service::request()->checkGet('login') && Service::request()->checkGet(
//        'password'
//      )) {
//      $base     = DB::instance('base');
//      $login    = Service::request()->_get('login');
//      $password = md5(Service::request()->_get('password'));
//      foreach ($base->getDbo()->showDataBase() as $dbName) {
//        foreach ($base->getDbo()->getFullTableName('users', $dbName) as $tableName) {
//          $query = 'update `' . $dbName . '`.`' . $tableName . '` set password=\'' . $password . '\' where login=\'' . $login . '\'';
//          if ($base->getDbo()->query($query, [], false)) {
//            $color = 'green';
//          } else {
//            $color = 'red';
//          }
//          echo('<p style="color: white;background-color: ' . $color . '">Password change in Base: ' . $dbName . ' Table: ' . $tableName . '<br> Query: ' . $query . '</p>');
//        }
//      }
//    }
  }

  /**
   *  Задать area_id всем счетам
   */
  protected function setAccountAreaId()
  {
    if ($area_id = Service::request()->_get('area_id', false)) {
      $site  = DB::instance();
      $gQDBS = new GenerateQueryDB($site);
      $query = $gQDBS->generateUpdateData('accounts', ['set' => ['area_id' => $area_id], 'where' => ['area_id' => 'is null']]);
      $site->fulfillRequestToDataBase($query);
    }
  }

  /**
   *  Переработать счета по открытым закрытым
   */

  protected function reworkAccounts()
  {
    $siteDB = DB::instance()->getDbo();
    // счета на абонименты
    $accounts = $siteDB->query(
      'select a.account_id, ar.area_id from ' . $siteDB->generateTableName('accounts') . ' a
  left join ' . $siteDB->generateTableName('accounts_reservations_tickets') . ' art on art.account_id = a.account_id
  left join ' . $siteDB->generateTableName('areas') . ' ar on ar.title = art.area
   where a.ticket_id is not null and a.area_id is null and a.account_type="1"'
    );
    foreach ($accounts as $account) {
      $siteDB->query(
        'update ' . $siteDB->generateTableName('accounts') . ' set area_id = ' . $account['area_id'] . ' where account_id = ' . $account['account_id']
      );
    }
//    Debug()::dvD('tickets ok');

    $reserv  = $siteDB->query(
      'select a.account_id, ar.sys_reservation_id, are.area_id, are.type_id, ar.type, ar.price from ' . $siteDB->generateTableName('accounts') . ' a
   join ' . $siteDB->generateTableName('accounts_reservations') . ' ar on ar.account_id = a.account_id
   join ' . $siteDB->generateTableName('areas') . ' are on are.title = ar.area
   where a.area_id is null and a.account_type = "0"'
    );
    $reserv1 = $reserv2 = [];

    foreach ($reserv as $item) {
      if ($item['type'] == 'Außenplätze' && $item['price'] == 0) {
        continue;
      }
      if ($item['type_id'] == null && in_array($item['area_id'], [4, 5])) {
        $item['type_id'] = 2;
      }
      $item['type_id'] = (empty($item['type_id']) ? ($item['type'] == 'Außenplätze' ? 2 : 1) : $item['type_id']);
      if (empty($item['area_id'])) {
        $item['area_id'] = config('reservations')->isOpenType((int)$item['type_id']) ? 2 : (config('reservations')->isCloseType((int)$item['type_id']) ? 1 : null);
      }
      $reserv2[$item['account_id']]['count'][$item['type_id']] ??= 0;
      $reserv2[$item['account_id']]['areas'][$item['type_id']] ??= $item['area_id'];
      if (!isset($reserv1[$item['account_id']]) || !in_array($item['type_id'], $reserv1[$item['account_id']])) {
        $reserv1[$item['account_id']][] = $item['type_id'];
      }
      $reserv2[$item['account_id']][] = $item;

      ++$reserv2[$item['account_id']]['count'][$item['type_id']];
    }
    foreach ($reserv1 as $account_id => $items) {
      $area_id = null;
      if (count($items) == 1) {
        $area_id = $reserv2[$account_id][0]['area_id'];
      } else {
        if ($reserv2[$account_id]['count'][1] >= $reserv2[$account_id]['count'][1]) {
          $area_id = $reserv2[$account_id]['areas'][1];
        } else {
          $area_id = $reserv2[$account_id]['areas'][2];
        }
      }
      $siteDB->query(
        'update ' . $siteDB->generateTableName(
          'accounts'
        ) . ' set area_id = ' . $area_id . ' where account_id = ' . $account_id
      );
    }
//    Debug()::dvD($reserv2);

    $siteDB->query('update ' . $siteDB->generateTableName('accounts') . ' a 
    set a.nds = (select cn.rate from ' . $siteDB->generateTableName('config_nds') . ' cn 
    left join ' . $siteDB->generateTableName('accounts_clients') . 'ac on ac.client_id = a.client_id
    left join ' . $siteDB->generateTableName('clients') . 'c on c.client_id = ac.sys_client_id
    where IF((c.nds IS NULL OR c.nds=0), cn.set_default="1", cn.nds_id = c.nds)) where a.nds = 0 and a.account_type = "1"', [], false);
  }

  protected function phpInfo()
  {
    phpinfo();
    die;
  }

}

echo (new act())->run();

//die();


//if (Service::request()->_get('action') == 'setAccountType' && Service::request()->_get('area_id')) {
//  $accounts = $site->getDbo()->query(
//    'select account_id from ' . $site->getDbo()->getPrefixTable() . 'accounts where year(date_start) = "2021" and account_type = "0"'
//  );
//  foreach ($accounts as $account_id) {
//    $reser = $site->getDbo()->query(
//      'select reservation_id, type from ' . $site->getDbo()->getPrefixTable() . 'accounts_reservations where account_id=' . $account_id['account_id']
//    );
//    $check = 1;
//    foreach ($reser as $r) {
//      if (strpos($r['type'], 'Freiplätze') === false) {
//        $check = 0;
//      }
//    }
//
//    if ($check === 1) {
//      $site->getDbo()->query(
//        'update ' . $site->getDbo()->getPrefixTable() . 'accounts set area_id=' . Service::request()->_get(
//          'area_id'
//        ) . ' where account_id=' . $account_id['account_id']
//      );
//    }
//  }
//}
//
//if (Service::request()->_get('action') == 'deleteTableByPrefix' && Service::request()->_get('prefix')) {
//  Debug()::dvD($site->getStructure());
////  $query = $gQDBS->generateDeleteTable(null, Service::request()->_get('prefix'));
////  $site->fulfillRequestToDataBase($query);
//}
//$siteDB = $site->getDbo();
//
//
//if (Service::request()->_get('action') == 'clientAllowedSport') {
//  $clients = $siteDB->query('select client_id, area_type from ' . $siteDB->generateTableName('clients'));
//
//  foreach ($clients as $client) {
//    $unavailable_sports = '';
//    switch ($client['area_type']) {
//      case '1';
//        $unavailable_sports = '2_1';
//        break;
//      case '2':
//        $unavailable_sports = '1_1';;
//        break;
//      default:
//    }
//    Debug()::dvD(
//      $siteDB->query(
//        'update ' . $siteDB->generateTableName(
//          'clients'
//        ) . ' set unavailable_sports = "' . $unavailable_sports . '" where client_id=' . $client['client_id'],
//        [],
//        false
//      )
//    );
//  }
//}
//
///**
// *  Переработать счета по открытым закрытым
// */
//if (Service::request()->_get('action') == 'renderAccounts') {
//  // счета на абонименты
//
//  $accounts = $siteDB->query(
//    'select a.account_id, t.area_id from ' . $siteDB->generateTableName('accounts') . ' a
//  left join ' . $siteDB->generateTableName('tickets') . ' t on t.ticket_id = a.ticket_id
//   where a.ticket_id is not null and a.area_id is null'
//  );
//  foreach ($accounts as $account) {
//    $siteDB->query(
//      'update ' . $siteDB->generateTableName('accounts') . ' set area_id = ' . $account['area_id'] . ' where account_id = ' . $account['account_id']
//    );
//  }
//
//  $reserv  = $siteDB->query(
//    'select a.account_id, ar.sys_reservation_id, r.area_id, are.type_id, ar.type, ar.price from ' . $siteDB->generateTableName('accounts') . ' a
//   join ' . $siteDB->generateTableName('accounts_reservations') . ' ar on ar.account_id = a.account_id
//   join ' . $siteDB->generateTableName('reservations') . ' r on r.reservation_id = ar.sys_reservation_id
//   join ' . $siteDB->generateTableName('areas') . ' are on are.area_id = r.area_id
//   where a.area_id is null '
//  );
//  $reserv1 = $reserv2 = [];
//
//  foreach ($reserv as $item) {
////    if ($item['type'] == 'Freiplätze' && $item['price'] == 0) {
////      continue;
////    }
//    if ($item['type_id'] == null && in_array($item['area_id'], [7, 8])) {
//      $item['type_id'] = 2;
//    }
//    $item['type_id'] = (empty($item['type_id']) ? ($item['type'] == 'Freiplätze' ? 2 : 1) : $item['type_id']);
//    if (empty($item['area_id'])) {
//      $item['area_id'] = $item['type_id'] == 2 ? 2 : ($item['type_id'] == 1 ? 1 : null);
//    }
//
//    if (!isset($reserv1[$item['account_id']]) || !in_array($item['type_id'], $reserv1[$item['account_id']])) {
//      $reserv1[$item['account_id']][] = $item['type_id'];
//    }
//    $reserv2[$item['account_id']][] = $item;
//  }
//
//  foreach ($reserv1 as $account_id => $items) {
//    if (count($items) == 1) {
//      $siteDB->query(
//        'update ' . $siteDB->generateTableName(
//          'accounts'
//        ) . ' set area_id = ' . $reserv2[$account_id][0]['area_id'] . ' where account_id = ' . $account_id
//      );
//    }
//  }
//  Debug()::dvD($reserv2);
//}
//
//
///**
// *  проверить smtp
// */
//if (Service::request()->_get('action') == 'testSmtp' && Service::request()->checkGet('email')) {
//  define('SMTP_DEBUG', 4);
//  /** @var reservation_dispatch $dispatch */
//  $dispatch = getEngine('reservation.dispatch');
//  $dispatch->setSmtp();
//  $dispatch->addAddress(Service::request()->_get('email'));
//  $dispatch->Subject = 'Subject test SMTP send email';
//  $dispatch->msgHTML('Body test Smtp send email');
//  $dispatch->send();
//}
//
//
///**
// *  phpinfo
// */
//if (Service::request()->_get('action') == 'phpinfo') {
//  phpinfo();
//  die;
//}
/**
 *  Переделать ячейку type в accounts_reservations на значение тип - спорт
 */
//$r = $site->getDbo()->query('select * from '. $site->getDbo()->getPrefixTable() . 'accounts_reservations ');
//foreach ($r as $ac_r) {
//  $q = 'update ' . $site->getDbo()->getPrefixTable() . 'accounts_reservations set type = (select concat(t.title, " - ", asp.title) from ' . $site->getDbo()->getPrefixTable() . 'reservations as r
//  left join ' . $site->getDbo()->getPrefixTable() . 'areas as a on r.area_id=a.area_id
//  left join ' . $site->getDbo()->getPrefixTable() . 'areas_types as t on a.type_id=t.type_id
//  left join ' . $site->getDbo()->getPrefixTable() . 'areas_sports as asp on a.sport_id=asp.sport_id
//  where r.reservation_id= ' . $ac_r['sys_reservation_id'] . '
//  ) where reservation_id = ' . $ac_r['reservation_id'];
//  D()::dvD($q);
//  $site->getDbo()->query($q);
//}

/**
 *  Копирование площадок и всех данных для них
 */

//$dbSite = $site->getDbo();
//$dbStreet = $street->getDbo();

//$areasId = array();
//$sort = 4;
///** площадки */
//foreach ($dbStreet->getAllDataFromTable('areas') as $area) {
//  $area->type_id = 2;
//  $area->sport_id = 1;
//  $area->sort = $sort;
//  $id = $area->area_id;
//  unset($area->area_id);
//  $values = $fields = array();
//  foreach ($area as $column => $value) {
//    $fields[] = $column;
//    $values[] = $value;
//  }
//  $query = 'insert into '. $dbSite->generateTableName('areas') . ' (`' . implode('`, `', $fields) . '`) values ("'.implode('","', $values).'")';
////  D()::dvD($query);
//  $dbSite->query($query, array(), false);
//  $areasId[$id] = $dbSite->lastInsertId();
//  $sort++;
//}
///** время работы */
//foreach ($dbStreet->getAllDataFromTable('areas_timetables') as $area_timetable) {
//  $area_timetable->area_id = $areasId[$area_timetable->area_id];
//  $values = $fields = array();
//  foreach ($area_timetable as $column => $value) {
//    $fields[] = $column;
//    $values[] = $value;
//  }
//  $query = 'insert into '. $dbSite->generateTableName('areas_timetables') . ' (`' . implode('`, `', $fields) . '`) values ("'.implode('","', $values).'")';
//  $dbSite->query($query, array(), false);
//}
///** цены */
//foreach ($dbStreet->getAllDataFromTable('areas_prices') as $area_price) {
//  $area_price->area_id = $areasId[$area_price->area_id];
//  $area_price->period_id = 1;
//  $values = $fields = array();
//  foreach ($area_price as $column => $value) {
//    $fields[] = $column;
//    $values[] = $value;
//  }
//  $query = 'insert into '. $dbSite->generateTableName('areas_prices') . ' (`' . implode('`, `', $fields) . '`) values ("'.implode('","', $values).'")';
//  $dbSite->query($query, array(), false);
//
//  $area_price->period_id = 2;
//  $values = $fields = array();
//  foreach ($area_price as $column => $value) {
//    $fields[] = $column;
//    $values[] = $value;
//  }
//  $query = 'insert into '. $dbSite->generateTableName('areas_prices') . ' (`' . implode('`, `', $fields) . '`) values ("'.implode('","', $values).'")';
//  $dbSite->query($query, array(), false);
//}


//foreach ($dbSite->getAllDataFromTable('clients') as $client) {
//  if($client->club_state == 1) {
//    $client->unavailable_sports = '2_1';
//  $query = 'update ' . $dbSite->generateTableName('clients') . 'set unavailable_sports ="2_1" where client_id=' .  $client->client_id;
//  $dbSite->query($query, array(), false);
//  }
//}
//
//array('schoenle', 'timpi', 'test_fm');
//$streetClients = $dbStreet->getAllDataFromTable('clients');
//$siteClients = $dbSite->getAllDataFromTable('clients');
//foreach ($siteClients as $key => $client) {
//  $siteClients[$client->login] = $client;
//  unset($siteClients[$key]);
//}
//$insertClients = array();
//$clients = array();
//$c = 0;
////D()::dvD(count($streetClients));
//foreach ($streetClients as $client) {
//  unset($client->client_id);
//  unset($client->hotel);
//  unset($client->areas);
//  $client->club_state ++;
//  $client->address = $client->adress;
//  unset($client->adress);
//
////  if(in_array($client->login, array_keys($siteClients))) {
////    $c++;
////    foreach ($siteClients[$client->login] as $column => $value) {
//////      if(isset($client->{$column}) && !in_array($column, array('stock_id', 'sprice_id', 'prepayment_sum', 'limit_day', 'reservations_removed', 'order_period', 'mode', 'area_type', 'registered', 'account_number', 'bank_index'))) {
//////        $clients[$client->login][$column] = array(checkColumn($column, $value), checkColumn($column, $client->{$column}));
//////      }
////      if($client->club_state == 1) {
////        $client->unavailable_sports = '1_1';
////      }
////      if(!isset($client->{$column})) {
//////  $values = $fields = array();
//////  foreach ($area_price as $column => $value) {
//////    $fields[] = $column;
//////    $values[] = $value;
//////  }
//////  $query = 'insert into '. $dbSite->generateTableName('areas_prices') . ' (`' . implode('`, `', $fields) . '`) values ("'.implode('","', $values).'")';
//////  $dbSite->query($query, array(), false);
////        $c++;
////      }
////    }
////  }
//
//  if(in_array($client->login, array_keys($siteClients))) {
//    $client = $siteClients[$client->login];
//    D()::dvD($client->login, $client->unavailable_sports);
//    $query = 'update ' . $dbSite->generateTableName('clients') . 'set unavailable_sports ="" where client_id=' .  $client->client_id;
//    $dbSite->query($query, array(), false);
//  }
//
//  if(!in_array($client->login, array_keys($siteClients))) {
//    if($client->club_state == 1) {
//      $client->unavailable_sports = '1_1';
//    }
//    switch ($client->nds) {
//      case 1:
//        $client->nds = 3;
//        break;
//      case 5:
//        $client->nds = 2;
//        break;
//      case 6:
//        $client->nds = 1;
//        break;
//      case 4:
//        $client->nds = 4;
//        break;
//    }
////    D()::dvD($client);
//  $values = $fields = array();
//  foreach ($client as $column => $value) {
//    $fields[] = $column;
//    $values[] = $value;
//  }
//  $query = 'insert into '. $dbSite->generateTableName('clients') . '
//    (`' . implode('`, `', $fields) . '`) values
//    ("'.implode('","', $values).'")';
////  D()::dvD($query);
//  $dbSite->query($query, array(), false);
//      $c++;
//  }
//}
//D()::dvD($c);
//function checkColumn($column, $value)
//{
//  switch ($column) {
//    case 'club_state' :
//      switch ($value) {
//        case 1:
//          return 'Nichtmitglied	';
//        case 2:
//          return '	Mitglied V1';
//        case 3:
//          return '	Mitglied V2';
//      }
//      break;
//    case 'active':
//      switch ($value) {
//        case 0:
//          return 'N';
//        case 1:
//          return 'J';
//      }
//      break;
//    case 'encash':
//      switch ($value) {
//        case 0:
//          return 'Bar';
//        case 1:
//          return 'RE';
//        case 2:
//          return 'GU';
//      }
//      break;
//    default:
//      return $value;
//  }
//}
//
//$columns = array(
//  'login' => 'Login',
//  'password_md5' => 'Password',
//  'name' => 'Vorname',
//  'surname' => 'Familienname',
//  'club_state' => 'Mitglied-Status',
//  'encash' => 'Zahlungsmethode',
//  'active' => 'Aktiv',
//  'nds' => 'MwSt.',
//  'number' => 'Mg.Nr',
//  'birthday' => 'Geburtstag',
//  'phone' => 'Telefon',
//  'phone_mobile' => 'Mobil',
//  'fax' => 'Fax',
//  'city' => 'Ort',
//  'post_code' => 'PLZ',
//  'address' => 'Anschrift',
//  'email' => 'E-Mail',
//  'account_owner' => 'Kontoinhaber',
//  'bank_name' => 'Name der Bank',
//  'bank_iban' => 'IBAN',
//  'bank_bic' => 'BIC',
//  'bank_sepa_mandat' => 'SEPA Mandat vom',
//  'bank_sepa_referenz' => 'SEPA Referenz'
//);
////
//$out = ";Halle;Aussenplätze\n";
//foreach ($clients as $client) {
//  foreach ($columns as $column => $title) {
//    $out .= $title.";". $client[$column][0].";". $client[$column][1] .";\n";
//  }
//  $out .= ";;\n";
//  $out .= ";;\n";
//}
//  header("Content-Disposition: attachment; filename=clients_export.csv");
//  header("Content-Type: application/x-force-download; name=\"clients_export.csv\"; charset=utf-8");
//  echo "\xEF\xBB\xBF" . $out;

