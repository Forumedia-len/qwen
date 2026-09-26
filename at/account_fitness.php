<?php

use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\TranslateHelper;

require_once 'accounts_admin.php';


class account_fitness extends accounts_admin
{
  public $currentType = array();
  /**
   * @var string
   */
  protected $page_key;

  public function __construct()
  {
    Service::engines()->areas->setGroupType('fitness');
    parent::__construct();
  }

  public function start()
  {
    switch (Service::request()->_('action')) {
      case 'generateAboFitAccount';
        return $this->generateAboFitAccounts();
        break;
      case 'showAboFitnessAccountsList':
        return $this->showAboFitnessAccountsList();
        break;
      case 'addToArchiveAccounts':
        return $this->addToArchive();
        break;
      case 'showAboFitnessAccountsArchives':
        return $this->showAboFitnessAccountsArchives();
        break;
      default:
        return parent::start();
    }
  }

  //первая страница
  function mainPage()
  {
    $this->page_key = 'fitness_accounts_tickets_generate';

    $output = array();
    if (isset ($this->error)) {
      $output[] = '<p align="center" class="error">' . $this->error . "</p>\n";
    } elseif (isset ($this->mess)) {
      $output[] = '<p align="center">' . $this->mess . "</p>\n";
    }

    $out      = '<table align="center" cellpadding="10" cellspacing="10">' . "\n";
    $out      .= '<tr>' . "\n";
    $out      .= '<td valign="top">' . $this->generateAboFitAccountsForms() . '</td>' . "\n";
    $out      .= '</tr>' . "\n";
    $out      .= '</table>' . "\n";
    $output[] = $out;

    return $output;
  }

  function generateAboFitAccountsForms()
  {

    $out = '<table align="center" border="0" cellspacing="8">' . "\n";
    $out .= '<tr><td valign="top">' . "\n";

    if (isset($_POST['client_id'])) {
      $client_id = (int)$_POST['client_id'];
    }

    //Клиенты
    if ($this->r->clients->getClientsDataByAboFit($clients_data) == true) {
      $select_clients = '<select name="client_id[]" MULTIPLE size="15">' . "\n";
      foreach ($clients_data as $c_id => $client_data) {
        if (!isset($client_id)) {
          $client_id = $c_id;
        }
        $ticket_id = null;
        foreach ($client_data['ticket'] as $key => $item) {
          if ($ticket_id == null) {
            $ticket_id = $key;
          }
        }
        $tmp = $client_data['surname'] . ' ' . $client_data['name'] . ' - ' . date('m-Y',
            strtotime($client_data['ticket'][$ticket_id]['ticket_start_date'] . ' + ' . ($client_data['ticket'][$ticket_id]['check_payd'] != null ? $client_data['ticket'][$ticket_id]['check_payd'] : 0) . ' month'));
        if (strlen($tmp) > 75) {
          $tmp = substr($tmp, 0, 75) . '...';
        }
        $select_clients .= "<option value=\"" . $c_id . "\" " . ($client_id == $c_id ? '' : '') . ">" . $tmp . "</option>\n";
      }
      $select_clients .= '</select>' . "\n";
    }

    $out .= '<td valign="top">' . "\n";
    //Выставление счета
    $out .= '<table cellspacing="1" cellpadding="3" class="main" border="0">' . "\n";
    $out .= '	<tr><th colspan="2">Abo-Fitness-Rechnungen</th></tr>' . "\n";
    //Выбор Клиентов имеющих заказы за эту дату
    if ($clients_data) {
      $out .= '<form action="account_fitness.php?action=generateAboFitAccount" method="post">' . "\n";
      $out .= '<tr><td class="light">Kunden:</td><td class="light"><table border="0"><tr><td>' . $select_clients . '</td></tr></table></td></tr>' . "\n";
      $out .= '<tr><td class="light" colspan="2" align="center"><input type="submit" value="Ausf&uuml;hren" class="button"/></td></tr>' . "\n";
      $out .= "</form>\n";
    }
    $out .= "</table>\n";
    $out .= '</td>' . "\n";
    $out .= '</tr></table>' . "\n";

    return $out;
  }

