<?php

namespace AC\core\modules\membershipFees\engines;

use AC\core\engines\AccountsEngine;
use AC\core\modules\accounts\models\AccountsModel;
use AC\core\system\db\Query;
use AC\core\system\helpers\JsonHelper;

class MembershipFeesAccountEngine extends AccountsEngine
{
  
  protected string $tableItems = 'accounts_membership_fees';
  
  protected function getCheckTables(): array
  {
    return array_merge(parent::getCheckTables(), [$this->tableItems]);
  }
  
  public function insertAccountMembershipFees(AccountsModel $account)
  {
    $q      = 'insert into ' . $this->table . ' set
							client_id = :client_id,
							account_type = :account_type,
							number = :number,
							date_start = :date_start, 
							date_finish = :date_finish, 
							date_creation = now(), 
							' . ($account->sepa_type ? 'sepa_type= "' . $account->sepa_type . '", ' : '') . '
							' . ($account->sepa_standart ? 'sepa_standart= "' . $account->sepa_standart . '", ' : '') . '
							text_config = "' . $account->text_config . '",
							nds = "' . $account->nds . '"';
    $params = [
      'client_id'    => $account->client_id,
      'account_type' => $account->account_type,
      'number'       => $account->number,
      'date_start'   => $account->date_start,
      'date_finish'  => $account->date_finish,
    ];
    
    if (Query::sqlQuery($q, $params, false)) {
      $account->account_id = Query::getLastId();
      
      return true;
    }
    
    return false;
  }
  
  public function verifyAccountExists(AccountsModel $account): bool
  {
    return !Query::sqlQuery(
      'select count(a.account_id) as count from ' . $this->table . ' as a 
              inner join ' . Query::tableName('accounts_clients') . ' as aoac on a.client_id = aoac.client_id and aoac.sys_client_id = ' . $account->getClientAccount()->sys_client_id . '
              where  a.date_start = \'' . $account->date_start . '\' and a.account_type = "' . $account->account_type . '" and a.deleted = "0"',
      [],
      true,
      ['onlyOne' => true]
    )['count'];
  }
  
  protected function removeItemsAccount($accountId)
  {
    Query::sqlQuery('DELETE FROM ' . Query::tableName($this->tableItems) . ' WHERE account_id = :account_id',
      ['account_id' => $accountId], false);
  }
  
  public function getAccounts($archives = false, $year_select = false, $type = false, $month_select = false)
  {
    $q   = 'SELECT a.number as a_number, a.*, c.*, c.number as client_number, cl.number as cl_number,
       r.price as sum, r.membership_fees_group_title, r.membership_fees_group, cl.club_state as club_state
        FROM ' . $this->table . ' a
        LEFT JOIN ' . $this->table_clients . ' c ON a.client_id = c.client_id
        LEFT JOIN ' . Query::tableName($this->tableItems) . ' r ON a.account_id = r.account_id 
        LEFT JOIN ' . $this->table_cl . ' cl ON c.sys_client_id = cl.client_id 
        WHERE a.account_type = "4" /*AND a.deleted="0"*/ AND a.archives = "' . (!$archives ? '0' : '1') . '"' .
      ($year_select ? ' AND YEAR(a.date_start) = "' . $year_select . '"' : '') .
      ($month_select ? ' AND MONTH(a.date_start) = "' . $month_select . '"' : '') .
      ' GROUP BY a.account_id';
    $out = Query::sqlQuery($q);
    if (!empty($out)) {
      return $out;
    } else {
      return false;
    }
  }
  
  public function getArrayListTableAccounts()
  {
    $arrayTable                                      = parent::getArrayListTableAccounts();
    $arrayTable[Query::tableName($this->tableItems)] = $this->tableItems;
    
    return $arrayTable;
  }
  
