<?php

namespace AC\core\modules\membershipFees\engines;

use AC\core\engines\ClientsEngine;
use AC\core\system\db\Query;
use AC\core\system\db\QueryBilder;
use AC\core\system\helpers\StringHelper;

class MembershipFeesClientEngine extends ClientsEngine
{

  protected function getQueryVerificationProperties($verificationProperties, $prefixClientsTable = 'c')
  {
    $out = [];
    foreach ($verificationProperties as $table => $verificationProperty) {
      $method = 'getQueryVerificationProperties' . ucfirst(StringHelper::underscoreToCamelCase($table));
      if (method_exists($this, $method)) {
        $query = $this->$method($verificationProperty, $prefixClientsTable);
        if (!empty($query)) {
          $out[] = $query;
        }
      }
    }

    return !empty($out) ? implode(' and ', $out) : '';
  }

  protected function getQueryVerificationPropertiesClientData($verificationProperties, $prefixClientsTable = 'c')
  {
    if (!empty($verificationProperties) && getEngine('clientData', false)) {
      $queryArr = [];
      foreach ($verificationProperties as $property => $verification) {
        $queryArr[] = '(name = "' . $property . '" and value ' . $verification . ')';
      }
      if (!empty($queryArr)) {
        $queryVerificationProperties = '(select count(client_id) from ' . Query::tableName('client_data')
          . ' where client_id = ' . $prefixClientsTable . '.client_id and typeBlock = "membershipFees" 
            and (' . implode(' or ', $queryArr) . ') group by client_id) = ' . count($queryArr);
      }
    }