  function generateAboFitAccounts()
  {
    $this->page_key = 'fitness_accounts_tickets_list';
    if (isset($_POST['client_id'])) {
      $client_ids = $_POST['client_id'];
      $this->r->clients->getClientsDataByAboFit($clients_data);
      foreach ($client_ids as $client_id) {
        $client_data = $clients_data[$client_id];
        foreach ($clients_data[$client_id]["ticket"] as $ticket_id => $ticket_data) {
          //хук для новых абонементов
          $plus_months = "+" . ($ticket_data['check_payd'] ? $ticket_data['check_payd'] : 0) . " month";
          $start_fit   = date('Y-m-01', strtotime($ticket_data['ticket_start_date'] . ' ' . $plus_months));
          $finish_fit  = date('Y-m-t', strtotime($ticket_data['ticket_start_date'] . ' ' . $plus_months));

          if ($this->a->insertAboFitAccount(
            $client_data['client_id'],
            $client_data['name'],
            $client_data['surname'],
            $client_data['address'],
            $client_data['post_code'],
            $client_data['city'],
            $client_data['email'],
            $client_data['number'],
            $ticket_data['time_start'], $ticket_data['time_finish'],
            $_POST['text_config'] ?? 0,
            $client_data['weekdays'],
            $client_data['type'], $client_data['area'],
            (int)$client_data['nds_rate'],
            $ticket_id,
            $ticket_data['check_payd'],
            $ticket_data["price_for_month"],
            array(
              $client_data['bank_iban'],
              $client_data['bank_bic'],
              $client_data['bank_sepa_referenz'],
              $client_data['bank_sepa_mandat'],
              $client_data['sepa_type'],
              $client_data['sepa_standart']
            ),
            array(
              $client_data['account_owner'],
              $client_data['account_number'],
              $client_data['bank_index'],
              $client_data['bank_name']
            ),
            null,
            null,
            $ticket_data['title'],
            $start_fit,
            $finish_fit,
            $ticket_data['area_id']
          )) {
            $this->mess = 'Account generate';
          } else {
            $this->error = 'Account not generate';
          }
        }
      }
    } else {
      $this->error = 'Incorect query';
    }

    return $this->showAboFitnessAccountsList();
  }