  function getAccountsById($accounts_id = [], $type = false, $additionalFields = [])
  {
    $typeStr = ($type) ? ('asys.type_id="' . $type . '" and') : '';
    $q       = 'select cl.firm as firm,a.number as a_number, a.*, c.*, c.number as client_number, cl.number as cl_number, 
                    cl.bank_sepa_referenz,ai.id as item_id, ai.*, cl.email as sys_email'
      . (isset($additionalFields['sql']['select']) ? ', ' . implode(',', (array)$additionalFields['sql']['select']) : '')
      . '
       from ' . $this->table . ' a  
        LEFT JOIN ' . $this->table_clients . ' c ON a.client_id = c.client_id 
        LEFT JOIN ' . Query::tableName($this->tableItems) . ' ai ON a.account_id = ai.account_id 
        LEFT JOIN ' . $this->table_cl . ' cl ON cl.client_id = c.sys_client_id 
       WHERE ' . $typeStr . ' a.account_id IN (' . join(
        ',',
        $accounts_id
      ) . ') ORDER BY a.account_id';
    $temp    = Query::sqlQuery($q);
    if (!empty($temp)) {
      foreach ($temp as $item) {
        $out[$item['account_id']]['a_number']                                                      = $item['a_number'];
        $out[$item['account_id']]['firm']                                                          = $item['firm'];
        $out[$item['account_id']]['a_date_start']                                                  = $item['date_creation'] ?: $item['date_start'];
        $out[$item['account_id']]['date_start']                                                    = $item['date_start'];
        $out[$item['account_id']]['date_finish']                                                   = $item['date_finish'];
        $out[$item['account_id']]['date_creation']                                                 = $item['date_creation'] ?: $item['date_start'];
        $out[$item['account_id']]['execution']                                                     = $item['execution'];
        $out[$item['account_id']]['send']                                                          = $item['send'];
        $out[$item['account_id']]['archives']                                                      = $item['archives'];
        $out[$item['account_id']]['name']                                                          = $item['name'];
        $out[$item['account_id']]['surname']                                                       = $item['surname'];
        $out[$item['account_id']]['address']                                                       = $item['address'];
        $out[$item['account_id']]['post_code']                                                     = $item['post_code'];
        $out[$item['account_id']]['city']                                                          = $item['city'];
        $out[$item['account_id']]['email']                                                         = $item['email'];
        $out[$item['account_id']]['client_number']                                                 = $item['client_number'];
        $out[$item['account_id']]['cl_number']                                                     = $item['cl_number'];
        $out[$item['account_id']]['account_owner']                                                 = $item['account_owner'];
        $out[$item['account_id']]['account_number']                                                = $item['account_number'];
        $out[$item['account_id']]['bank_index']                                                    = $item['bank_index'];
        $out[$item['account_id']]['bank_name']                                                     = $item['bank_name'];
        $out[$item['account_id']]['text_config']                                                   = $item['text_config'];
        $out[$item['account_id']]['nds']                                                           = $item['nds'];
        $out[$item['account_id']]['sepa_type']                                                     = $item['sepa_type'];
        $out[$item['account_id']]['sepa_standart']                                                 = $item['sepa_standart'];
        $out[$item['account_id']]['sepa_iban']                                                     = $item['sepa_iban'];
        $out[$item['account_id']]['sepa_bic']                                                      = $item['sepa_bic'];
        $out[$item['account_id']]['sepa_mndtid']                                                   = $item['sepa_mndtid'];
        $out[$item['account_id']]['sepa_dtofsgntr']                                                = $item['sepa_dtofsgntr'];
        $out[$item['account_id']]['bank_sepa_referenz']                                            = $item['bank_sepa_referenz'];
        $out[$item['account_id']]['reservations'][$item['item_id']]['membership_fees_group']       = $item['membership_fees_group'];
        $out[$item['account_id']]['reservations'][$item['item_id']]['membership_fees_group_title'] = $item['membership_fees_group_title'];
        $out[$item['account_id']]['reservations'][$item['item_id']]['price']                       = $item['price'];
        $out[$item['account_id']]['reservations'][$item['item_id']]['comment']                     = $item['comment'] ?? '';
        $out[$item['account_id']]['sys_email']                                                     = $item['sys_email'];
        $out[$item['account_id']]['text_fields']                                                   = JsonHelper::decode($item['text_fields'], true)
          ?: [];
      }
      
      return $out;
    } else {
      return false;
    }
  }
}