    return $queryVerificationProperties ?? '';
  }

  protected function getQueryVerificationPropertiesClients($verificationProperties, $prefixClientsTable = 'c')
  {
    if (!empty($verificationProperties)) {
      $queryArr = [];
      foreach ($verificationProperties as $property => $verification) {
        if (is_array($verification)) {
          foreach ($verification as $itemVerification) {
            $function = null;
            [$condition, $value, $function] = array_pad($itemVerification, 3, null);
            $field = $prefixClientsTable . '.' . $property;
            if ($function) {
              $field = $function . '(' . $field . ')';
            }
            $queryArr[] = $field . ' ' . $condition . ($value !== null ? ' "' . $value . '"' : '');
          }
        } else {
          $queryArr[] = $prefixClientsTable . '.' . $property . ' ' . $verification;
        }
      }
      if (!empty($queryArr)) {
        $queryVerificationProperties = implode(' and ', $queryArr);
      }
    }

    return $queryVerificationProperties ?? '';
  }

  public function getClientsDataByMembershipFees($year = false, $verificationProperties = [], $order_by = 'c.surname, c.name'): array
  {
    $clients_data                = [];
    $tmp_clients_data            = [];
    $queryVerificationProperties = $this->getQueryVerificationPropertiesClientData($verificationProperties);
    $q                           = 'select a.account_id, cd.value as membership_fees_group, c.* 
    from ' . Query::tableName('clients') . ' c
		left join ' . Query::tableName('accounts_clients') . ' ac ON ac.sys_client_id = c.client_id
		left join ' . Query::tableName('client_data') . ' cd ON cd.client_id = c.client_id and cd.typeBlock = "membershipFees" and cd.name = "membership_fees_group"
		left join ' . Query::tableName('accounts') . ' a ON a.client_id = ac.client_id AND a.deleted = "0" and a.account_type = "4" and YEAR(a.date_start) = "' . $year . '" 
		where c.club_state > 1 ' .
      ($queryVerificationProperties ? ' and ' . $queryVerificationProperties : '')
      . '
		GROUP BY a.account_id DESC, c.surname, c.name ORDER BY ' . $order_by;

    foreach (Query::sqlQuery($q) as $row) {
      $tmp_clients_data[$row['client_id']][$row['account_id']] = $row;
    }

    foreach ($tmp_clients_data as $client_id => $accounts) {
      if (is_array($accounts)) {
        if (count($accounts) == 1 && isset($accounts[null])) {
          $clients_data[] = $accounts[null];
          //в кеш инфрмации о клиентах
          $this->cache_client_data[$client_id] = $accounts[null];
        }
      }
    }

    return $clients_data;
  }

  public function getClientsDataById($clients_id = [], &$client_data = [])
  {
    parent::getClientsDataById($clients_id, $_client_data);
    foreach ($_client_data as $datum) {
      $client = module('membershipFees')->useModel('MembershipFeesClientModel')->loadData($datum);
      if (!empty($client->membership_fees_group)) {
        $client->membership_fees_group = getEngine('MembershipFeesGroups', false)->one($client->membership_fees_group);
      }
      $client_data[] = $client;
    }

    return $client_data;
  }

  public function getClubMemberData($order_by = 'surname, name')
  {
    $result = [];
    $q      = 'select c.client_id, c.name, c.surname,  cd.name as propertyKey, cd.value as propertyValue 
    from ' . Query::tableName('client_data') . ' cd 
    left join ' . Query::tableName('clients') . ' c on c.client_id = cd.client_id
    where cd.typeBlock = "membershipFees" and c.club_state > 1 order by c.surname, c.name';

    foreach (Query::sqlQuery($q) as $row) {
      if (!isset($result[$row['client_id']]['name'])) {
        $result[$row['client_id']]['name']      = $row['name'];
        $result[$row['client_id']]['surname']   = $row['surname'];
        $result[$row['client_id']]['client_id'] = $row['client_id'];
      }
      $result[$row['client_id']][$row['propertyKey']] = $row['propertyValue'];
    }

    return $result;
  }

  public function getTheBirthYearOfTheClubMembers()
  {
    $result                 = [];
    $verificationProperties = $this->getQueryVerificationProperties(config('membershipFees')->verificationPropertiesMembershipFees(null,
      ['clients' => ['birthday' => [['is not null'], ['<>', '0', 'year']]], 'SqlQueryExistGroup' => []]));

    $q = 'select DISTINCT year(c.birthday) as birthYear
    from ' . Query::tableName('clients') . ' c 
    left join ' . Query::tableName('client_data') . ' cd on c.client_id = cd.client_id
		' . (!empty($verificationProperties) ? ' where ' . $verificationProperties : '') . '
		GROUP BY c.birthday DESC';
    foreach (Query::sqlQuery($q) as $row) {
      $result[(string)$row['birthYear']] = $row['birthYear'];
    }

    return $result;
  }

  public function getClientsClubMembers($select = ['clients' => ['*']], $where = [], $orderBy = [])
  {
    $result                 = [];
    $verificationProperties = $this->getQueryVerificationProperties(config('membershipFees')->verificationPropertiesMembershipFees(null,
      array_replace_recursive(['clients' => ['birthday' => 'is not null']], $where)));

    $QB = (new QueryBilder())->setTableAlias(['clients' => 'c', 'client_data' => 'cd']);

    $q = 'select ' . implode(', ', $QB->fieldNamesWithTableAlias($select)) . ', cd.name as propertyKey, cd.value as propertyValue
    from ' . Query::tableName('clients') . ' c
    left join ' . Query::tableName('client_data') . ' cd on c.client_id = cd.client_id
		' . (!empty($verificationProperties) ? ' where ' . $verificationProperties : '')
      . (!empty($orderBy) ? ' order by ' . implode(',', $QB->fieldNamesWithTableAlias($orderBy)) : '');

    foreach (Query::sqlQuery($q) as $row) {
      if (!isset($result[$row['client_id']]['client_id'])) {
        $result[$row['client_id']]['client_id'] = $row['client_id'];
      }
      foreach ($row as $key => $value) {
        if ($key != 'propertyKey' && $key != 'propertyValue' && $key != 'client_id') {
          $result[$row['client_id']][$key] = $value;
        }
      }
      $result[$row['client_id']][$row['propertyKey']] = $row['propertyValue'];
    }

    return $result;
  }

  protected function getQueryVerificationPropertiesSqlQueryExistGroup()
  {
    return '(SELECT EXISTS(SELECT id FROM '. Query::tableName('membership_fees_groups') . ' WHERE id = (select value from '. Query::tableName('client_data') . ' where client_id = c.client_id and typeBlock = "membershipFees" and name = "membership_fees_group")))';
  }


}