  function showAboFitnessAccountsList()
  {
    $this->page_key = 'fitness_accounts_tickets_list';

    $output = array();

    if (isset ($this->error)) {
      $output[] = '<p align="center" class="error">' . $this->error . "</p>\n";
    } elseif (isset ($this->mess)) {
      $output[] = '<p align="center">' . $this->mess . "</p>\n";
    }

    $out = "<h1>Fitness Abo-Rechnungsjournal</h1>\n";

    $out .= '<p>NB! Sie k&ouml;nnen nur ausgedruckte (gr&uuml;n markierte) Rechnungen archivieren!</p>' . "\n";

    $out .= '<table align="center" border="0" cellspacing="1" cellpadding="3" class="main wide" width="80%">' . "\n";
    $out .= '<tr>
              <td class="light" colspan="10" align="right">' . "\n";
    if (config('account')->useSEPA()) {
      $out .= $this->sepaForm('aboFitness');
    }
    $out .= '</td>
					    </tr>' . "\n";
    $out .= '<tr>' . "\n";
    $out .= '<th><input type="checkbox" name="all_select" value="0" onchange="selectAllAccount(this)"/>' . lang('To mark') . '</th>' . "\n";
    $out .= '<th>' . lang('Invoice number', 'accounts') . '</th>' . "\n";
    $out .= '<th>' . lang('Date') . '</th>' . "\n";
    $out .= '<th>' . lang('Name-address', 'accounts') . '</th>' . "\n";
    $out .= '<th>' . lang('Status', 'accounts') . '</th>' . "\n";
    $out .= '<th>' . lang('Total amount', 'accounts') . ' (' . CURR_VALUTE . ')</th>' . "\n";
    if (config('account')->useSEPA()) {
      $out .= '<th>' . lang('SEPA_title') . '</th>' . "\n";
    }
    $out .= '<th colspan="2">' . lang('Actions') . '</th>' . "\n";
    $out .= '</tr>' . "\n";

    if ($accounts = $this->a->getAboAccounts(false, false, false)) {
      $out .= '<form action="account_fitness.php?action=markExecution" method="POST" id="accounts_list">' . "\n";
      $out .= '<input type="hidden" name="account_type" value="6"/>' . "\n";

      foreach ($accounts as $account_id => $a) {
        $sum = 0;
//        $out      .= '<tr class="row_fit">' . "\n";
        $out .= '<tr>' . "\n";
        $out .= '<td class="light" align="center" valign="top"><input type="checkbox" name="account[]" value="' . $account_id . '"/></td>' . "\n";
        $out .= '<td class="' . ($a['execution'] ? 'select' : 'dark') . '" id="numberAccountBlock' . $account_id . '" valign="top">' . config('accountView')->getNumberAccount($a['a_number'],
            ABO_ACCOUNT_NUMBER) . '</td>' . "\n";
        $out .= '<td class="light">' . "\n";
        $out .= $a['type'] . ' ' . $a['area'] . "\n";
//        $weekdays = explode(',', $a['weekdays']);
//        foreach ($weekdays as $weekday) {
//          $out .= TranslateHelper::translateWeekday($weekday, true) . ' ';
//        }
        if (is_array($a['reservations'])) {
          $out             .= '<div>' . lang('Time period') . ':</div>' . "\n";
          $out             .= '<div>';
          $max_finish_date = false;
          foreach ($a['reservations'] as $p) {
            $sum += $p['price'];
            $out .= date('d.m.Y', strtotime($p['date_start'])) . ' - ' . date('d.m.Y',
                strtotime($p['date_finish'])) . "<br/>\n";
            if (!$max_finish_date || $max_finish_date < date('Y-m-d', strtotime($p['date_finish']))) {
              $max_finish_date = date('Y-m-d', strtotime($p['date_finish']));
            }
          }
          $out .= '</div>' . "\n";
        }
        $sum = ($a['abo_sum'] > 0 ? $a['abo_sum'] : $sum);
        $out .= '</td>' . "\n";
        $out .= '<td class="dark">' . $a['name'] . ' ' . $a['surname'] . '<br/>' . $a['address'] . ', ' . $a['post_code'] . ' ' . $a['city'] . '</td>' . "\n";
        $out .= '<td class="light" style="white-space: nowrap">' . ((!empty($a['club_state'])) ? ((($a['club_state'] == 1) ? 'NM'
              : ('V' . ($a['club_state'] - 1))) . '/ ') : '') . $a['nds'] . '%</td>' . "\n";
        $out .= '<td class="light" align="right" valign="top">'
          . NumberHelper::valute($sum) . '</td>' . "\n";
        if (config('account')->useSEPA()) {
          if (strlen($a['name']) > 0 && strlen($a['sepa_iban']) > 0 && strlen($a['sepa_bic']) > 0 && strlen($a['sepa_mndtid']) > 0 && strlen($a['sepa_dtofsgntr']) > 0 && $sum > 0) {
            $color = 'green';
          } else {
            $color = 'red';
          }
          $out .= '<td class="dark" valign="top" align="center">' . "\n";
          $out .= '<img src="' . base_url(paths()->getAssetsDir('images/bullet_' . $color . '.gif')) . '"/>';
          $out .= '</td>' . "\n";
        }
        $out .= '<td class="dark" valign="top">
							<a href="accounts_view.php?account=' . $account_id . '&account_type=6" target="_blank" class="btnView">'
          . lang('View') . '</a><br />
							<a href="accounts_view.php?account=' . $account_id . '&account_type=6&action=send" target="_blank" onclick="return markExecution(this, \'numberAccountBlock' . $account_id . '\')" class="btnMail"  ' . ($a['send'] == 1 ? 'style="color:#0712bb"' : '') . '>' . lang('Send as email',
            'accounts') . '</a>
						</td>' . "\n";
        if (isset($a['light_reservations'])) {
          $out .= '<td class="dark" valign="top"><a href="accounts_view.php?account=' . $account_id . '&account_type=21" target="_blank" class="btnView">'
            . lang('To the light/heating bill', 'accounts') . '</a></td>' . "\n";
        } else {
          if ($max_finish_date < date('Y-m-d')) {
            $out .= '<td class="dark" valign="top"><a href="account_fitness.php?action=generateLightAccount&account=' . $account_id . '" class="btnView">' . lang('Accounting for light/heating',
                'accounts') . '</a></td>' . "\n";
          } else {
            $out .= '<td class="dark" valign="top">&nbsp;</td>' . "\n";
          }
        }
        $out .= '</tr>' . "\n";

      }

      $out .= '<tr><td class="light" colspan="10" align="right">
							<input type="submit" name="submit_print" value="' . lang('Print') . '" class="button"/>
							<input type="submit" name="submit_archives" value="' . lang('Archive') . '" class="button"/>
							<input type="submit" name="submit_download" value="' . lang('Export RE Journal (CSV)', 'accounts') . '" class="button"/>
							<input type="submit" name="submit_sendAllEmails" value="' . lang('Send as email', 'accounts') . '" class="button" 
							       onClick="return ifConfirm(\'' . lang('confirm_send_email_account', 'js_confirm') . '\')"/> 
					</td></tr>' . "\n";
      $out .= '</form>' . "\n";
    }
    $out      .= '</table>' . "\n";
    $output[] = $out;

    return $output;
  }

  //Архив счетов
  function showAboFitnessAccountsArchives()
  {
    $this->page_key = 'fitness_accounts_tickets_archives';

    $output = array();
    if ($this->message !== null) {
      $output[] = '<p style="text-align: center">' . $this->message . '</p>';
      unset($this->message);
    }
    $month = Service::request()->_('month', date('m'));

    $out = "<h1>Fitness Abo-Rechnungsarchiv</h1>\n";

    $out .= '<table align="center" border="0" cellspacing="1" cellpadding="3" class="main wide" width="80%">' . "\n";

    $out .= '<tr><td class="dark" colspan="10">' . "\n";
//    $out .= $this->navigationYearsLine(1, $year, true);
    $tb     = getThemeBuilder();
    $years  = $this->navigationYearsLineFitness(1, $year, true, true);
    $months = array();
    for ($i = 1; $i <= 12; $i++) {
      $months[sprintf("%02d", $i)] = TranslateHelper::translateMonth($i);
    }
    $out .= '<form method="post" action="account_fitness.php?action=showAboFitnessAccountsArchives">';
    $out .= '<input type="hidden" name="action" value="showAboFitnessAccountsArchives">';
    $out .= '<input type="hidden" name="account_type" value="6">';
    $out .= $tb->select('year', $years, $year);
    $out .= $tb->select('month', $months, $month);
    $out .= '<input type="submit" value="ok" class="button" style="height: 22px;width: 44px;margin-left: 10px;"/>';
    $out .= '</td></tr>' . "\n";
    $out .= '</form>';
    $out .= '<form method="post" action="account_fitness.php?action=markExecution" id="archive_accounts_list">';
    $out .= '<input type="hidden" name="action" value="markExecution">';
    $out .= '<input type="hidden" name="account_type" value="6">';
    $out .= '<input type="hidden" name="year" value="' . $year . '">';
    $out .= '<input type="hidden" name="month" value="' . $month . '">';
    $out .= '<tr>
                <td colspan="10" align="right">
                  <input type="submit" name="submit_addToArchive" value="Löschen" class="button" style="padding: 5px;margin-left: 10px;" onClick="return ifConfirm(\'Das löschen der Rechnungen kann nicht mehr rückgängig gemacht werden. \nSind Sie sicher ?\')"/>
                </td>
             </tr>';
    $out .= '<tr><th colspan="10" style="font-size: 14px; font-weight: bold">Rechnungen für ' . TranslateHelper::translateMonth($month,
        true) . ' ' . $year . '</th></tr>';
    $out .= '<tr>' . "\n";
    $out .= '<th valign="middle">
                <input type="checkbox" name="all_select" value="0" onchange="selectAllAccount(this)" style="display: inline-block; position: relative; top:2px;left:-3px"/>
                <span style="display: inline-block;">Alle markieren</span>
             </th>' . "\n";
    $out .= '<th>' . lang('Invoice number', 'accounts') . '</th>' . "\n";
    $out .= '<th>' . lang('Date') . '</th>' . "\n";
    $out .= '<th>' . lang('Name-address', 'accounts') . '</th>' . "\n";
    $out .= '<th>' . lang('Status', 'accounts') . '</th>' . "\n";
    $out .= '<th>' . lang('Total amount', 'accounts') . ' (' . CURR_VALUTE . ')</th>' . "\n";
    $out .= '<th colspan="4">' . lang('Actions') . '</th>' . "\n";
    $out .= '</tr>' . "\n";

    if ($accounts = $this->a->getAboAccounts(true, $year, $month)) {
      foreach ($accounts as $account_id => $a) {
        $sum = 0;
        $out .= '<tr>' . "\n";
        $out .= '<td class="light"><input type="checkbox" value="1" name="account[' . $account_id . ']"></td>';
        $out .= '<td class="' . ($a['deleted'] ? 'selectDel' : 'dark') . '" valign="top">' . config('accountView')->getNumberAccount($a['a_number'],
            ABO_ACCOUNT_NUMBER) . '</td>' . "\n";
        $out .= '<td class="light"  valign="top">' . "\n";
        $out .= $a['type'] . ' ' . $a['area'] . ' ' . date('H:i', strtotime($a['time_start'])) . '-' . date('H:i',
            strtotime($a['time_finish'])) . ' Uhr' . "\n";
//        $weekdays = explode(',', $a['weekdays']);
//        foreach ($weekdays as $weekday) {
//          $out .= TranslateHelper::translateWeekday($weekday, true) . ' ';
//        }
        if (is_array($a['reservations'])) {
          $out .= '<div>' . lang('Time period') . ':</div>' . "\n";
          $out .= '<div>';
          foreach ($a['reservations'] as $p) {
            $sum += $p['price'];
            $out .= date('d.m.Y', strtotime($p['date_start'])) . ' - ' . date('d.m.Y',
                strtotime($p['date_finish'])) . "<br/>\n";
          }
          $out .= '</div>' . "\n";
        }
        $sum = ($a['abo_sum'] > 0 ? $a['abo_sum'] : $sum);
        $out .= '</td>' . "\n";
        $out .= '<td class="dark">' . $a['name'] . ' ' . $a['surname'] . '<br/>' . $a['address'] . ', ' . $a['post_code'] . ' ' . $a['city'] . '</td>' . "\n";
        $out .= '<td class="light">' . ((!empty($a['club_state'])) ? ((($a['club_state'] == 1) ? 'NM' : ('V' . ($a['club_state'] - 1))) . '/ ')
            : '') . $a['nds'] . '%</td>' . "\n";
        $out .= '<td class="light" align="right"  valign="top">' . NumberHelper::valute($sum) . '</td>' . "\n";
        $out .= '<td class="dark" valign="top">' . ($a['deleted'] ? '<a href="accounts_view.php?account_delete=' . $account_id . '&account_type=6&year=' . $year . '&month=' . $month . '" target="_blank" class="btnConfirmationDelete">Zur Gutschrift</a>' : '') . '</td>
						<td class="dark" valign="top"><a href="accounts_view.php?account=' . $account_id . '&account_type=6" target="_blank" class="btnView">Ansehen</a></td>
						<td class="dark" valign="top"><a href="account_fitness.php?action=deleteAccount&account=' . $account_id . '&account_type=6&year=' . $year . '&month=' . $month . '" class="btnRemove" onClick="return ifConfirm()">Stornieren</a></td>
						<td class="dark" valign="top"><a href="account_fitness.php?action=addToArchiveAccounts&account=' . $account_id . '&account_type=6&year=' . $year . '&month=' . $month . '" class="btnRemove" onClick="return ifConfirm(\'Das löschen der Rechnungen kann nicht mehr rückgängig gemacht werden. \nSind Sie sicher ?\')">Löschen</a></td>
						' . "\n";
        $out .= '</tr>' . "\n";
      }

    } else {
      $out .= '<tr><td colspan="10">Keine Rechnungen im Archiv für ' . TranslateHelper::translateMonth($month, true) . ' ' . $year . '</td></tr>';
    }
    $out .= '<tr>
                <td colspan="10" align="right">
                  <input type="submit" name="submit_addToArchive" value="Löschen" class="button" style="padding: 5px;margin-left: 10px;" onClick="return ifConfirm(\'Das löschen der Rechnungen kann nicht mehr rückgängig gemacht werden. \nSind Sie sicher ?\')"/>
                </td>
             </tr>';
    $out .= '</form>';
    $out .= '</table>' . "\n";

    $output[] = $out;

    return $output;
  }

  protected function download($prefixAccountNumber = ACCOUNT_NUMBER)
  {
    $fields = [
      'Invoice number'      => lang('Invoice number', 'accounts'),
      'Name'                => lang('Name'),
      'Surname'             => lang('First name'),
      'Street'              => lang('Street/No'),
      'Zip'                 => lang('Zip'),
      'City'                => lang('City'),
      'Bank account holder' => lang('Bank account holder'),
      'Bank name'           => lang('Bank name'),
      'IBAN'                => lang('IBAN'),
      'BIC'                 => lang('BIC'),
      'Mandate reference'   => lang('Mandate reference'),
      'Mandate date'        => lang('Mandate date'),
      'Amount'              => lang('Amount'),
      'Net amount'          => lang('Net amount'),
      'VAT Amount'          => lang('VAT Amount'),
      'VAT Set'             => lang('VAT Set'),
      'E-mail'              => lang('E-mail')
    ];
    $out    = implode(';', $fields) . "\n";
    if ($accounts = $this->a->getAboAccounts()) {
      foreach ($accounts as $a) {
        $sum = 0;
        if (is_array($a['reservations'])) {
          foreach ($a['reservations'] as $p) {
            $sum += $p['price'];
          }
        }

        $sum     = ($a['abo_sum'] > 0 ? $a['abo_sum'] : $sum);
        $nds_sum = $sum - ($sum / (1 + $a['nds'] / 100));

        $out .= join(
            ';',
            array(
              config('accountView')->getNumberAccount($a['a_number'], $prefixAccountNumber),
              $a['name'],
              $a['surname'],
              $a['address'],
              $a['post_code'],
              $a['city'],
              $a['account_owner'],
              $a['bank_name'],
              $a['sepa_iban'],
              $a['sepa_bic'],
              trim($a['sepa_mndtid']),
              trim($a['sepa_dtofsgntr']),
              number_format($sum, 2, ',', ''),
              number_format(($sum - $nds_sum), '2', ',', ''),
              number_format($nds_sum, '2', ',', ''),
              $a['nds'] . ' %',
              $a['email']
            )

          ) . "\n";
      }
      header("Content-Disposition: attachment; filename=accounts_export_" . date('Y_m_d') . ".csv");
      header(
        "Content-Type: application/x-force-download; name=\"accounts_export_" . date('Y_m_d') . ".csv\"; charset=utf-8"
      );
      echo "\xEF\xBB\xBF" . $out;
      die;
    }
  }

  function markExecution()
  {
    return match (str_replace('submit_', '', Service::request()->checkPregMatchPost('submit_'))) {
      'print'         => $this->print(),
      'sendAllEmails' => $this->accountPrint('sendAll', true),
      'archives'      => $this->sendInArchives(),
      'download'      => $this->download(ABO_ACCOUNT_NUMBER),
      'addToArchive'  => $this->addToArchive(),
      default         => $this->mainPage()
    };
  }

  protected function print()
  {
    $this->a->markExecution(Service::request()->_post('account', []));

    return $this->accountPrint();
  }

  protected function accountPrint($action = 'print', $send = false)
  {
    $accountIds = Service::request()->_post('account', []);
    view()->addPathToView('core\modules\accounts\views\\');
    $out   = $this->switchBack(Service::request()->_('account_type'));
    $out[] = view()->render('accountPrint', [
      'accountIds'     => $accountIds,
      'accountType'    => 6,
      'action'         => $action,
      'jsonAccountIds' => json_encode($accountIds),
      'send'           => $send,
      'formAction'     => 'accounts_view.php',
    ]);
    return $out;
  }

  function navigationYearsLineFitness($type, &$year, $fitness = false, $array = false)
  {
    $year = Service::request()->_('year', date('Y'));

    if ($year_periods = $this->a->getMaxMinDate($type, true)) {
      if ($year > date('Y', strtotime($year_periods['max_date'])) || $year < date('Y',
          strtotime($year_periods['min_date']))) {
        $year = date('Y', strtotime($year_periods['max_date']));
      }

      $s   = '';
      $out = $array ? [] : '';
      for ($y = date('Y', strtotime($year_periods['max_date'])); $y >= date('Y',
        strtotime($year_periods['min_date'])); $y--) {
        if ($array) {
          $out[$y] = (int)$y;
        } else {
          $out .= $s . ($year == $y ? ' <strong>' . $y . '</strong> '
              : ' <a href="account_fitness.php?action=' . ($type == 1 ? 'showAbo' . ($fitness ? 'Fitness' : '') . 'AccountsArchives' : ($type == 2 ? 'showOtherAccountsArchives' : ($type == 3 ? 'showPrepaymentAccountsArchives' : 'showAccountsArchives'))) . '&year=' . $y . '">' . $y . '</a> ') . "\n";
          $s   = '|';
        }
      }
    }

    return $out;
  }

  protected function switchBack($type, $archive = false)
  {
    //Выбираем куда вернуться после выполнения
    $prefix = $archive ? 'Archives' : 'List';
    switch ($type) {
      case 6:
        return $this->{'showAboFitnessAccounts' . $prefix}();
      default:
        return parent::switchBack($type, $archive);
    }
  }

  protected function addToArchive()
  {
    $accounts = Service::request()->_('account', []);
    $type     = Service::request()->_('account_type');
    if (!empty($accounts)) {
      if (is_array($accounts)) {
        $accounts = array_keys($accounts);
      } else {
        $accounts = [$accounts];
      }


      $this->a->addToArchiveAccounts($accounts, $type);
      $this->message = 'Rechnungen wurden gelöscht';
    }

    return $this->switchBack($type, true);
  }
}

$b                = new account_fitness();
$_page['content'] = $b->start();
$_page['key']     = $b->getPageKey();
$_page['js'][]    = 'accounts';
$_page['js'][]    = ['maskedinput', 'common', false, 'cdn'];
$_page['js'][]    = ['clients', 'admin', false, 'cdn'];
$_page['js'][]    = 'visualeditor/ckeditor';
$_page['js'][]    = 'popup';