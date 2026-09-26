<?php

use AC\app\entities\enums\AccountType;
use AC\app\entities\enums\AccountViewType;
use AC\core\modules\config\models\ConfigClubStateModel;
use AC\core\modules\config\models\ConfigModel;
use AC\core\modules\mailing\entities\enums\ModeTemplate;
use AC\core\modules\payment\payone\entities\enums\OnlineGateway;
use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\system\db\Query;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\helpers\TranslateHelper;
use AC\core\system\view\View;


require 'accounts_ext.php';


/** Административные операции со счетами и экспорт журналов. */
class accounts_admin extends accounts_ext
{
  public $currentType = [];
  /**
   * @var string
   */
  protected         $page_key;
  protected ?string $n;
  protected int     $bc;
  protected int     $an;
  protected string  $place_name;
  private ?string   $error;
  private ?string   $message;

  public function __construct()
  {
    //считываем концигурационные значения
    $temp = Query::sqlQuery('select * from ' . Query::tableName('config'));
    foreach ($temp as $item) {
      $this->config[$item['alias']] = $item['value'];
    }

    // Name of the bank account owner (Tennishallenbesitzer)
    $this->n = $this->config['name_bank'];
    // Bank code (BLZ)
    $this->bc = (int)$this->config['bank_code'];
    // Bank account number (Kontonr.)
    $this->an = (int)$this->config['bank_number'];

    $this->place_name = $this->config['account_attachment'];
    $structure2       = Service::structure();
    $menuTypes        = $structure2->getTypes();
    if (empty($_REQUEST['type'])) {
      $item             = current($menuTypes);
      $_REQUEST['type'] = $item['type_id'];
    }
    if ((isset($_REQUEST['type']) && isset($_SESSION['account_type_id'])) ||
      (isset($_REQUEST['type']) && !isset($_SESSION['account_type_id']))) {
      $_SESSION['account_type_id'] = $_REQUEST['type'];
    } elseif (!isset($_REQUEST['type']) && isset($_SESSION['account_type_id'])) {
      $_REQUEST['type'] = $_SESSION['account_type_id'];
    }
    if (isset($_REQUEST['action']) && !($_REQUEST['action'] == 'showAccountTextConfig' || $_REQUEST['action'] == 'editPdfTemplate')) {
      $_SESSION['account_type_action'] = $_REQUEST['action'];
    }
    foreach ($menuTypes as $item) {
      if ($item['type_id'] == $_REQUEST['type']) {
        $this->currentType = $item;
      }
    }
    parent::__construct();
  }

  function start()
  {
    switch (Service::request()->_('action')) {
      //Работа со счетами
      case 'downloadAccounts':
        $this->downloadAccounts();
        break;
      case 'downloadAboAccounts':
        $this->downloadAboAccounts();
        break;
      case 'downloadPrepaymentAccounts':
        $this->downloadPrepaymentAccounts();
        break;
      case 'downloadOtherAccounts':
        $this->downloadOtherAccounts();
        break;
      case 'downloadOnlinePaymentAccounts':
        $this->downloadOnlinePaymentAccounts();
        break;
      case 'generateAccount';
        return $this->generateAccounts();
        break;
      case 'showAccountsList':
        return $this->showAccountsList();
        break;
      case 'showAccountsArchives':
        return $this->showAccountsArchives();
        break;
      //Работа для Абонементов
      case 'generateAboAccount';
        return $this->generateAboAccounts();
        break;
      case 'showAboAccountsList':
        return $this->showAboAccountsList();
        break;
      case 'showAboAccountsArchives':
        return $this->showAboAccountsArchives();
        break;
      case 'generateLightAccount':
        return $this->generateLightAccount();
        break;
      //Дополнительные счета
      case 'showOtherAccountsList':
        return $this->showOtherAccountsList();
        break;
      case 'showOtherAccountsArchives':
        return $this->showOtherAccountsArchives();
        break;
      //Предоплата
      case 'generatePrepaymentAccount';
        return $this->generatePrepaymentAccounts();
        break;
      case 'showPrepaymentAccountsList':
        return $this->showPrepaymentAccountsList();
        break;
      case 'showPrepaymentAccountsArchives':
        return $this->showPrepaymentAccountsArchives();
        break;
      case 'showOnlinePaymentAccountsList':
        return $this->showOnlinePaymentAccountsList();
        break;
      case 'showOnlinePaymentAccountsArchives':
        return $this->showOnlinePaymentAccountsArchives();
        break;

      //Общие функции для счетов
      case 'markExecution':
        return $this->markExecution();
        break;
      case 'sendInArchives':
        return $this->sendInArchives();
        break;
      case 'deleteAccount':
        return $this->deleteAccount();
        break;
      //Текстовые блоки для счетов
      case 'showAccountTextConfig':
        return $this->showAccountTextConfig();
        break;
      case 'insertText':
        return $this->insertText();
        break;
      case 'editText':
        return $this->editText();
        break;
      case 'changeText':
        return $this->changeText();
        break;
      case 'removeText':
        return $this->removeText();
        break;

      //saveConfig
      case 'saveConfig':
        return $this->saveConfig();
        break;
      case 'changeAccountTextFields':
        return $this->changeAccountTextFields();
        break;
      //DTA
      case 'downloadAccountsDTA':
        $this->downloadAccountsDTA();
        break;
      case 'downloadAboAccountsDTA':
        $this->downloadAboAccountsDTA();
        break;
      case 'downloadPrepaymentAccountsDTA':
        $this->downloadPrepaymentAccountsDTA();
        break;
      case 'downloadOtherAccountsDTA':
        $this->downloadOtherAccountsDTA();
        break;

      //SEPA
      case 'downloadAccountsSEPA':
        $this->downloadAccountsSEPA();
        break;

      case 'editPdfTemplate':
        return $this->editPdfTemplate();
        break;
      case 'updatePdfTemplate':
        return $this->updatePdfTemplate();
        break;

      default:
        return $this->mainPage();
    }
  }

  //первая страница
  function mainPage()
  {
    $this->page_key = 'accounts_generate';

    $output = [];
    if (isset ($this->error)) {
      $output[] = '<p align="center" class="error">' . $this->error . "</p>\n";
    } elseif (isset ($this->mess)) {
      $output[] = '<p align="center">' . $this->mess . "</p>\n";
    }

    $out      = '<table align="center" cellpadding="10" cellspacing="10">' . "\n";
    $out      .= '<tr>' . "\n";
    $out      .= '<td valign="top">' . $this->generateAccountsForms() . '</td>' . "\n";
    $out      .= '<td valign="top">' . $this->generateAboAccountsForms() . '</td>' . "\n";
    $out      .= '<td valign="top">' . $this->generateOtherAccountsForms() . '</td>' . "\n";
    $out      .= '<td valign="top">' . $this->generatePrepaymentAccountsForms() . '</td>' . "\n";
    $out      .= '</tr>' . "\n";
    $out      .= '</table>' . "\n";
    $output[] = $out;

    return $output;
  }

  function generateAccounts()
  {
    if (isset($_POST['year']) && isset($_POST['month']) && isset($_POST['client_id']) && isset($_POST['type'])) {
      $clients   = $_POST['client_id'];
      $year      = $_POST['year'];
      $month     = $_POST['month'];
      $work_date = $year . '-' . $month . '-01';
      //Проверяем правильность даты
      if ($work_date == date('Y-m-d', strtotime($work_date))) {
        if (is_array($clients)) {
          //Дата начала и конца счета
          $date_start  = date('Y-m-d', strtotime($work_date));
          $date_finish = date('Y-m-d', strtotime($year . '-' . $month . '-' . date('t', strtotime($work_date))));
          //Берем данные всех клиентов для которых выставляется счет
          if ($this->r->clients->getClientsDataById($clients, $clients_data)) {
            //Проходим всех клиентов для которых выставляется счет
            foreach ($clients_data as $client_data) {
              if ($this->a->insertAccount(
                $client_data['client_id'],
                $client_data['name'],
                $client_data['surname'],
                $client_data['address'],
                $client_data['post_code'],
                $client_data['city'],
                $client_data['email'],
                $client_data['number'],
                $date_start,
                $date_finish,
                $_POST['text_config'],
                (int)$client_data['nds_rate'],
                [
                  $client_data['bank_iban'],
                  $client_data['bank_bic'],
                  $client_data['bank_sepa_referenz'],
                  $client_data['bank_sepa_mandat'],
                  $client_data['sepa_type'],
                  $client_data['sepa_standart'],
                ],
                [
                  $client_data['account_owner'],
                  $client_data['account_number'],
                  $client_data['bank_index'],
                  $client_data['bank_name'],
                ],
                $_POST['type']
              )) {
                $this->mess = lang('Account generate', 'message_success');
              } else {
                $this->error = lang('Account not generate', 'message_error');
              }
            }
          } else {
            $this->error = lang('Clients not found', 'message_error');
          }
        } else {
          $this->error = lang('Clients not found', 'message_error');
        }
      } else {
        $this->error = lang('Incorrect date', 'message_error');
      }
    } else {
      $this->error = lang('Incorrect query', 'message_error');
    }

    return $this->showAccountsList();
  }

  function generateAccountsForms()
  {
    $out = '<table align="center" border="0" cellspacing="8">' . "\n";
    $out .= '<tr><td valign="top">' . "\n";

    if (isset($_POST['year']) && isset($_POST['month'])) {
      $c_date = $_POST['year'] . '-' . $_POST['month'] . '-01';
    } else {
      $c_date = mktime(0, 0, 0, (date('m') - 1), 1, date('Y'));
      $c_date = date('Y-m-d', $c_date);
    }
    //годы
    $select_years = '<select name="year">' . "\n";
    for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++) {
      $select_years .= '<option value="' . $i . '"' . ($i == date(
          'Y',
          strtotime($c_date)
        ) ? ' selected' : '') . '>' . $i . '</option>' . "\n";
    }
    $select_years .= '</select>' . "\n";

    //месяцы
    $select_monthes = '<select name="month">' . "\n";
    if (date('Y', strtotime($c_date)) == date('Y')) {
      $end_monthes = date('m');
    } else {
      $end_monthes = 13;
    }

    for ($i = 1; $i < $end_monthes; $i++) {
      $select_monthes .= '<option value="' . sprintf("%02d", $i) . '"' . ($i == date(
          'm',
          strtotime($c_date)
        ) ? ' selected' : '') . '>' . sprintf("%02d", $i) . '</option>' . "\n";
    }
    $select_monthes .= '</select>' . "\n";

    //Клиенты
    if ($this->r->clients->getClientsDataByOrder(
      date('Y-m-d', strtotime($c_date)),
      Service::request()->_('type', $this->currentType['type_id']),
      $clients_data,
    )) {
      $clients = '<select name="client_id[]" MULTIPLE size="5" id="clientlist">' . "\n";
      foreach ($clients_data as $client_data) {
        $tmp = $client_data['surname'] . ' ' . $client_data['name'];
        if (strlen($tmp) > 35) {
          $tmp = substr($tmp, 0, 35) . '...';
        }
        $clients .= "<option value=\"" . $client_data['client_id'] . "\" " . (!isset($sel) ? 'selected' : '') . ">" . $tmp . "</option>\n";
        $sel     = true;
      }
      $clients .= '</select>' . "\n";
    }

    $out .= '<td valign="top">' . "\n";
    //Выставление счета
    $out .= '<table cellspacing="1" cellpadding="3" class="main" border="0">' . "\n";
    $out .= '	<tr><th colspan="2">' . lang('Individual invoices title', 'accounts', ['court_name' => $this->currentType["title"]]
      ) . '</th></tr>' . "\n";
    //Выбор даты
    $out .= '<form action="accounts.php?type=' . (Service::request()->_('type',
        module('areas')->useModel()->getFirstActiveType())) . '" method="post">' . "\n";
    $out .= '<input type="hidden" name="type" value="' . (Service::request()->_('type', module('areas')->useModel()->getFirstActiveType())) . '"/>';
    $out .= '	<tr><td class="light">' . lang(
        'Date'
      ) . ':</td><td class="light"><table border="0"><tr><td>' . $select_years . '</td><td>' . $select_monthes . "</td><td><input type=\"submit\" value=\"&gt;&gt;&gt;\" class=\"button\"/></td></tr></table></td></tr>\n";
    $out .= "</form>\n";
    //Выбор Клиентов имеющих заказы за эту дату
    if ($clients_data) {
      $out .= '<form action="accounts.php?action=generateAccount&type=' . (Service::request()->_('type',
          module('areas')->useModel()->getFirstActiveType())) . '" method="post">' . "\n";
      $out .= '<input type="hidden" name="type" value="' . (Service::request()->_('type', module('areas')->useModel()->getFirstActiveType())) . '"/>';
      $out .= '<input type="hidden" name="year" value="' . date('Y', strtotime($c_date)) . '" />' . "\n";
      $out .= '<input type="hidden" name="month" value="' . date('m', strtotime($c_date)) . '" />' . "\n";
      $out .= '	<tr><td class="dark">' . lang('Client') . ':</td><td class="dark">' . $clients . '</td></tr>' . "\n";
      $out .= '	<tr><td class="light">&nbsp;</td><td class="light"><a href="#" onclick="return selectClientList(\'clientlist\')">' . lang(
          'Mark all'
        ) . '</a></td></tr>' . "\n";
      $out .= '<tr><td class="dark">' . lang('Text') . ':</td><td class="dark">' . "\n";
      $out .= '<select name="text_config">' . "\n";
      $out .= '<option value="0">' . lang('no') . '</option>' . "\n";
      if ($text_blocks = $this->a->getTextBlocks()) {
        foreach ($text_blocks as $tb) {
          $out .= '<option value="' . $tb['config_id'] . '">' . $tb['title'] . '</option>' . "\n";
        }
      }
      $out .= '</select>' . "\n";
      $out .= '</td></tr>' . "\n";
      $out .= '	<tr><td class="dark" colspan="2" align="center"><input type="submit" value="' . lang(
          'Execute'
        ) . '" class="button"/></td></tr>' . "\n";
      $out .= "</form>\n";
    }
    $out .= "</table>\n";
    $out .= '</td>' . "\n";
    $out .= '</tr></table>' . "\n";

    return $out;
  }

  function downloadAccounts()
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
      'E-mail'              => lang('E-mail'),
    ];
    $out    = implode(';', $fields) . "\n";
    if ($accounts = $this->a->getAccounts(false, false, Service::request()->_('type', 1))) {
      foreach ($accounts as $a) {
        $nds_sum = 0;
        $nds_sum = $a['sum'] - ($a['sum'] / (1 + $a['nds'] / 100));

        $out .= join(
            ';',
            [
              config('accountView')->getNumberAccount($a['a_number'], ACCOUNT_NUMBER),
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
              number_format($a['sum'], 2, ',', ''),
              number_format(($a['sum'] - $nds_sum), '2', ',', ''),
              number_format($nds_sum, '2', ',', ''),
              $a['nds'] . ' %',
              $a['email'],
            ]

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

  /**
   * Выгрузить журнал счетов онлайн-оплаты в CSV.
   */
  public function downloadOnlinePaymentAccounts(): void
  {
    if ($accounts = $this->a->getOnlinePaymentAccounts()) {
      $accountDetails = $this->a->getOnlinePaymentAccountsById(array_column($accounts, 'account_id')) ?: [];
      $out = $this->buildOnlinePaymentAccountsCsv($accounts, $accountDetails);
      $fileName = 'accounts_online_payment_export_' . date('Y_m_d') . '.csv';
      header('Content-Disposition: attachment; filename=' . $fileName);
      header('Content-Type: application/x-force-download; name="' . $fileName . '"; charset=utf-8');
      echo "\xEF\xBB\xBF" . $out;
      die;
    }
  }

  /** Сформировать CSV со снимками дат игр из счетов онлайн-оплаты. */
  protected function buildOnlinePaymentAccountsCsv(array $accounts, array $accountDetails): string
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
      'E-mail'              => lang('E-mail'),
      'Booking/payment date' => lang('Booking/payment date', 'accounts'),
      'Occupancy/usage date' => lang('Occupancy/usage date', 'accounts'),
      'PayPal transaction ID' => lang('PayPal transaction ID', 'accounts'),
    ];
    $out    = implode(';', $fields) . "\n";

    foreach ($accounts as $account) {
      // Дата выставления совпадает с PDF; даты игр берутся из сохранённых позиций счёта.
      $invoiceDate = strtotime((string)($account['date_creation'] ?: $account['date_start']));
      $usageDates = [];
      foreach ($accountDetails[$account['account_id']]['reservations'] ?? [] as $reservation) {
        $usageDate = strtotime((string)($reservation['date'] ?? ''));
        if ($usageDate !== false) {
          $usageDates[date('Y-m-d', $usageDate)] = date('d.m.Y', $usageDate);
        }
      }
      ksort($usageDates);
      // Исторический префикс paypal| используется и у Payone; шлюз определяет общий парсер.
      $priceInfo = (string)($account['price_info'] ?? '');
      $paymentInfo = str_starts_with(strtolower($priceInfo), 'paypal|')
        ? OnlineGatewayService::parseOnlinePaymentInvoicePriceInfo($priceInfo)
        : null;
      $transactionId = ($paymentInfo['gateway'] ?? null) === OnlineGateway::PAYPAL->value
        ? $paymentInfo['transaction_id']
        : '';
      $vatAmount = $account['sum'] - ($account['sum'] / (1 + $account['nds'] / 100));
      $out .= implode(';', [
        config('accountView')->getNumberAccount($account['a_number'], ONLINE_PAYMENT_ACCOUNT_NUMBER),
        $account['name'],
        $account['surname'],
        $account['address'],
        $account['post_code'],
        $account['city'],
        $account['account_owner'],
        $account['bank_name'],
        $account['sepa_iban'],
        $account['sepa_bic'],
        trim($account['sepa_mndtid']),
        trim($account['sepa_dtofsgntr']),
        number_format($account['sum'], 2, ',', ''),
        number_format($account['sum'] - $vatAmount, 2, ',', ''),
        number_format($vatAmount, 2, ',', ''),
        $account['nds'] . ' %',
        $account['email'],
        $invoiceDate === false ? '' : date('d.m.Y', $invoiceDate),
        implode(', ', $usageDates),
        $transactionId,
      ]) . "\n";
    }

    return $out;
  }

  function navigationLine(AccountType $accountType, &$year, &$month, &$type, bool $archives): string
  {
    $type      = Service::request()->_('type', 1);
    $year      = Service::request()->_('year', null);
    $month     = Service::request()->_('month', false);
    $typeCourt = in_array($accountType, [AccountType::Individual, AccountType::Abo], true) ? (int)$type : null;
    if ($years = $this->a->getMonthByYearDate($accountType, $archives, $typeCourt)) {
      $action    = match ($accountType) {
        AccountType::Individual     => 'showAccounts' . ($archives ? 'Archives' : 'List'),
        AccountType::Abo            => 'showAboAccounts' . ($archives ? 'Archives' : 'List'),
        AccountType::Special        => 'showOtherAccounts' . ($archives ? 'Archives' : 'List'),
        AccountType::PrivateAccount => 'showPrepaymentAccounts' . ($archives ? 'Archives' : 'List'),
        AccountType::OnlinePayment  => 'showOnlinePaymentAccounts' . ($archives ? 'Archives' : 'List'),
        AccountType::MembershipFees => throw new LogicException('Membership fees use their own account navigation.'),
      };
      $action    .= '&type=' . $type;
      $outYears  = [];
      $outMonths = [];
      if (!$archives) {
        $outYears[]  = (!$year ? ' <strong>' . lang('All years', 'accounts') . '</strong> '
            : ' <a href="accounts.php?action=' . $action . '">' . lang('All years', 'accounts') . '</a> ') . "\n";
        $outMonths[] = (!$month ? ' <strong>' . lang('All months', 'accounts') . '</strong> '
            : ' <a href="accounts.php?action=' . $action . ($year ? '&year=' . $year : '') . '">' . lang('All months', 'accounts') . '</a> ') . "\n";
        if (!$year) {
          foreach ($years['all'] as $monthKey) {
            $outMonths[] = ($monthKey == $month ? ' <strong>' . TranslateHelper::translateMonth($monthKey) . '</strong> '
                : ' <a href="accounts.php?action=' . $action . ($year ? '&year=' . $year : '') . '&month=' . $monthKey . '">' . TranslateHelper::translateMonth($monthKey) . '</a> ') . "\n";
          }
        }
      }
      foreach ($years as $yearKey => $months) {
        if (!$year) {
          $year = $yearKey;
        }

        if ($yearKey != 'all') {
          if ($archives || count($years) > 2) {
            $outYears[] = ($yearKey == $year ? ' <strong>' . $yearKey . '</strong> '
                : ' <a href="accounts.php?action=' . $action . '&year=' . $yearKey . ($archives ? '&month=' . $months[0] : '') . '">' . $yearKey . '</a> ') . "\n";
          }
          if ($yearKey == $year) {

            if ($archives || count($months) < 2) {
              if (!$month) {
                $month = $months[0];
              }
              $outMonths = [];
            }
            foreach ($months as $monthKey) {
              $outMonths[] = ($monthKey == $month ? ' <strong>' . TranslateHelper::translateMonth($monthKey) . '</strong> '
                  : ' <a href="accounts.php?action=' . $action . '&year=' . $yearKey . '&month=' . $monthKey . '">' . TranslateHelper::translateMonth($monthKey) . '</a> ') . "\n";
            }
          }
        }
      }
      if ($year == 'all') {
        $year = false;
      }
      if ($month == 'all') {
        $month = false;
      }
      return '<div style="padding: 3px 0">' . implode(' | ',
          $outYears) . '</div>' . (USE_MONTHS_LIST_MENU_IN_ACCOUNTS ? '<div style="padding: 3px 0">' . implode(' | ',
            $outMonths) . '</div>' : '') . "\n";
    }
    return '';
  }

  function showAccountsList()
  {
    $this->page_key = 'accounts_list';

    $out = "<h1>" . lang('Single invoice journal', 'accounts') . " " . (!(defined('HIDE_ACCOUNT_TITLE_AREA') && HIDE_ACCOUNT_TITLE_AREA)
        ? ("(" . $this->currentType["title"] . ")") : "") . "</h1>\n";

    $out .= '<p>' . lang('text_about_archiving_invoices', 'accounts') . '</p>' . "\n";

    $out .= '<table align="center" border="0" cellspacing="1" cellpadding="3" class="main wide" width="80%">' . "\n";
    $out .= '<tr>
              <td class="light" colspan="8" align="right">' . "\n";
    if (config('account')->useSEPA()) {
      $out .= $this->sepaForm();
    }
    $out .= '</td>
					    </tr>' . "\n";
    if (USE_LIST_MENU_IN_ACCOUNTS_NOT_ARCHIVE) {
      $out .= '<tr><td class="dark" colspan="8">' . "\n";
      $out .= $this->navigationLine(AccountType::Individual, $year, $month, $type, false);
      $out .= '</td></tr>' . "\n";
    }
    $out .= '<tr>' . "\n";
    $out .= '<th width="10%"><input type="checkbox" name="all_select" value="0" onchange="selectAllAccount(this)"/>' . lang(
        'To mark'
      ) . '</th>' . "\n";
    $out .= '<th width="10%">' . lang('Invoice number', 'accounts') . '</th>' . "\n";
    $out .= '<th width="10%">' . lang('Date') . '</th>' . "\n";
    $out .= '<th width="20%">' . lang('Name-address', 'accounts') . '</th>' . "\n";
    $out .= '<th width="5%">' . lang('Status', 'accounts') . '</th>' . "\n";
    $out .= '<th width="20%">' . lang('Total amount', 'accounts') . ' (' . CURR_VALUTE . ')</th>' . "\n";
    if (config('account')->useSEPA()) {
      $out .= '<th width="5%">' . lang('SEPA_title') . '</th>' . "\n";
    }
    $out .= '<th width="15%">' . lang('Actions') . '</th>' . "\n";
    $out .= '</tr>' . "\n";
    if ($accounts = $this->a->getAccounts(false, $year, $type, $month)) {
      if (defined('USE_ACCOUNT_TEXT_FIELDS') && USE_ACCOUNT_TEXT_FIELDS) {
        $out .= '<div class="pupopWindowPlace" id="pupopWindowPlace"></div>';
        $out .= '<div class="pupopWindow" id="pupopWindow"></div>';
        $out .= "<script>var popup = new popupAjaxWindow('pupopWindowPlace', 'pupopWindow');</script>";
      }
      $out .= '<form action="accounts.php?action=markExecution&type=' . $type . '" method="POST" id="accounts_list">' . "\n";
      $out .= '<input type="hidden" name="account_type" value="1"/>' . "\n";
      $out .= '<input type="hidden" name="type" value="' . $type . '"/>' . "\n";
      foreach ($accounts as $a) {
        if ((!defined(ADMIN_HIDE_NULL_ACCOUNT) || ADMIN_HIDE_NULL_ACCOUNT) && $a['sum'] == 0 && $type == 2) {
          continue;
        }
        $out .= '<tr>' . "\n";
        $out .= '<td class="light" align="center"><input type="checkbox" name="account[]" value="' . $a['account_id'] . '"/></td>' . "\n";
        $out .= '<td class="' . ($a['execution'] ? 'select'
            : 'dark') . '" id="numberAccountBlock' . $a['account_id'] . '">' . config('accountView')->getNumberAccount($a['a_number'],
            ACCOUNT_NUMBER) . '</td>' . "\n";
        $out .= '<td class="light">' . TranslateHelper::translateMonth(date('n', strtotime($a['date_start']))) . ' ' . date(
            'Y',
            strtotime($a['date_start'])
          ) . '</td>' . "\n";
        $out .= '<td class="dark">' . $a['name'] . ' ' . $a['surname'] . '<br/>' . $a['address'] . ', ' . $a['post_code'] . ' ' . $a['city'] . '</td>' . "\n";
        $out .= '<td class="light">' . ((!empty($a['club_state'])) ? ((($a['club_state'] == 1) ? 'NM' : ('V' . ($a['club_state'] - 1))) . '/ ')
            : '') . $a['nds'] . '%</td>' . "\n";
        $out .= '<td class="light" align="right">' . NumberHelper::valute($a['sum']) . '</td>' . "\n";
        if (config('account')->useSEPA()) {
          $out .= '<td class="dark" valign="top" align="center">' . "\n";
          if (strlen($a['name'] ?? '') > 0 && strlen($a['sepa_iban'] ?? '') > 0 && strlen($a['sepa_bic'] ?? '') > 0 && strlen(
              $a['sepa_mndtid'] ?? ''
            ) > 0 && strlen(
              $a['sepa_dtofsgntr'] ?? ''
            ) > 0 && $a['sum'] > 0) {
            $out .= '<img src="' . base_url(paths()->getAssetsDir('images/bullet_green.gif')) . '"/>';
          } else {
            $out .= '<img src="' . base_url(paths()->getAssetsDir('images/bullet_red.gif')) . '"/>';
          }
          $out .= '</td>' . "\n";
        }
        $out .= '<td class="dark">';
        if (defined('USE_ACCOUNT_TEXT_FIELDS') && USE_ACCOUNT_TEXT_FIELDS) {
          $out .= '<a href="#" onclick="return popup.createWindow(\'get_ajax_data.php?action=getAccountTextForm&account_id=' . $a['account_id'] . '&account_type=1&type=' . $type . '\')" class="btnEdit" ' . (!empty($a['text_fields'])
              ? 'style="color:#0bbe2a"' : '') . '>' . lang('text_fields', 'accounts_view') . '</a><br />';
        }
        $out .= '<a href="accounts_view.php?account=' . $a['account_id'] . '&account_type=1&type=' . $type . '" target="_blank" class="btnView">' . lang(
            'View'
          ) . '</a><br />
					       <a href="accounts_view.php?account=' . $a['account_id'] . '&account_type=1&type=' . $type . '&action=send" target="_blank" onclick="return markExecution(this, \'numberAccountBlock' . $a['account_id'] . '\')" class="btnMail" ' . ($a['send'] == 1
            ? 'style="color:#0712bb"' : '') . ' id="linkSendEmailAccount' . $a['account_id'] . '">' . lang(
            'Send as email',
            'accounts'
          ) . '</a></td>' . "\n";
        $out .= '</tr>' . "\n";
      }

      $out .= '<tr><td class="light" colspan="9" align="right">
							<input type="submit" data-type="' . $type . '" name="print" value="' . lang('Print') . '" class="button"/>
							<input type="button" data-type="' . $type . '" name="archives" value="' . lang('Archive') . '" onClick="sendInArchives()"  class="button"/>
							<input type="button" data-type="' . $type . '" name="download" value="' . lang('Export RE Journal (CSV)', 'accounts') . '" onClick="downloadAccounts(\'simple\')"  class="button"/>
							<input type="submit" data-type="' . $type . '" name="send-email" value="' . lang('Send as email', 'accounts') . '" class="button" 
  						  onClick="return ifConfirm(\'' . lang('confirm_send_email_account', 'js_confirm') . '\')"/> 
					</td></tr>' . "\n";
      $out .= '</form>' . "\n";
    }
    $out      .= '</table>' . "\n";
    $output[] = $out;

    return $output;
  }


  function showAccountsArchives()
  {
    $this->page_key = 'accounts_archives';

    $out = "<h1>" . lang('Individual invoice archive', 'accounts') . (!(defined('HIDE_ACCOUNT_TITLE_AREA') && HIDE_ACCOUNT_TITLE_AREA)
        ? ("(" . $this->currentType["title"] . ")") : "") . "</h1>\n";

    $out .= '<table align="center" border="0" cellspacing="1" cellpadding="3" class="main wide" width="80%">' . "\n";

    $out .= '<tr><td class="dark" colspan="8">' . "\n";
    $out .= $this->navigationLine(AccountType::Individual, $year, $month, $type, true);
    $out .= '</td></tr>' . "\n";

    $out .= '<tr>' . "\n";
    $out .= '<th width="10%">' . lang('Invoice number', 'accounts') . '</th>' . "\n";
    $out .= '<th width="10%">' . lang('Date') . '</th>' . "\n";
    $out .= '<th width="25%">' . lang('Name-address', 'accounts') . '</th>' . "\n";
    $out .= '<th width="5%">' . lang('Status', 'accounts') . '</th>' . "\n";
    $out .= '<th width="20%">' . lang('Total amount', 'accounts') . ' (' . CURR_VALUTE . ')</th>' . "\n";
    $out .= '<th colspan="3" width="30%">' . lang('Actions') . '</th>' . "\n";
    $out .= '</tr>' . "\n";

    if ($accounts = $this->a->getAccounts(true, $year, $type, $month)) {
      foreach ($accounts as $a) {
        $out .= '<tr>' . "\n";
        $out .= '<td class="' . ($a['deleted'] ? 'selectDel' : 'dark') . '">' . config('accountView')->getNumberAccount($a['a_number'],
            ACCOUNT_NUMBER) . '</td>' . "\n";
        $out .= '<td class="light">' . TranslateHelper::translateMonth(date('n', strtotime($a['date_start']))) . ' ' . date(
            'Y',
            strtotime($a['date_start'])
          ) . '</td>' . "\n";
        $out .= '<td class="dark">' . $a['name'] . ' ' . $a['surname'] . '<br/>' . $a['address'] . ', ' . $a['post_code'] . ' ' . $a['city'] . '</td>' . "\n";
        $out .= '<td class="light">' . ((!empty($a['club_state'])) ? ((($a['club_state'] == 1) ? 'NM' : ('V' . ($a['club_state'] - 1))) . '/ ')
            : '') . $a['nds'] . '%</td>' . "\n";
        $out .= '<td class="light" align="right">' . NumberHelper::valute($a['sum']) . '</td>' . "\n";
        $out .= '<td class="dark">' . ($a['deleted']
            ? '<a href="accounts_view.php?account_delete=' . $a['account_id'] . '&account_type=1&type=' . $type . '" target="_blank" class="btnConfirmationDelete">' . lang(
              'To the credit',
              'accounts'
            ) . '</a>'
            : '') . '</td>
						<td class="dark"><a href="accounts_view.php?account=' . $a['account_id'] . '&account_type=1&type=' . $type . '" target="_blank" class="btnView">' . lang(
            'View'
          ) . '</a></td>
						<td class="dark"><a href="accounts.php?action=deleteAccount&account=' . $a['account_id'] . '&account_type=1&type=' . $type . '" class="btnRemove" onClick="return ifConfirm()">' . lang(
            'Cancel',
            'accounts'
          ) . '</a></td>' . "\n";
        $out .= '</tr>' . "\n";
      }
    }


    $out .= '</table>' . "\n";

    $output[] = $out;

    return $output;
  }

//################ АБОНЕМЕНТЫ #########################
  function generateAboAccountsForms()
  {
    $out = '<table align="center" border="0" cellspacing="8">' . "\n";
    $out .= '<tr><td valign="top">' . "\n";

    if (isset($_POST['client_id'])) {
      $client_id = (int)$_POST['client_id'];
    }
    $type = Service::request()->_get('type', module('areas')->useModel()->getFirstActiveType());
    //Клиенты
    $select_clients = '';
    if ($this->r->clients->getClientsDataByAbo($clients_data, $type)) {
      $select_clients = '<select name="client_id">' . "\n";
      foreach ($clients_data as $c_id => $client_data) {
        if (!isset($client_id)) {
          $client_id = $c_id;
        }
        $tmp = $client_data['surname'] . ' ' . $client_data['name'];
        if (strlen($tmp) > 35) {
          $tmp = substr($tmp, 0, 35) . '...';
        }
        $select_clients .= "<option value=\"" . $c_id . "\" " . ($client_id == $c_id ? 'selected' : '') . ">" . $tmp . "</option>\n";

        if ($client_id == $c_id && is_array($client_data['ticket'])) {
          $select_ticket = '<table class="main" cellspacing="1" cellpadding="3">' . "\n";
          $sel           = 'checked';
          foreach ($client_data['ticket'] as $ticket_id => $ticket) {
            $this->r->areas->getAreaData($ticket['area_id'], $area);
            if ($area['type_id'] != $type) {
              continue;
            }
            $weekdays      = explode(',', $ticket['weekdays']);
            $select_ticket .= "<tr>\n";
            $select_ticket .= "<td class=\"dark\"><input type=\"checkbox\" name=\"ticket_id[]\" value=\"" . $ticket_id . "\" " . $sel . "/></td>\n";
            $select_ticket .= '<td class="dark">' . $this->r->areas->getTitleByAreaId(
                $ticket['area_id']
              ) . '-' . $ticket['area'] . '</td>' . "\n";
            $select_ticket .= '<td  class="dark">' . date(
                'H:i',
                strtotime($ticket['time_start'])
              ) . '-' . TimeHelper::convertTime24($ticket['time_finish'], false) . '</td>' . "\n";
            $select_ticket .= '<td class="dark">';
            foreach ($weekdays as $weekday) {
              $select_ticket .= TranslateHelper::translateWeekday($weekday, true) . ' ';
            }
            $select_ticket .= '</td>' . "\n";
            $select_ticket .= "</tr>\n";
            $sel           = '';
          }
          $select_ticket .= '</table>' . "\n";
        }
      }
      $select_clients .= '</select>' . "\n";
    }

    $out .= '<td valign="top">' . "\n";
    //Выставление счета
    $out .= '<table cellspacing="1" cellpadding="3" class="main" border="0">' . "\n";
    $out .= '	<tr><th colspan="2">' . lang('Subscription invoices', 'accounts') . ' (' . $this->currentType["title"] . ')</th></tr>' . "\n";
    //Выбор Клиента
    $out .= '<form action="accounts.php?type=' . $type . '" method="post">' . "\n";
    $out .= '<input type="hidden" name="type" value="' . $type . '"/>';
    $out .= '<tr><td class="light">' . lang(
        'Client'
      ) . ':</td><td class="light"><table border="0"><tr><td>' . $select_clients . '</td><td><input type="submit" value="&gt;&gt;&gt;" class="button"/></td></tr></table></td></tr>' . "\n";

    $out .= "</form>\n";
    //Выбор Клиентов имеющих заказы за эту дату
    if ($clients_data) {
      $out .= '<form action="accounts.php?action=generateAboAccount&type=' . $type . '" method="post">' . "\n";
      $out .= '<input type="hidden" name="client_id" value="' . $client_id . '"/>' . "\n";
      $out .= '<input type="hidden" name="type" value="' . $type . '"/>';
      $out .= '<tr><td class="light" style="padding:0px; margin:0px;" colspan="2">' . $select_ticket . '</td></tr>' . "\n";
      $out .= '<tr><td class="light">' . lang(
          'Change Total price',
          'accounts'
        ) . ':</td><td class="light"><input type="text" name="abo_sum" value="0,00"/>' . "\n";
      $out .= '<tr><td class="dark">' . lang('Text') . ':</td><td class="dark">' . "\n";
      $out .= '<select name="text_config" class="account-text">' . "\n";
      $out .= '<option value="0">' . lang('no') . '</option>' . "\n";
      if ($text_blocks = $this->a->getTextBlocks()) {
        foreach ($text_blocks as $tb) {
          $out .= '<option value="' . $tb['config_id'] . '">' . $tb['title'] . '</option>' . "\n";
        }
      }
      $out .= '</select>' . "\n";
      $out .= '</td></tr>' . "\n";
      $out .= '<tr><td class="light" colspan="2" align="center"><input type="submit" value="' . lang(
          'Execute'
        ) . '" class="button"/></td></tr>' . "\n";
      $out .= "</form>\n";
    }
    $out .= "</table>\n";
    $out .= '</td>' . "\n";
    $out .= '</tr></table>' . "\n";

    return $out;
  }

  function generateAboAccounts()
  {
    if (isset($_POST['client_id']) && isset($_POST['ticket_id'])) {
      $abo_sum = 0;
      if (isset($_POST['abo_sum'])) {
        $abo_sum = NumberHelper::float($_POST['abo_sum']);
      }

      $client_id = (int)$_POST['client_id'];
      $tickets   = $_POST['ticket_id'];

      foreach ($tickets as $ticket_id) {
        if ($this->r->tickets->getTicketFullDataWithPeriodsAndPriceById($ticket_id, $ticket_data, $periods_data)) {
          if ($this->r->clients->getClientData($client_id, $client_data)) {
            foreach ($periods_data as $k => $pd) {
              if (is_array($pd['count_game'])) {
                foreach ($pd['count_game'] as $weekday => $games) {
                  if (is_array($games)) {
                    foreach ($games as $g_date => $t_periods) {
                      if (is_array($t_periods)) {
                        foreach ($t_periods as $start => $count) {
                          if ($count) {
                            if ($p_id = $this->r->areas->getPeriodByDate($g_date)) {
                              $periods_data[$k]['sum_price']                                          += $pd['price'][$weekday][$start][$p_id];
                              $tmp_price_info[$weekday][$g_date][$start]['price']                     = $pd['price'][$weekday][$start][$p_id];
                              $tmp_price_info[$weekday][$g_date][$start]['net_price']                 = $ticket_data['price_info'][$weekday][$start][$p_id];
                              $tmp_price_info[$weekday][$g_date][$start]['extra']                     = $ticket_data['extra'];
                              $tmp_price_info[$weekday][$g_date][$start]['discount_client']           = $ticket_data['discount_client'];
                              $tmp_price_info[$weekday][$g_date][$start]['discount_client_dimension'] = $ticket_data['discount_client_dimension'];
                              $tmp_price_info[$weekday][$g_date][$start]['discount_ticket']           = $ticket_data['discount_ticket'];
                              $tmp_price_info[$weekday][$g_date][$start]['discount_ticket_dimension'] = $ticket_data['discount_ticket_dimension'];
                            }
                          }
                        }
                      }
                    }
                  }
                }
              }
              $count_game[date('Y-m-d', strtotime($pd['start']))] = $pd['count_game'];
            }

            if ($this->a->insertAboAccount(
              $client_data['client_id'],
              $client_data['name'],
              $client_data['surname'],
              $client_data['address'],
              $client_data['post_code'],
              $client_data['city'],
              $client_data['email'],
              $client_data['number'],
              $ticket_data['time_start'],
              $ticket_data['time_finish'],
              $_POST['text_config'],
              $ticket_data['weekdays'],
              $this->r->areas->getTitleByAreaId($ticket_data['area_id']),
              $ticket_data['area'],
              (int)$client_data['nds_rate'],
              $ticket_data['ticket_id'],
              $abo_sum,
              [
                $client_data['bank_iban'],
                $client_data['bank_bic'],
                $client_data['bank_sepa_referenz'],
                $client_data['bank_sepa_mandat'],
                $client_data['sepa_type'],
                $client_data['sepa_standart'],
              ],
              [
                $client_data['account_owner'],
                $client_data['account_number'],
                $client_data['bank_index'],
                $client_data['bank_name'],
              ],
              (!empty($count_game) ? serialize($count_game) : ''),
              (!empty($tmp_price_info) ? serialize($tmp_price_info) : ''),
              $ticket_data['title'],
              $periods_data,
              $ticket_data['area_id']
            )) {
              $this->mess = lang('Account generate', 'message_success');
            } else {
              $this->error = lang('Account not generate', 'message_error');
            }
          } else {
            $this->error = lang('Clients not found', 'message_error');
          }
        } else {
          $this->error = lang('Ticket not found', 'message_error');
        }
      }
    } else {
      $this->error = lang('Incorrect query', 'message_error');
    }

    return $this->showAboAccountsList();
  }

  function generateLightAccount()
  {
    if (isset($_GET['account'])) {
      $account_id = (int)$_GET['account'];

      if ($account_data = $this->a->getAboAccountsById([$account_id])) {
        //проходим весь абонемент и смотри когда есть игры со светом
        foreach ($account_data as $account) {
          //берем игры когда свет горел
          $light_heating_data = $this->r->tickets->getLightHeatingTicketDataByTicketId($account['ticket_id']);

          if (is_array($account['reservations'])) {
            foreach ($account['reservations'] as $reservation) {
              $weekdays = explode(',', $reservation['weekdays']);
              if (is_array($reservation['periods'])) {
                foreach ($reservation['periods'] as $period) {
                  $current_date = strtotime($period['date_start']);
                  $finish_date  = strtotime($period['date_finish']);
                  while ($current_date <= $finish_date) {
                    if (in_array(CalendarHelper::getWeekdayByUnixtime($current_date), $weekdays)) {
                      if (isset(
                        $light_heating_data[1][date(
                          'Y-m-d',
                          $current_date
                        )][CalendarHelper::getWeekdayByUnixtime($current_date)][$reservation['time_start']]
                      )) {
                        $light_periods[] = [
                          date('Y-m-d', $current_date),
                          $reservation['time_start'],
                          array_sum(
                            $light_heating_data[1][date(
                              'Y-m-d',
                              $current_date
                            )][CalendarHelper::getWeekdayByUnixtime($current_date)]
                          ),
                          $reservation['type'],
                          $reservation['area'],
                        ];
                      }

                      if (isset(
                        $light_heating_data[2][date(
                          'Y-m-d',
                          $current_date
                        )][CalendarHelper::getWeekdayByUnixtime($current_date)][$reservation['time_start']]
                      )) {
                        $heating_periods[] = [
                          date('Y-m-d', $current_date),
                          $reservation['time_start'],
                          array_sum(
                            $light_heating_data[2][date(
                              'Y-m-d',
                              $current_date
                            )][CalendarHelper::getWeekdayByUnixtime($current_date)]
                          ),
                          $reservation['type'],
                          $reservation['area'],
                        ];
                      }
                    }
                    $current_date = mktime(
                      0,
                      0,
                      0,
                      date('m', $current_date),
                      (date('d', $current_date) + 1),
                      date('Y', $current_date)
                    );
                  }

                  if (isset($light_periods) && is_array($light_periods)) {
                    $this->a->insertLightAboReservationsAccount($account_id, 1, $light_periods);
                  }
                  if (isset($heating_periods) && is_array($heating_periods)) {
                    $this->a->insertLightAboReservationsAccount($account_id, 2, $heating_periods);
                  }
                }
              } else {
                $this->error = lang('periods not found', 'message_error');
              }
            }
          } else {
            $this->error = lang('reservations not found', 'message_error');
          }
        }
      } else {
        $this->error = lang('Account not found', 'message_error');
      }
    } else {
      $this->error = lang('Account not found', 'message_error');
    }

    return $this->showAboAccountsList();
  }

  function downloadAboAccounts()
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
      'E-mail'              => lang('E-mail'),
    ];
    $out    = implode(';', $fields) . "\n";
    if ($accounts = $this->a->getAboAccounts(false, false, false, isset($_REQUEST["type"]) ? $_REQUEST["type"] : '1')) {
      foreach ($accounts as $a) {
        $sum     = 0;
        $nds_sum = 0;

        if (is_array($a['reservations'])) {
          foreach ($a['reservations'] as $p) {
            $sum += $p['price'];
          }
        }
        if ($a['abo_sum'] > 0) {
          $sum = $a['abo_sum'];
        }

        $nds_sum = $sum - ($sum / (1 + $a['nds'] / 100));

        $out .= join(
            ';',
            [
              config('accountView')->getNumberAccount($a['a_number'], ABO_ACCOUNT_NUMBER),
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
              $a['email'],
            ]

          ) . "\n";
      }
      header("Content-Disposition: attachment; filename=accounts_abo_export_" . date('Y_m_d') . ".csv");
      header(
        "Content-Type: application/x-force-download; name=\"accounts_abo_export_" . date(
          'Y_m_d'
        ) . ".csv\"; charset=utf-8"
      );
      echo "\xEF\xBB\xBF" . $out;
      die;
    }
  }

  function showAboAccountsList()
  {
    $this->page_key = 'accounts_tickets_list';

    $output = [];
//    $this->a->updatePriceInfoAndCountGameInAllAboAccounts();

    if (isset ($this->error)) {
      $output[] = '<p align="center" class="error">' . $this->error . "</p>\n";
    } elseif (isset ($this->mess)) {
      $output[] = '<p align="center">' . $this->mess . "</p>\n";
    }

    $out = "<h1>" . lang('Subscription invoice journal', 'accounts') . " " . (!(defined('HIDE_ACCOUNT_TITLE_AREA') && HIDE_ACCOUNT_TITLE_AREA)
        ? ("(" . $this->currentType["title"] . ")")
        : "") . "</h1>\n";

    $out .= '<p>' . lang('text_about_archiving_invoices', 'accounts') . '</p>' . "\n";

    $out .= '<table align="center" border="0" cellspacing="1" cellpadding="3" class="main wide" width="80%">' . "\n";
    $out .= '<tr>
              <td class="light" colspan="8" align="right">' . "\n";
    if (config('account')->useSEPA()) {
      $out .= $this->sepaForm('abo');
    }
    $out .= '</td>
					    </tr>' . "\n";
    if (USE_LIST_MENU_IN_ACCOUNTS_NOT_ARCHIVE) {
      $out .= '<tr><td class="dark" colspan="8">' . "\n";
      $out .= $this->navigationLine(AccountType::Abo, $year, $month, $type, false);
      $out .= '</td></tr>' . "\n";
    }
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
    if ($accounts = $this->a->getAboAccounts(false, $year, $month, $type)) {
      if (defined('USE_ACCOUNT_TEXT_FIELDS') && USE_ACCOUNT_TEXT_FIELDS) {
        $out .= '<div class="pupopWindowPlace" id="pupopWindowPlace"></div>';
        $out .= '<div class="pupopWindow" id="pupopWindow"></div>';
        $out .= "<script>var popup = new popupAjaxWindow('pupopWindowPlace', 'pupopWindow');</script>";
      }

      $out .= '<form action="accounts.php?action=markExecution&type=' . $type . '" method="POST" id="accounts_list">' . "\n";
      $out .= '<input type="hidden" name="account_type" value="2"/>' . "\n";
      $out .= '<input type="hidden" name="type" value="' . $type . '"/>' . "\n";
      $out .= $nav;
      foreach ($accounts as $account_id => $a) {
        $outTemp = '';
        $sum     = 0;
        if (!empty($a['count_game'])) {
          $count_game = unserialize($a['count_game'], ['allowed_classes' => false]);
        }
        if (!empty($a['price_info'])) {
//          $price_info = unserialize($a['price_info'], ['allowed_classes' => false]);
        }

        $outTemp  .= '<tr>' . "\n";
        $outTemp  .= '<td class="light" align="center" valign="top"><input type="checkbox" name="account[]" value="' . $account_id . '"/></td>' . "\n";
        $outTemp  .= '<td class="' . ($a['execution'] ? 'select'
            : 'dark') . '" id="numberAccountBlock' . $account_id . '" valign="top">' . config('accountView')->getNumberAccount($a['a_number'],
            ABO_ACCOUNT_NUMBER) . '</td>' . "\n";
        $outTemp  .= '<td class="light">' . "\n";
        $outTemp  .= $a['type'] . ' ' . $a['area'] . ' ' . date(
            'H:i',
            strtotime($a['time_start'])
          ) . '-' . TimeHelper::convertTime24(
            $a['time_finish'],
            false
          ) . ' ' . lang('Clock') . "\n";
        $weekdays = explode(',', $a['weekdays']);
        foreach ($weekdays as $weekday) {
          $outTemp .= TranslateHelper::translateWeekday($weekday, true) . ' ';
        }
        if (is_array($a['reservations'])) {
          $outTemp         .= '<div>' . lang('Time period') . ':</div>' . "\n";
          $outTemp         .= '<div>';
          $max_finish_date = false;

          foreach ($a['reservations'] as $p) {
            $sum     += $p['price'];
            $outTemp .= date('d.m.Y', strtotime($p['date_start'])) . ' - ' . date(
                'd.m.Y',
                strtotime($p['date_finish'])
              ) . "<br/>\n";
            if (!$max_finish_date || $max_finish_date < date('Y-m-d', strtotime($p['date_finish']))) {
              $max_finish_date = date('Y-m-d', strtotime($p['date_finish']));
            }
          }
          $outTemp .= '</div>' . "\n";
        }
        $sum = ($a['abo_sum'] > 0 ? $a['abo_sum'] : $sum);
        if ($sum == 0 && ((isset($_REQUEST["type"]) ? $_REQUEST["type"] : '1') == 2)) {
          continue;
        }
        $outTemp .= '</td>' . "\n";
        $outTemp .= '<td class="dark">' . $a['name'] . ' ' . $a['surname'] . '<br/>' . $a['address'] . ', ' . $a['post_code'] . ' ' . $a['city'] . '</td>' . "\n";
        $outTemp .= '<td class="light" style="white-space: nowrap">' . ((!empty($a['club_state'])) ? ((($a['club_state'] == 1) ? 'NM'
              : ('V' . ($a['club_state'] - 1))) . '/ ') : '') . $a['nds'] . '%</td>' . "\n";
        $outTemp .= '<td class="light" align="right" valign="top">'
          . NumberHelper::valute($sum) . '</td>' . "\n";
        if (config('account')->useSEPA()) {
          if (strlen($a['name']) > 0 && strlen($a['sepa_iban']) > 0 && strlen($a['sepa_bic']) > 0 && strlen($a['sepa_mndtid']) > 0 && strlen($a['sepa_dtofsgntr']) > 0 && $sum > 0) {
            $color = 'green';
          } else {
            $color = 'red';
          }
          $outTemp .= '<td class="dark" valign="top" align="center">' . "\n";
          $outTemp .= '<img src="' . base_url(paths()->getAssetsDir('images/bullet_' . $color . '.gif')) . '"/>';
          $outTemp .= '</td>' . "\n";
        }
        $outTemp .= '<td class="dark" valign="top">';
        if (defined('USE_ACCOUNT_TEXT_FIELDS') && USE_ACCOUNT_TEXT_FIELDS) {
          $outTemp .= '<a href="#" onclick="return popup.createWindow(\'get_ajax_data.php?action=getAccountTextForm&account_id=' . $account_id . '&account_type=2&type=' . $type . '\')" class="btnEdit" ' . (!empty($a['text_fields'])
              ? 'style="color:#0bbe2a"' : '') . '>' . lang('text_fields', 'accounts_view') . '</a><br />';
        }

        $outTemp .= '<a href="accounts_view.php?account=' . $account_id . '&account_type=2&type=' . $type . '" target="_blank" class="btnView">' . lang(
            'View'
          ) . '</a><br />
						<a href="accounts_view.php?account=' . $account_id . '&account_type=2&type=' . $type . '&action=send" target="_blank" onclick="return markExecution(this, \'numberAccountBlock' . $account_id . '\')" class="btnMail"  ' . ($a['send'] == 1
            ? 'style="color:#0712bb"' : '') . ' id="linkSendEmailAccount' . $account_id . '">' . lang('Send as email', 'accounts') . '</a>
					</td>' . "\n";
        if (isset($a['light_reservations'])) {
          $outTemp .= '<td class="dark" valign="top"><a href="accounts_view.php?account=' . $account_id . '&account_type=21" target="_blank" class="btnView">'
            . lang('To the light/heating bill', 'accounts') . '</a></td>' . "\n";
        } else {
          if ($max_finish_date < date('Y-m-d')) {
            $outTemp .= '<td class="dark" valign="top"><a href="accounts.php?action=generateLightAccount&account=' . $account_id . '&type=' . (isset($_REQUEST["type"])
                ? $_REQUEST["type"] : '1') . '" class="btnView">' . lang('Accounting for light/heating', 'accounts') . '</a></td>' . "\n";
          } else {
            $outTemp .= '<td class="dark" valign="top">&nbsp;</td>' . "\n";
          }
        }
        $outTemp .= '</tr>' . "\n";
        $out     .= $outTemp;
      }

      $out .= '<tr><td class="light" colspan="10" align="right">
							<input type="submit" data-type="' . $type . '" name="print" value="' . lang('Print') . '" class="button"/>
							<input type="button" data-type="' . $type . '" name="archives" value="' . lang('Archive') . '" onClick="return sendInArchives()"  class="button"/>
							<input type="button" data-type="' . $type . '" name="download" value="' . lang('Export RE Journal (CSV)', 'accounts') . '" onClick="downloadAccounts(\'abo\')"  class="button"/>
							<input type="submit" data-type="' . $type . '" name="send-email" value="' . lang(
          'Send as email',
          'accounts'
        ) . '" class="button" onClick="return ifConfirm(\'' . lang(
          'confirm_send_email_account',
          'js_confirm'
        ) . '\')"/> 
					</td></tr>' . "\n";
      $out .= '</form>' . "\n";
    }
    $out      .= '</table>' . "\n";
    $output[] = $out;

    return $output;
  }

  //Архив счетов
  function showAboAccountsArchives()
  {
    $this->page_key = 'accounts_tickets_archives';

    $out = "<h1>" . lang('Subscription invoice archive', 'accounts') . " " . (!(defined('HIDE_ACCOUNT_TITLE_AREA') && HIDE_ACCOUNT_TITLE_AREA)
        ? ("(" . $this->currentType["title"] . ")")
        : "") . "</h1>\n";

    $out .= '<table align="center" border="0" cellspacing="1" cellpadding="3" class="main wide" width="80%">' . "\n";

    $out .= '<tr><td class="dark" colspan="8">' . "\n";
    $out .= $this->navigationLine(AccountType::Abo, $year, $month, $type, true);
    $out .= '</td>' . "\n";

    $out .= '<tr>' . "\n";
    $out .= '<th>' . lang('Invoice number', 'accounts') . '</th>' . "\n";
    $out .= '<th>' . lang('Date') . '</th>' . "\n";
    $out .= '<th>' . lang('Name-address', 'accounts') . '</th>' . "\n";
    $out .= '<th>' . lang('Status', 'accounts') . '</th>' . "\n";
    $out .= '<th>' . lang('Total amount', 'accounts') . ' (' . CURR_VALUTE . ')</th>' . "\n";
    $out .= '<th colspan="3">' . lang('Actions') . '</th>' . "\n";
    $out .= '</tr>' . "\n";
    if ($accounts = $this->a->getAboAccounts(true, $year, $month, $type)) {
      foreach ($accounts as $account_id => $a) {
        $sum      = 0;
        $out      .= '<tr>' . "\n";
        $out      .= '<td class="' . ($a['deleted'] ? 'selectDel' : 'dark') . '" valign="top">' . config('accountView')->getNumberAccount($a['a_number'],
            ABO_ACCOUNT_NUMBER) . '</td>' . "\n";
        $out      .= '<td class="light"  valign="top">' . "\n";
        $out      .= $a['type'] . ' ' . $a['area'] . ' ' . date(
            'H:i',
            strtotime($a['time_start'])
          ) . '-' . TimeHelper::convertTime24(
            $a['time_finish'],
            false
          ) . ' ' . lang('Clock') . "\n";
        $weekdays = explode(',', $a['weekdays']);
        foreach ($weekdays as $weekday) {
          $out .= TranslateHelper::translateWeekday($weekday, true) . ' ';
        }
        if (is_array($a['reservations'])) {
          $out .= '<div>' . lang('Time period') . ':</div>' . "\n";
          $out .= '<div>';
          foreach ($a['reservations'] as $p) {
            $sum += $p['price'];
            $out .= date('d.m.Y', strtotime($p['date_start'])) . ' - ' . date(
                'd.m.Y',
                strtotime($p['date_finish'])
              ) . "<br/>\n";
          }
          $out .= '</div>' . "\n";
        }
        $sum = ($a['abo_sum'] > 0 ? $a['abo_sum'] : $sum);
        $out .= '</td>' . "\n";
        $out .= '<td class="dark">' . $a['name'] . ' ' . $a['surname'] . '<br/>' . $a['address'] . ', ' . $a['post_code'] . ' ' . $a['city'] . '</td>' . "\n";
        $out .= '<td class="light">' . ((!empty($a['club_state'])) ? ((($a['club_state'] == 1) ? 'NM' : ('V' . ($a['club_state'] - 1))) . '/ ')
            : '') . $a['nds'] . '%</td>' . "\n";
        $out .= '<td class="light" align="right"  valign="top">' . NumberHelper::valute($sum) . '</td>' . "\n";
        $out .= '<td class="dark" valign="top">' . ($a['deleted']
            ? '<a href="accounts_view.php?account_delete=' . $account_id . '&account_type=2" target="_blank" class="btnConfirmationDelete">' . lang(
              'To the credit',
              'accounts'
            ) . '</a>'
            : '') . '</td>
						<td class="dark" valign="top"><a href="accounts_view.php?account=' . $account_id . '&account_type=2" target="_blank" class="btnView">' . lang(
            'View'
          ) . '</a></td>
						<td class="dark" valign="top"><a href="accounts.php?action=deleteAccount&account=' . $account_id . '&account_type=2&type=' . (isset($_REQUEST["type"])
            ? $_REQUEST["type"] : '1') . '" class="btnRemove" onClick="return ifConfirm()">' . lang('Cancel', 'accounts') . '</a></td>' . "\n";
        $out .= '</tr>' . "\n";
      }
    }
    $out .= '</table>' . "\n";

    $output[] = $out;

    return $output;
  }

//################ ПРЕДОПЛАТА #########################
  function generatePrepaymentAccounts()
  {
    if (isset($_POST['client_id'])) {
      //Дата начала
      $date_start = $_POST['year'] . '-' . $_POST['month'] . '-' . $_POST['days'];
      if ($date_start == date('Y-m-d', strtotime($date_start))) {
        $client_id = $_POST['client_id'];

        //Берем данные всех клиентов для которых выставляется счет
        if ($this->r->clients->getClientData($client_id, $client_data)) {
          if ($this->a->insertPrepaymentAccount(
            $client_data['client_id'],
            $client_data['name'],
            $client_data['surname'],
            $client_data['address'],
            $client_data['post_code'],
            $client_data['city'],
            $client_data['email'],
            $client_data['number'],
            $date_start,
            $_POST['text_config'],
            (int)$client_data['nds_rate'],
            [
              $client_data['bank_iban'],
              $client_data['bank_bic'],
              $client_data['bank_sepa_referenz'],
              $client_data['bank_sepa_mandat'],
              $client_data['sepa_type'],
              $client_data['sepa_standart'],
            ],
            [
              $client_data['account_owner'],
              $client_data['account_number'],
              $client_data['bank_index'],
              $client_data['bank_name'],
            ],
            NumberHelper::float($_POST['price'])
          )) {
            $this->mess = lang('Account generate', 'message_success');
          } else {
            $this->error = lang('Account not generate', 'message_error');
          }
        } else {
          $this->error = lang('Clients not found', 'message_error');
        }
      } else {
        $this->error = lang('Incorrect data', 'message_error');
      }
    } else {
      $this->error = lang('Incorrect data', 'message_error');
    }

    return $this->showPrepaymentAccountsList();
  }

  function downloadPrepaymentAccounts()
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
      'E-mail'              => lang('E-mail'),
    ];
    $out    = implode(';', $fields) . "\n";

    if ($accounts = $this->a->getPrepaymentAccounts()) {
      foreach ($accounts as $a) {
        $nds_sum = 0;
        $nds_sum = $a['sum'] - ($a['sum'] / (1 + $a['nds'] / 100));
        $out     .= join(
            ';',
            [
              config('accountView')->getNumberAccount($a['a_number'], PREPAYMENT_ACCOUNT_NUMBER),
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
              number_format($a['sum'], 2, ',', '')

              ,
              number_format(($a['sum'] - $nds_sum), '2', ',', ''),
              number_format($nds_sum, '2', ',', ''),
              $a['nds'] . ' %',
              $a['email'],
            ]

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


  //Форма генерации счетов
  function generatePrepaymentAccountsForms()
  {
    $out = '<table align="center" border="0" cellspacing="8">' . "\n";
    $out .= '<tr><td valign="top">' . "\n";

    $out .= '<td valign="top">' . "\n";
    //Выставление счета
    $out .= '<table cellspacing="1" cellpadding="3" class="main" border="0">' . "\n";
    $out .= '	<tr><th colspan="2">' . lang('Credit balance') . '</th></tr>' . "\n";

    $c_date = date('Y-m-d');
    //годы
    $select_years = '<select name="year">' . "\n";
    for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++) {
      $select_years .= '<option value="' . $i . '"' . ($i == date(
          'Y',
          strtotime($c_date)
        ) ? ' selected' : '') . '>' . $i . '</option>' . "\n";
    }
    $select_years .= '</select>' . "\n";

    //месяцы
    $select_monthes = '<select name="month">' . "\n";
    for ($i = 1; $i <= 12; $i++) {
      $select_monthes .= '<option value="' . sprintf("%02d", $i) . '"' . ($i == date(
          'm',
          strtotime($c_date)
        ) ? ' selected' : '') . '>' . sprintf("%02d", $i) . '</option>' . "\n";
    }
    $select_monthes .= '</select>' . "\n";

    //дни
    $select_days = '<select name="days">' . "\n";
    for ($i = 1; $i <= 31; $i++) {
      $select_days .= '<option value="' . sprintf("%02d", $i) . '"' . ($i == date(
          'd',
          strtotime($c_date)
        ) ? ' selected' : '') . '>' . sprintf("%02d", $i) . '</option>' . "\n";
    }
    $select_days .= '</select">' . "\n";
    //Клиенты

    $out .= '<form action="accounts.php?action=generatePrepaymentAccount&type=' . Service::request()->_get('type',
        module('areas')->useModel()->getFirstActiveType()) . '" method="post">' . "\n";
    $out .= '	<tr><td class="light">' . lang(
        'Date'
      ) . ':</td><td class="light"><table border="0"><tr><td>' . $select_years . '</td><td>' . $select_monthes . '</td><td>' . $select_days . "</td></tr></table></td></tr>\n";
    $out .= '	<tr><td class="dark">' . lang('Client') . ':</td><td class="dark"><select name="client_id" class="account-text clientsList select2-find"></select></td></tr>' . "\n";
    $out .= '	<tr><td class="light">' . lang(
        'Invoice amount',
        'accounts'
      ) . ':</td><td class="light"><input type="text" name="price" value="0,00" class="input"/></td></tr>' . "\n";
    $out .= '	<tr><td class="dark">' . lang('Text') . ':</td><td class="dark">' . "\n";
    $out .= '	<select name="text_config" class="account-text">' . "\n";
    $out .= '	<option value="0">' . lang('no') . '</option>' . "\n";
    if ($text_blocks = $this->a->getTextBlocks()) {
      foreach ($text_blocks as $tb) {
        $out .= '<option value="' . $tb['config_id'] . '">' . $tb['title'] . '</option>' . "\n";
      }
    }
    $out .= '	</select>' . "\n";
    $out .= '	</td></tr>' . "\n";
    $out .= '	<tr><td class="light" colspan="2" align="center"><input type="submit" value="' . lang(
        'Execute'
      ) . '" class="button"/></td></tr>' . "\n";
    $out .= "</form>\n";
    $out .= "</table>\n";
    $out .= '</td>' . "\n";
    $out .= '</tr></table>' . "\n";

    return $out;
  }

  function showPrepaymentAccountsList()
  {
    $this->page_key = 'accounts_prepayment_list';

    $out = "<h1>" . lang('Credit balance invoice journal', 'accounts') . "</h1>\n";

    $out .= '<p>' . lang('text_about_archiving_invoices', 'accounts') . '</p>' . "\n";

    $out .= '<table align="center" border="0" cellspacing="1" cellpadding="3" class="main wide" width="80%">' . "\n";
    $out .= '<tr>
              <td class="light" colspan="8" align="right">' . "\n";
    if (config('account')->useSEPA()) {
      $out .= $this->sepaForm('prepayment');
    }
    $out .= ' </td>
					   </tr>' . "\n";
    if (USE_LIST_MENU_IN_ACCOUNTS_NOT_ARCHIVE) {
      $out .= '<tr><td class="dark" colspan="8">' . "\n";
      $out .= $this->navigationLine(AccountType::PrivateAccount, $year, $month, $type, false);
      $out .= '</td></tr>' . "\n";
    }
    $out .= '<tr>' . "\n";
    $out .= '<th width="10%"><input type="checkbox" name="all_select" value="0" onchange="selectAllAccount(this)"/>' . lang(
        'To mark'
      ) . '</th>' . "\n";
    $out .= '<th width="10%">' . lang('Invoice number', 'accounts') . '</th>' . "\n";
    $out .= '<th width="10%">' . lang('Date') . '</th>' . "\n";
    $out .= '<th width="25%">' . lang('Name-address', 'accounts') . '</th>' . "\n";
    $out .= '<th width="5%">' . lang('Status', 'accounts') . '</th>' . "\n";
    $out .= '<th width="20%">' . lang('Invoice amount', 'accounts') . ' (' . CURR_VALUTE . ')</th>' . "\n";
    if (config('account')->useSEPA()) {
      $out .= '<th>' . lang('SEPA_title') . '</th>' . "\n";
    }
    $out .= '<th width="15%">' . lang('Actions') . '</th>' . "\n";
    $out .= '</tr>' . "\n";
    if ($accounts = $this->a->getPrepaymentAccounts(false, $year ?? null, $month ?? null)) {
      if (defined('USE_ACCOUNT_TEXT_FIELDS') && USE_ACCOUNT_TEXT_FIELDS) {
        $out .= '<div class="pupopWindowPlace" id="pupopWindowPlace"></div>';
        $out .= '<div class="pupopWindow" id="pupopWindow"></div>';
        $out .= "<script>var popup = new popupAjaxWindow('pupopWindowPlace', 'pupopWindow');</script>";
      }

      $out .= '<form action="accounts.php?action=markExecution&type=' . (isset($_REQUEST["type"]) ? $_REQUEST["type"]
          : '1') . '" method="POST" id="accounts_list">' . "\n";
      $out .= '<input type="hidden" name="account_type" value="4"/>' . "\n";
      $out .= '<input type="hidden" name="type" value="' . (isset($_REQUEST["type"]) ? $_REQUEST["type"] : '1') . '"/>' . "\n";
      foreach ($accounts as $a) {
        $out .= '<tr>' . "\n";
        $out .= '<td class="light" align="center"><input type="checkbox" name="account[]" value="' . $a['account_id'] . '"/></td>' . "\n";
        $out .= '<td class="' . ($a['execution'] ? 'select'
            : 'dark') . '" id="numberAccountBlock' . $a['account_id'] . '">' . config('accountView')->getNumberAccount($a['a_number'],
            PREPAYMENT_ACCOUNT_NUMBER) . '</td>' . "\n";
        $out .= '<td class="light">'
          . date('d', strtotime($a['date_start'])) . '. '
          . TranslateHelper::translateMonth(date('n', strtotime($a['date_start']))) . ' '
          . date('Y', strtotime($a['date_start']))
          . '</td>' . "\n";
        $out .= '<td class="dark">' . $a['name'] . ' ' . $a['surname'] . '<br/>' . $a['address'] . ', ' . $a['post_code'] . ' ' . $a['city'] . '</td>' . "\n";
        $out .= '<td class="light" style="white-space: nowrap">' . ((!empty($a['club_state'])) ? ((($a['club_state'] == 1) ? 'NM'
              : ('V' . ($a['club_state'] - 1))) . '/ ') : '') . $a['nds'] . '%</td>' . "\n";
        $out .= '<td class="light" align="right">' . NumberHelper::valute($a['sum']) . ' '
          . (str_starts_with($a['price_info'], 'paypal') ? '<img src="' . cdn_url(
              paths()->getAssetsDir('images/' . (config('payment')->usePayonePayment() ? 'payone' : 'paypal_logo') . '.png', 'common')
            ) . '" style="float:left;max-height: 15px;"/>'
            : '') . '</td>' . "\n";
        if (config('account')->useSEPA()) {
          $out .= '<td class="light" valign="top" align="center">' . "\n";
          if (strlen($a['name']) > 0 && strlen($a['sepa_iban']) > 0 && strlen($a['sepa_bic']) > 0 && strlen(
              $a['sepa_mndtid']
            ) > 0 && strlen($a['sepa_dtofsgntr']) > 0 && $a['sum'] > 0) {
            $out .= '<img src="' . cdn_url(paths()->getAssetsDir('images/bullet_green.gif')) . '"/>';
          } else {
            $out .= '<img src="' . cdn_url(paths()->getAssetsDir('images/bullet_red.gif')) . '"/>';
          }
          $out .= '</td>' . "\n";
        }

        $out .= '<td class="dark">';
        if (defined('USE_ACCOUNT_TEXT_FIELDS') && USE_ACCOUNT_TEXT_FIELDS) {
          $out .= '<a href="#" onclick="return popup.createWindow(\'get_ajax_data.php?action=getAccountTextForm&account_id=' . $a['account_id'] . '&account_type=4&type=' . $type . '\')" class="btnEdit" ' . (!empty($a['text_fields'])
              ? 'style="color:#0bbe2a"' : '') . '>' . lang('text_fields', 'accounts_view') . '</a><br />';
        }

        $out .= '<a href="accounts_view.php?account=' . $a['account_id'] . '&account_type=4" target="_blank" class="btnView">' . lang('View') . '</a><br />
				    <a href="accounts_view.php?account=' . $a['account_id'] . '&account_type=4&action=send" target="_blank" onclick="return markExecution(this, \'numberAccountBlock' . $a['account_id'] . '\')" class="btnMail" ' . ($a['send'] == 1
            ? 'style="color:#0712bb"' : '') . ' id="linkSendEmailAccount' . $a['account_id'] . '">' . lang('Send as email', 'accounts') . '</a>
				</td>' . "\n";
        $out .= '</tr>' . "\n";
      }

      $out .= '<tr><td class="light" colspan="7" align="right">
						<input type="submit" name="print" value="' . lang('Print') . '" class="button"/>
						<input type="button" name="archives" value="' . lang('Archive') . '" onClick="sendInArchives()"  class="button"/>
						<input type="button" name="download" value="' . lang(
          'Export RE Journal (CSV)',
          'accounts'
        ) . '" onClick="downloadAccounts(\'prepayment\')"  class="button"/>'
        . '<input type="submit" name="send-email" value="' . lang(
          'Send as email',
          'accounts'
        ) . '" class="button" onClick="return ifConfirm(\'' . lang(
          'confirm_send_email_account',
          'js_confirm'
        ) . '\')"/> 
				</td></tr>' . "\n";
      $out .= '</form>' . "\n";
    }
    $out      .= '</table>' . "\n";
    $output[] = $out;

    return $output;
  }

  function showPrepaymentAccountsArchives()
  {
    $this->page_key = 'accounts_prepayment_archives';

    $out = "<h1>" . lang('Credit balance invoice archive', 'accounts') . "</h1>\n";

    $out .= '<table align="center" border="0" cellspacing="1" cellpadding="3" class="main wide" width="80%">' . "\n";

    $out .= '<tr><td class="dark" colspan="8">' . "\n";
    $out .= $this->navigationLine(AccountType::PrivateAccount, $year, $month, $type, true);
    $out .= '</td></tr>' . "\n";

    $out .= '<tr>' . "\n";
    $out .= '<th width="10%">' . lang('Invoice number', 'accounts') . '</th>' . "\n";
    $out .= '<th width="10%">' . lang('Date') . '</th>' . "\n";
    $out .= '<th width="25%">' . lang('Name-address', 'accounts') . '</th>' . "\n";
    $out .= '<th width="5%">' . lang('Status', 'accounts') . '</th>' . "\n";
    $out .= '<th width="20%">' . lang('Invoice amount', 'accounts') . ' (' . CURR_VALUTE . ')</th>' . "\n";
    $out .= '<th colspan="3" width="30%">' . lang('Actions') . '</th>' . "\n";
    $out .= '</tr>' . "\n";
    if ($accounts = $this->a->getPrepaymentAccounts(true, $year, $month)) {
      foreach ($accounts as $a) {
        $out .= '<tr>' . "\n";
        $out .= '<td class="' . ($a['deleted'] ? 'selectDel' : 'dark') . '">' . config('accountView')->getNumberAccount($a['a_number'],
            PREPAYMENT_ACCOUNT_NUMBER) . '</td>' . "\n";
        $out .= '<td class="light">'
          . date('d', strtotime($a['date_start'])) . '. '
          . TranslateHelper::translateMonth(date('n', strtotime($a['date_start']))) . ' '
          . date('Y', strtotime($a['date_start']))
          . '</td>' . "\n";
        $out .= '<td class="dark">' . $a['name'] . ' ' . $a['surname'] . '<br/>' . $a['address'] . ', ' . $a['post_code'] . ' ' . $a['city'] . '</td>' . "\n";
        $out .= '<td class="light" style="white-space: nowrap">' . ((!empty($a['club_state'])) ? ((($a['club_state'] == 1) ? 'NM'
              : ('V' . ($a['club_state'] - 1))) . '/ ') : '') . $a['nds'] . '%</td>' . "\n";
        $out .= '<td class="light" align="right">' . NumberHelper::valute($a['sum']) . '</td>' . "\n";
        $out .= '<td class="dark">' . ($a['deleted']
            ? '<a href="accounts_view.php?account_delete=' . $a['account_id'] . '&account_type=4" target="_blank" class="btnConfirmationDelete">' . lang(
              'To the credit',
              'accounts'
            ) . '</a>'
            : '') . '</td>
					    <td class="dark"><a href="accounts_view.php?account=' . $a['account_id'] . '&account_type=4" target="_blank" class="btnView">' . lang(
            'View'
          ) . '</a></td>
					    <td class="dark"><a href="accounts.php?action=deleteAccount&account=' . $a['account_id'] . '&account_type=4&type=' . (isset($_REQUEST["type"])
            ? $_REQUEST["type"] : '1') . '" class="btnRemove" onClick="return ifConfirm()">' . lang('Cancel', 'accounts') . '</a></td>' . "\n";
        $out .= '</tr>' . "\n";
      }
    }


    $out .= '</table>' . "\n";

    $output[] = $out;

    return $output;
  }

//################ ДОПОЛНИТЕЛЬНЫЕ СЧЕТА #########################

  //Форма генерации счетов
  function generateOtherAccountsForms()
  {
    $out = '<table align="center" border="0" cellspacing="8">' . "\n";
    $out .= '<tr><td valign="top">' . "\n";

    $c_date = date('Y-m-d');

    //годы
    $select_years = '<select name="year">' . "\n";
    for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++) {
      $select_years .= '<option value="' . $i . '"' . ($i == date(
          'Y',
          strtotime($c_date)
        ) ? ' selected' : '') . '>' . $i . '</option>' . "\n";
    }
    $select_years .= '</select>' . "\n";

    //месяцы
    $select_monthes = '<select name="month">' . "\n";
    for ($i = 1; $i <= 12; $i++) {
      $select_monthes .= '<option value="' . sprintf("%02d", $i) . '"' . ($i == date(
          'm',
          strtotime($c_date)
        ) ? ' selected' : '') . '>' . sprintf("%02d", $i) . '</option>' . "\n";
    }
    $select_monthes .= '</select>' . "\n";

    //дни
    $select_days = '<select name="days">' . "\n";
    for ($i = 1; $i <= 31; $i++) {
      $select_days .= '<option value="' . sprintf("%02d", $i) . '"' . ($i == date(
          'd',
          strtotime($c_date)
        ) ? ' selected' : '') . '>' . sprintf("%02d", $i) . '</option>' . "\n";
    }
    $select_days .= '</select">' . "\n";
    $out         .= '<td valign="top">' . "\n";
    //Выставление счета
    $out .= '<table cellspacing="1" cellpadding="3" class="main" border="0">' . "\n";
    $out .= '	<tr><th colspan="2">' . lang('Special accounts', 'accounts') . '</th></tr>' . "\n";
    //Выбор Клиентов
    $out .= '<form action="accounts_builder.php" method="post" target="_blank">' . "\n";
    $out .= '	<tr><td class="light">' . lang(
        'Date'
      ) . ':</td><td class="light"><table border="0"><tr><td>' . $select_years . '</td><td>' . $select_monthes . '</td><td>' . $select_days . "</td></tr></table></td></tr>\n";
    $out .= '	<tr><td class="dark">' . lang('Client') . ':</td><td class="dark">
	      <select name="client_id" class="account-text clientsList select2-find"></select>
	      </td></tr>' . "\n";
    $out .= '	<tr><td class="light">' . lang('VAT', 'accounts') . ':</td><td class="light">
					<select name="nds">';
    if ($this->r->nds->getNdss($nds)) {
      foreach ($nds as $n) {
        $out .= '<option value="' . $n['rate'] . '">' . $n['rate'] . '%</option>' . "\n";
      }
    }
    $out .= '		</select></td></tr>' . "\n";
    $out .= '<tr><td class="dark">' . lang('Text') . ':</td><td class="dark">' . "\n";
    $out .= '<select name="text_config"  class="account-text">' . "\n";
    $out .= '<option value="0">' . lang('no') . '</option>' . "\n";
    if ($text_blocks = $this->a->getTextBlocks()) {
      foreach ($text_blocks as $tb) {
        $out .= '<option value="' . $tb['config_id'] . '">' . $tb['title'] . '</option>' . "\n";
      }
    }
    $out .= '</select>' . "\n";
    $out .= '</td></tr>' . "\n";
    $out .= '	<tr><td class="light" colspan="2" align="center"><input type="submit" value="' . lang(
        'Execute'
      ) . '" class="button"/></td></tr>' . "\n";
    $out .= "</form>\n";
    $out .= "</table>\n";
    $out .= '</td>' . "\n";
    $out .= '</tr></table>' . "\n";

    return $out;
  }

  function downloadOtherAccounts()
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
      'E-mail'              => lang('E-mail'),
    ];
    $out    = implode(';', $fields) . "\n";
    if ($accounts = $this->a->getOtherAccounts(false, false)) {
      foreach ($accounts as $a) {
        $nds_sum = 0;
        $nds_sum = $a['sum'] - ($a['sum'] / (1 + $a['nds'] / 100));
        $out     .= join(
            ';',
            [
              config('accountView')->getNumberAccount($a['a_number'], OTHER_ACCOUNT_NUMBER),
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
              number_format($a['sum'], 2, ',', '')

              ,
              number_format(($a['sum'] - $nds_sum), '2', ',', ''),
              number_format($nds_sum, '2', ',', ''),
              $a['nds'] . ' %',
              $a['email'],
            ]

          ) . "\n";
      }
      header("Content-Disposition: attachment; filename=accounts_other_export_" . date('Y_m_d') . ".csv");
      header(
        "Content-Type: application/x-force-download; name=\"accounts_other_export_" . date(
          'Y_m_d'
        ) . ".csv\"; charset=utf-8"
      );
      echo "\xEF\xBB\xBF" . $out;
      die;
    }
  }

  //Журнал счетов
  function showOtherAccountsList()
  {
    $this->page_key = 'accounts_other_list';

    $out = "<h1>" . lang('Special Accounts Journal', 'accounts') . "</h1>\n";

    $out .= '<p>' . lang('text_about_archiving_invoices', 'accounts') . '</p>' . "\n";

    $out .= '<table align="center" border="0" cellspacing="1" cellpadding="3" class="main wide" style="width:70%">' . "\n";
    $out .= '<tr>
              <td class="light" colspan="8" align="right">' . "\n";
    if (config('account')->useSEPA()) {
      $out .= $this->sepaForm('other');
    }
    $out .= ' </td></tr>' . "\n";
    if (USE_LIST_MENU_IN_ACCOUNTS_NOT_ARCHIVE) {
      $out .= '<tr><td class="dark" colspan="8">' . "\n";
      $out .= $this->navigationLine(AccountType::Special, $year, $month, $type, false);
      $out .= '</td></tr>' . "\n";
    }
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
    $out .= '<th>' . lang('Actions') . '</th>' . "\n";
    $out .= '</tr>' . "\n";
    if ($accounts = $this->a->getOtherAccounts(false, $year, $month)) {
      if (defined('USE_ACCOUNT_TEXT_FIELDS') && USE_ACCOUNT_TEXT_FIELDS) {
        $out .= '<div class="pupopWindowPlace" id="pupopWindowPlace"></div>';
        $out .= '<div class="pupopWindow" id="pupopWindow"></div>';
        $out .= "<script>var popup = new popupAjaxWindow('pupopWindowPlace', 'pupopWindow');</script>";
      }
      $out .= '<form action="accounts.php?action=markExecution&type=' . (isset($_REQUEST["type"]) ? $_REQUEST["type"]
          : '1') . '" method="POST" id="accounts_list">' . "\n";
      $out .= '<input type="hidden" name="account_type" value="3"/>' . "\n";
      $out .= '<input type="hidden" name="type" value="' . (isset($_REQUEST["type"]) ? $_REQUEST["type"] : '1') . '"/>' . "\n";
      foreach ($accounts as $a) {
        $out .= '<tr>' . "\n";
        $out .= '<td class="light" align="center"><input type="checkbox" name="account[]" value="' . $a['account_id'] . '"/></td>' . "\n";
        $out .= '<td class="' . ($a['execution'] ? 'select'
            : 'dark') . '" id="numberAccountBlock' . $a['account_id'] . '">' . config('accountView')->getNumberAccount($a['a_number'],
            OTHER_ACCOUNT_NUMBER) . '</td>' . "\n";
        $out .= '<td class="light">' . date('d.m.Y', strtotime($a['date_start'])) . '</td>' . "\n";
        $out .= '<td class="dark">' . $a['name'] . ' ' . $a['surname'] . '<br/>' . $a['address'] . ', ' . $a['post_code'] . ' ' . $a['city'] . '</td>' . "\n";
        $out .= '<td class="light" style="white-space: nowrap">' . ((!empty($a['club_state'])) ? ((($a['club_state'] == 1) ? 'NM'
              : ('V' . ($a['club_state'] - 1))) . '/ ') : '') . $a['nds'] . '%</td>' . "\n";
        $out .= '<td class="light" align="right">' . NumberHelper::valute($a['sum']) . '</td>' . "\n";
        if (config('account')->useSEPA()) {
          $out .= '<td class="dark" valign="top" align="center">' . "\n";
          if (strlen($a['name']) > 0 && strlen($a['sepa_iban']) > 0 && strlen($a['sepa_bic']) > 0 && strlen(
              $a['sepa_mndtid']
            ) > 0 && strlen($a['sepa_dtofsgntr']) > 0 && $a['sum'] > 0) {
            $out .= '<img src="' . base_url(paths()->getAssetsDir('images/bullet_green.gif')) . '"/>';
          } else {
            $out .= '<img src="' . base_url(paths()->getAssetsDir('images/bullet_red.gif')) . '"/>';
          }
          $out .= '</td>' . "\n";
        }
        $out .= '<td class="dark">';
        if (defined('USE_ACCOUNT_TEXT_FIELDS') && USE_ACCOUNT_TEXT_FIELDS) {
          $out .= '<a href="#" onclick="return popup.createWindow(\'get_ajax_data.php?action=getAccountTextForm&account_id=' . $a['account_id'] . '&account_type=3&type=' . $type . '\')" class="btnEdit" ' . (!empty($a['text_fields'])
              ? 'style="color:#0bbe2a"' : '') . '>' . lang('text_fields', 'accounts_view') . '</a><br />';
        }

        $out .= '<a href="accounts_view.php?account=' . $a['account_id'] . '&account_type=3" target="_blank" class="btnView">' . lang('View') . '</a><br />
					    <a href="accounts_view.php?account=' . $a['account_id'] . '&account_type=3&action=send" target="_blank" onclick="return markExecution(this, \'numberAccountBlock' . $a['account_id'] . '\')" class="btnMail"  ' . ($a['send'] == 1
            ? 'style="color:#0712bb"' : '') . ' id="linkSendEmailAccount' . $a['account_id'] . '">' . lang('Send as email', 'accounts') . '</a>
					 </td>' . "\n";
        $out .= '</tr>' . "\n";
      }

      $out .= '<tr><td class="light" colspan="8" align="right">
							<input type="submit"  data-type="' . $type . '" name="print" value="' . lang('Print') . '" class="button"/>
							<input type="button"  data-type="' . $type . '" name="archives" value="' . lang('Archive') . '" onClick="sendInArchives()"  class="button"/>
							<input type="button"  data-type="' . $type . '" name="download" value="' . lang('Export RE Journal (CSV)', 'accounts') . '" onClick="downloadAccounts(\'other\')"  class="button"/>
  						    <input type="submit"  data-type="' . $type . '" name="send-email" value="' . lang(
          'Send as email',
          'accounts'
        ) . '" class="button" onClick="return ifConfirm(\'' . lang(
          'confirm_send_email_account',
          'js_confirm'
        ) . '\')"/> 
					</td></tr>' . "\n";
      $out .= '</form>' . "\n";
    }


    $out      .= '</table>' . "\n";
    $output[] = $out;

    return $output;
  }

  //Архив счетов
  function showOtherAccountsArchives()
  {
    $this->page_key = 'accounts_other_archives';

    $out = "<h1>" . lang('Special accounts archive', 'accounts') . "</h1>\n";

    $out .= '<table align="center" border="0" cellspacing="1" cellpadding="3" class="main wide">' . "\n";

    $out .= '<tr><td class="dark" colspan="8">' . "\n";
    $out .= $this->navigationLine(AccountType::Special, $year, $month, $type, true);
    $out .= '</td></tr>' . "\n";

    $out .= '<tr>' . "\n";
    $out .= '<th>' . lang('Invoice number', 'accounts') . '</th>' . "\n";
    $out .= '<th>' . lang('Date') . '</th>' . "\n";
    $out .= '<th>' . lang('Name-address', 'accounts') . '</th>' . "\n";
    $out .= '<th>' . lang('Status', 'accounts') . '</th>' . "\n";
    $out .= '<th>' . lang('Total amount', 'accounts') . ' (' . CURR_VALUTE . ')</th>' . "\n";
    $out .= '<th colspan="3">' . lang('Actions') . '</th>' . "\n";
    $out .= '</tr>' . "\n";
    if ($accounts = $this->a->getOtherAccounts(true, $year, $month)) {
      foreach ($accounts as $a) {
        $out .= '<tr>' . "\n";
        $out .= '<td class="' . ($a['deleted'] ? 'selectDel' : 'dark') . '">' . config('accountView')->getNumberAccount($a['a_number'],
            OTHER_ACCOUNT_NUMBER) . '</td>' . "\n";
        $out .= '<td class="light">' . date('d.m.Y', strtotime($a['date_start'])) . '</td>' . "\n";
        $out .= '<td class="dark">' . $a['name'] . ' ' . $a['surname'] . '<br/>' . $a['address'] . ', ' . $a['post_code'] . ' ' . $a['city'] . '</td>' . "\n";
        $out .= '<td class="light" style="white-space: nowrap">' . ((!empty($a['club_state'])) ? ((($a['club_state'] == 1) ? 'NM'
              : ('V' . ($a['club_state'] - 1))) . '/ ') : '') . $a['nds'] . '%</td>' . "\n";
        $out .= '<td class="light" align="right">' . NumberHelper::valute($a['sum']) . '</td>' . "\n";
        $out .= '<td class="dark">' . ($a['deleted']
            ? '<a href="accounts_view.php?account_delete=' . $a['account_id'] . '&account_type=3" target="_blank" class="btnConfirmationDelete">' . lang(
              'To the credit',
              'accounts'
            ) . '</a>'
            : '') . '</td>
						<td class="dark"><a href="accounts_view.php?account=' . $a['account_id'] . '&account_type=3" target="_blank" class="btnView">' . lang(
            'View'
          ) . '</a></td>
						<td class="dark"><a href="accounts.php?action=deleteAccount&account=' . $a['account_id'] . '&account_type=3&type=' . (isset($_REQUEST["type"])
            ? $_REQUEST["type"] : '1') . '" class="btnRemove" onClick="return ifConfirm()">' . lang('Cancel', 'accounts') . '</a></td>' . "\n";
        $out .= '</tr>' . "\n";
      }
    }
    $out .= '</table>' . "\n";

    $output[] = $out;

    return $output;
  }

  /**
   * Показать журнал действующих счетов онлайн-оплаты.
   */
  public function showOnlinePaymentAccountsList(): array
  {
    $this->page_key = 'accounts_online_payment_list';

    return [$this->renderOnlinePaymentAccounts(false)];
  }

  /**
   * Показать архив счетов онлайн-оплаты.
   */
  public function showOnlinePaymentAccountsArchives(): array
  {
    $this->page_key = 'accounts_online_payment_archives';

    return [$this->renderOnlinePaymentAccounts(true)];
  }

  /**
   * Сформировать таблицу счетов онлайн-оплаты.
   */
  private function renderOnlinePaymentAccounts(bool $archives): string
  {
    $title = lang(
      $archives ? 'accounts_online_payment_archives_title' : 'accounts_online_payment_list_title',
      'structure'
    );
    $out   = '<h1>' . $title . '</h1>' . "\n";
    if (!$archives) {
      $out .= '<p>' . lang('text_about_archiving_invoices', 'accounts') . '</p>' . "\n";
    }

    $out .= '<table align="center" border="0" cellspacing="1" cellpadding="3" class="main wide" width="80%">' . "\n";
    $out .= '<tr><td class="dark" colspan="' . ($archives ? 8 : 7) . '">';
    $out .= $this->navigationLine(AccountType::OnlinePayment, $year, $month, $type, $archives);
    $out .= '</td></tr>' . "\n";

    $out .= '<tr>' . "\n";
    if (!$archives) {
      $out .= '<th width="10%"><input type="checkbox" name="all_select" value="0" onchange="selectAllAccount(this)"/>'
        . lang('To mark') . '</th>' . "\n";
    }
    $out .= '<th width="10%">' . lang('Invoice number', 'accounts') . '</th>' . "\n";
    $out .= '<th width="10%">' . lang('Date') . '</th>' . "\n";
    $out .= '<th width="25%">' . lang('Name-address', 'accounts') . '</th>' . "\n";
    $out .= '<th width="5.5%">' . lang('Status', 'accounts') . '</th>' . "\n";
    $out .= '<th width="20%">' . lang('Total amount', 'accounts') . ' (' . CURR_VALUTE . ')</th>' . "\n";
    $out .= '<th' . ($archives ? ' colspan="3"' : '') . ' width="20%">' . lang('Actions') . '</th>' . "\n";
    $out .= '</tr>' . "\n";

    $accounts = $this->a->getOnlinePaymentAccounts(
      $archives,
      $year === false || $year === null ? null : (int)$year,
      $month === false || $month === null ? null : (int)$month
    );
    if ($accounts) {
      if (!$archives) {
        $out .= '<form action="accounts.php?action=markExecution&type=' . (int)$type . '" method="POST" id="accounts_list">' . "\n";
        $out .= '<input type="hidden" name="account_type" value="' . AccountViewType::OnlinePayment->value . '"/>' . "\n";
        $out .= '<input type="hidden" name="type" value="' . (int)$type . '"/>' . "\n";
      }

      foreach ($accounts as $account) {
        $accountId = (int)$account['account_id'];
        $out       .= '<tr>' . "\n";
        if (!$archives) {
          $out .= '<td class="light" align="center"><input type="checkbox" name="account[]" value="' . $accountId . '"/></td>' . "\n";
        }
        $out       .= '<td class="' . ($account['deleted'] ? 'selectDel' : ($account['execution'] ? 'select' : 'dark'))
          . '" id="numberAccountBlock' . $accountId . '">'
          . config('accountView')->getNumberAccount($account['a_number'], ONLINE_PAYMENT_ACCOUNT_NUMBER) . '</td>' . "\n";
        $out       .= '<td class="light">' . date('d.m.Y', strtotime($account['date_start'])) . '</td>' . "\n";
        $out       .= '<td class="dark">' . htmlspecialchars(trim(($account['name'] ?? '') . ' ' . ($account['surname'] ?? '')), ENT_QUOTES)
          . '<br/>' . htmlspecialchars((string)($account['address'] ?? ''), ENT_QUOTES) . ', '
          . htmlspecialchars((string)($account['post_code'] ?? ''), ENT_QUOTES) . ' '
          . htmlspecialchars((string)($account['city'] ?? ''), ENT_QUOTES) . '</td>' . "\n";
        $clubState = !empty($account['club_state'])
          ? ConfigClubStateModel::getItemById($account['club_state'], 'short') . ' / '
          : '';
        $out       .= '<td class="light">' . htmlspecialchars($clubState . $account['nds'], ENT_QUOTES) . '%</td>' . "\n";
        $out       .= '<td class="light" align="right">'
          . $this->onlinePaymentGatewayIcon($account['price_info'] ?? null)
          . NumberHelper::valute($account['sum']) . '</td>' . "\n";

        if ($archives) {
          $out .= '<td class="dark">' . ($account['deleted']
              ? '<a href="accounts_view.php?account_delete=' . $accountId . '&account_type=' . AccountViewType::OnlinePayment->value
              . '&type=' . (int)$type
              . '" target="_blank" class="btnConfirmationDelete">' . lang('To the credit', 'accounts') . '</a>'
              : '') . '</td>' . "\n";
          $out .= '<td class="dark"><a href="accounts_view.php?account=' . $accountId . '&account_type=' . AccountViewType::OnlinePayment->value
            . '&type=' . (int)$type
            . '" target="_blank" class="btnView">' . lang('View') . '</a></td>' . "\n";
          $out .= '<td class="dark"><a href="accounts.php?action=deleteAccount&account=' . $accountId
            . '&account_type=' . AccountViewType::OnlinePayment->value . '&type=' . (int)$type
            . '" class="btnRemove" onClick="return ifConfirm()">'
            . lang('Cancel', 'accounts') . '</a></td>' . "\n";
        } else {
          $out .= '<td class="dark"><a href="accounts_view.php?account=' . $accountId . '&account_type=' . AccountViewType::OnlinePayment->value
            . '&type=' . (int)$type
            . '" target="_blank" class="btnView">' . lang('View') . '</a><br/>' . "\n";
          $out .= '<a href="accounts_view.php?account=' . $accountId . '&account_type=' . AccountViewType::OnlinePayment->value
            . '&type=' . (int)$type
            . '&action=send" target="_blank" onclick="return markExecution(this, \'numberAccountBlock' . $accountId
            . '\')" class="btnMail"' . ($account['send'] == 1 ? ' style="color:#0712bb"' : '')
            . ' id="linkSendEmailAccount' . $accountId . '">' . lang('Send as email', 'accounts') . '</a></td>' . "\n";
        }
        $out .= '</tr>' . "\n";
      }

      if (!$archives) {
        $out .= '<tr><td class="light" colspan="7" align="right">'
          . '<input type="submit" name="print" value="' . lang('Print') . '" class="button"/> '
          . '<input type="button" name="archives" value="' . lang('Archive') . '" onClick="sendInArchives()" class="button"/> '
          . '<input type="submit" name="download" value="' . lang('Export RE Journal (CSV)', 'accounts')
          . '" formaction="accounts.php?action=downloadOnlinePaymentAccounts" class="button"/> '
          . '<input type="submit" name="send-email" value="' . lang('Send as email', 'accounts') . '" class="button" '
          . 'onClick="return ifConfirm(\'' . lang('confirm_send_email_account', 'js_confirm') . '\')"/>'
          . '</td></tr>' . "\n";
        $out .= '</form>' . "\n";
      }
    }

    $out .= '</table>' . "\n";

    return $out;
  }

  /**
   * Сформировать иконку шлюза, сохранённого в данных онлайн-счёта.
   */
  private function onlinePaymentGatewayIcon(?string $priceInfo): string
  {
    $paymentData = OnlineGatewayService::parseOnlinePaymentInvoicePriceInfo($priceInfo);
    if ($paymentData === null) {
      return '';
    }

    $gateway = OnlineGateway::tryFrom($paymentData['gateway']);
    if ($gateway === null) {
      return '';
    }

    $image = $gateway === OnlineGateway::PAYONE ? 'payone.png' : 'paypal_logo.png';
    $title = $gateway === OnlineGateway::PAYONE ? 'Payone' : 'PayPal';

    return '<img src="' . cdn_url(paths()->getAssetsDir('images/' . $image, 'common'))
      . '" alt="' . $title . '" title="' . $title . '" style="float:left;max-height:15px;"/>';
  }


//#################### ОБЩИЕ ФУНЦКЦИИ ДЛЯ СЧЕТОВ #########################
  function changeAccountTextFields()
  {
    if ($account_id = Service::request()->_post('account_id')) {
      $this->a->changeAccountTextFields($account_id, Service::request()->_post('text_fields'));
    }
    $output = $this->switchBack(Service::request()->_('account_type'));

    return $output;
  }

  function markExecution()
  {
    $action = 'print';
    if (Service::request()->_('send-email')) {
      $action = 'sendAll';
    }
    if (isset($_POST['account'])) {
      if ($action != 'sendAll') {
        $this->a->markExecution($_POST['account']);
      }
      $out = '<form action="accounts_view.php" method="POST" id="accountPrint" target="_blank">' . "\n";
      $out .= '<input type="hidden" name="account_type" value="' . (int)$_POST['account_type'] . '"/></td>' . "\n";
      $out .= '<input type="hidden" name="action" value="' . $action . '"/></td>' . "\n";
      foreach ($_POST['account'] as $account_id) {
        $out .= '<input type="hidden" name="account[]" value="' . $account_id . '"/></td>' . "\n";
      }
      $out .= '</form>' . "\n";
      $out .= '<script>document.getElementById("accountPrint").submit()</script>' . "\n";
      $out .= '<script>markExecutionAll(' . json_encode($_POST['account']) . ')</script>';
    }
    //Выбираем куда вернуться после выполнения
    $output   = $this->switchBack(Service::request()->_('account_type'));
    $output[] = $out;

    return $output;
  }

  function sendInArchives()
  {
    if (isset($_POST['account'])) {
      $this->a->sendInArchives($_POST['account']);
    }
    //Выбираем куда вернуться после выполнения
    return $this->switchBack(Service::request()->_('account_type'));

  }

  function deleteAccount()
  {
    if (isset($_GET['account'])) {
      $this->a->deleteAccount((int)$_GET['account']);
    }

    //Выбираем куда вернуться после выполнения
    return $this->switchBack(Service::request()->_('account_type'), true);
  }

  protected function switchBack($type, $archive = false)
  {
    //Выбираем куда вернуться после выполнения
    $type   = AccountViewType::tryFrom((int)$type);
    $prefix = $archive ? 'Archives' : 'List';
    return match ($type) {
      AccountViewType::Individual     => $this->{'showAccounts' . $prefix}(),
      AccountViewType::Abo            => $this->{'showAboAccounts' . $prefix}(),
      AccountViewType::Special        => $this->{'showOtherAccounts' . $prefix}(),
      AccountViewType::PrivateAccount => $this->{'showPrepaymentAccounts' . $prefix}(),
      AccountViewType::OnlinePayment  => $this->{'showOnlinePaymentAccounts' . $prefix}(),
      default                         => $this->generateAccounts(),
    };
  }

  //Навигация по годам
  function navigationYearsLine($type, &$year, &$mounth, $typePlace = false)
  {
    $year   = Service::request()->_get('year', date('Y'));
    $mounth = Service::request()->_get('month', false);

    if (($year_periods = $this->a->getMaxMinDate($type, true)) && !empty($year_periods['max_date']) && !empty($year_periods['min_date'])) {
      if ($year > date('Y', strtotime($year_periods['max_date'])) || $year < date(
          'Y',
          strtotime($year_periods['min_date'])
        )) {
        $year = date('Y', strtotime($year_periods['max_date']));
      }

      $s   = '';
      $out = '';
      for (
        $y = date('Y', strtotime($year_periods['max_date'])); $y >= date(
        'Y',
        strtotime($year_periods['min_date'])
      ); $y--
      ) {
        $strType = ($typePlace) ? ('&type=' . $typePlace) : "";
        $out     .= $s . ($year == $y
            ? ' <strong>' . $y . '</strong> '
            : ' <a href="accounts.php?action=' . ($type == 1
              ? 'showAboAccountsArchives'
              : ($type == 2
                ? 'showOtherAccountsArchives'
                : ($type == 3 ? 'showPrepaymentAccountsArchives'
                  : 'showAccountsArchives'))) . '&year=' . $y . $strType . '">' . $y . '</a> ') . "\n";
        $s       = '|';
      }
    }

    if (empty($year_periods['min_date'])) {
      return '';
    }

    return $out;
  }


  //ключ страницы
  function getPageKey()
  {
    return $this->page_key;
  }

//#################### ТЕКСТОВЫЕ БЛОКИ ДЛЯ СЧЕТОВ #########################
  function showAccountTextConfig()
  {
    //очень хитро, была перенемена часть настроек, касающихся счетов, в раздел счетов, а именно DTA и email (отправка pdf счета)
    //но хранится все как и раньше в тех же таблицах
    //таблица letters_templates - сабжект и текст письма
    //таблица config - все остальное
    //все сохраняется через класс account - функции setConfig и setLetterConfig

    $this->page_key = 'accounts_text_config';

    if (isset ($this->message)) {
      $output[] = '<span class="success">' . $this->message . '</span>';
    }

    if (isset ($this->error)) {
      $output[] = '<span class="error">' . $this->error . '</span>';
    }
    $out = "<h1>" . lang('E-mail / DTA - Settings', 'accounts') . "</h1>\n";
    Service::mailer()->getTemplatesEngine()->getTemplate(ModeTemplate::SERVICE->value, 'account_file_send', $title, $subject, $content,
      config('lang')->getDefault());
    $out .= '<form action="accounts.php?action=saveConfig" method="post" onsubmit="return ifConfirm ()">' . "\n";
    $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide" width="700px">' . "\n";
    $out .= '<tr><th colspan="2">' . lang('E-mail settings', 'accounts') . '</th></tr>' . "\n";
    $out .= '<tr><td class="dark" align="right">' . lang(
        'Sender\'s email address',
        'accounts'
      ) . '</td><td class="light"><input type="text" class="input wide" name="account_mail" value="' . htmlspecialchars(
        $this->r->config['account_mail'],
        ENT_QUOTES
      ) . '"></td></tr>' . "\n";
    $out .= '<tr><td class="dark" align="right">' . lang(
        'Subject',
        'accounts'
      ) . '</td><td class="light"><input type="text" class="input wide" name="subject" value="' . htmlspecialchars(
        $subject,
        ENT_QUOTES
      ) . '"></td></tr>' . "\n";
    $out .= '<tr><td class="dark" align="right">' . lang(
        'Name of the plant',
        'accounts'
      ) . '</td><td class="light"><input type="text" class="input wide" name="account_attachment" value="' . htmlspecialchars(
        $this->r->config['account_attachment'],
        ENT_QUOTES
      ) . '"></td></tr>' . "\n";
    $out .= '<tr><td class="dark" align="right">' . lang('Text') . '</td>' . "\n";
    $out .= '<td class="light">';
    $out .= '<textarea name="content" class="ckeditor">' . (isset($content) ? $content : '') . '</textarea>';
    $out .= '</td></tr>' . "\n";
    if (config('account')->useSEPA()) {
      $out .= '<tr><th colspan="2">' . lang('SEPA') . '</th></tr>' . "\n";
      $out .= '<tr><td class="dark" align="right">' . lang(
          'Name'
        ) . '</td><td class="light"><input type="text" class="input wide" name="sepa_name" value="' . htmlspecialchars(
          $this->r->config['sepa_name'],
          ENT_QUOTES
        ) . '"></td></tr>' . "\n";
      $out .= '<tr><td class="dark" align="right">' . lang(
          'IBAN'
        ) . '</td><td class="light"><input type="text" class="input wide" name="sepa_iban" value="' . htmlspecialchars(
          $this->r->config['sepa_iban'],
          ENT_QUOTES
        ) . '"></td></tr>' . "\n";
      $out .= '<tr><td class="dark" align="right">' . lang(
          'BIC'
        ) . '</td><td class="light"><input type="text" class="input wide" name="sepa_bic" value="' . htmlspecialchars(
          $this->r->config['sepa_bic'],
          ENT_QUOTES
        ) . '"></td></tr>' . "\n";
      $out .= '<tr><td class="dark" align="right">' . lang(
          'Creditor ID',
          'accounts'
        ) . '</td><td class="light"><input type="text" class="input wide" name="sepa_direct_debit" value="' . htmlspecialchars(
          $this->r->config['sepa_direct_debit'],
          ENT_QUOTES
        ) . '"></td></tr>' . "\n";
    }
    $out .= '<tr><th colspan="2">&nbsp;</th></tr>' . "\n";

    $out .= '<tr><td class="dark" align="right" rowspan="2">' . lang(
        'E-mail for the copy of the invoice',
        'accounts'
      ) . '</td><td class="light"><input type="text" class="input wide" name="mail_for_duplicate_account" value="' . htmlspecialchars(
        $this->r->config['mail_for_duplicate_account'],
        ENT_QUOTES
      ) . '"></td></tr>' . "\n";
    $out .= '<tr><td class="dark"><p>' . lang('Enter multiple e-mails by comma.', 'accounts') . '</p></td></tr>' . "\n";

    if (OnlineGatewayService::onlinePaymentUse()) {
      $out .= '<tr><th colspan="2">' . lang('Online payment', 'accounts') . '</th></tr>' . "\n";
      $out .= '<tr><td class="dark"></td><td class="light"><input type="checkbox" id="online_payment_invoice_use" name="online_payment_invoice_use" value="1"'
        . (config('account')->useOnlinePaymentInvoice() ? ' checked' : '') . '> <label for="online_payment_invoice_use">' . lang(
          'Create invoices for online payments',
          'accounts'
        ) . '</label></td></tr>' . "\n";
    }

    $out .= '<tr><th colspan="2"><input type="submit" value="' . lang('button_save') . '" class="button"></th></tr>' . "\n";

    $out      .= "</table>\n";
    $out      .= "</form>\n";
    $output[] = $out;

    //настройки текстов для счетов

    $out      = "<h1>" . lang('Invoice text variants', 'accounts') . "</h1>\n";
    $out      .= $this->showTextConfigForm();
    $output[] = $out;

    $out = "<h1>" . lang('List of text variants', 'accounts') . "</h1>\n";

    $out .= '<table align="center" border="0" cellspacing="1" cellpadding="3" class="main wide" width="700px">' . "\n";

    $out .= '<tr>' . "\n";
    $out .= '<th style="width:90%">' . lang('Title') . '</th>' . "\n";
    $out .= '<th colspan="2">' . lang('Actions') . '</th>' . "\n";
    $out .= '</tr>' . "\n";

    if ($text_blocks = $this->a->getTextBlocks()) {
      foreach ($text_blocks as $tb) {
        $out .= '<tr>' . "\n";
        $out .= '<td class="dark">' . $tb['title'] . '</td>' . "\n";
        $out .= '<td class="light"><a href="accounts.php?action=editText&config_id=' . $tb['config_id'] . '" class="btnEdit">' . lang(
            'button_update'
          ) . '</a></td>';
        $out .= '<td class="light"><a href="accounts.php?action=removeText&config_id=' . $tb['config_id'] . '" onclick="return ifConfirm ()" class="btnRemove">' . lang(
            'button_remove'
          ) . '</a></td>';
        $out .= '</tr>' . "\n";
      }
    }
    $out .= '</table>' . "\n";

    $output[] = $out;


    return $output;
  }

  function saveConfig()
  {
    if (isset ($_POST['subject']) && isset ($_POST['content']) && isset ($_POST['account_mail']) && isset ($_POST['account_attachment'])) {
      $this->a->setConfig(
        $_POST['account_mail'],
        $_POST['account_attachment'],
        $_POST['sepa_name'] ?? '',
        $_POST['sepa_iban'] ?? '',
        $_POST['sepa_bic'] ?? '',
        $_POST['sepa_direct_debit'] ?? '',
        $_POST['mail_for_duplicate_account']
      );
      $this->a->setLetterConfig($_POST['subject'], $_POST['content']);
      try {
        if (OnlineGatewayService::onlinePaymentUse()
          && !$this->a->setOnlinePaymentInvoiceUse(isset($_POST['online_payment_invoice_use']))) {
          throw new RuntimeException('Online payment invoice setting was not saved.');
        }
      } catch (Exception $exception) {
        Service::logger('accounts')->logException(
          $exception,
          'Online payment invoice activation failed.',
          ['enabled' => isset($_POST['online_payment_invoice_use'])]
        );
        $this->error = lang('Online payment invoice activation failed', 'accounts');
      }

      //считываем концигурационные значения
      $temp = Query::sqlQuery('select * from ' . Query::tableName('config'));
      foreach ($temp as $item) {
        $this->r->config[$item['alias']] = $item['value'];
      }

      if (empty($this->error)) {
        $this->message = lang('message_element_base_update', 'message_success');
      }
    }

    return $this->showAccountTextConfig();
  }

  function insertText()
  {
    if (!$this->a->insertTextBlock($_POST['title'], $_POST['footer'])) {
      $this->error = lang('Text was not added!', 'message_error');
    }

    return $this->showAccountTextConfig();
  }

  function editText()
  {
    if ($row = $this->a->getTextBlock((int)$_GET['config_id'])) {
      $this->page_key = 'accounts_text_config';
      $out            = "<h1>" . lang('Text variants', 'accounts') . "</h1>\n";
      $out            .= $this->showTextConfigForm($row);
      $output[]       = $out;

      return $output;
    } else {
      $this->error = lang('This text variant not found!', 'message_error');
    }

    return $this->showAccountTextConfig();
  }

  function changeText()
  {
    if (!$this->a->changeTextBlock((int)$_GET['config_id'], $_POST['title'], $_POST['footer'])) {
      $this->error = lang('This text variant has not been changed!', 'message_error');
    }

    return $this->showAccountTextConfig();
  }

  function removeText()
  {
    if (!$this->a->removeTextBlock((int)$_GET['config_id'])) {
      $this->error = lang('This text variant is not deleted!', 'message_error');
    }

    return $this->showAccountTextConfig();
  }


  function showTextConfigForm($row = [])
  {
    $out = '<form action="accounts.php?action=' . (isset($row['config_id']) ? 'changeText&config_id=' . $row['config_id']
        : 'insertText') . '" method="post">' . "\n";
    $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide" style="width:700px">' . "\n";
    $out .= '<tr><th colspan="2">' . (!isset($row['config_id']) ? lang('Create new text variant', 'accounts')
        : lang('Change text variant', 'accounts')) . '</th></tr>' . "\n";
    $out .= '<tr>' . "\n";
    $out .= '<td class="dark">' . lang('Title') . ':</td>' . "\n";
    $out .= '<td class="dark">' . "\n";
    $out .= '<input type="text" name="title" class="input wide" style="width:99%" value="' . (isset($row['title']) ? htmlspecialchars(
        $row['title'],
        ENT_QUOTES
      ) : '') . '"/>' . "\n";
    $out .= '</td>' . "\n";
    $out .= '</tr>' . "\n";
    $out .= '<tr><td class="light">' . lang('Text') . '</td>' . "\n";
    $out .= '<td class="light">';
    $out .= '<textarea name="footer" class="ckeditor">' . (isset($row['footer']) ? $row['footer'] : '') . '</textarea>';
    $out .= '</td></tr>' . "\n";
    $out .= '<tr><th colspan="2"><input type="submit" value="' . lang('button_save') . '" class="button"></th></tr>' . "\n";
    if (isset($row['config_id'])) {
      $out .= '<tr>' . "\n";
      $out .= '<td colspan="2" align="center"><br /><a href="accounts.php?action=showAccountTextConfig">' . lang('Back') . '</a></td>' . "\n";
      $out .= '</tr>' . "\n";
    }
    $out .= "</table>\n";
    $out .= "</form>\n";

    return $out;
  }


//#################### ФУНЦКЦИИ DTA #########################

  function downloadAccountsDTA()
  {
    // Initialize new DTA file
    //$dta_file = new DTA(DTA_DEBIT);
    // Set file sender
    $this->dta_file->setAccountFileSender(
      [
        "name"           => $this->n,
        "bank_code"      => $this->bc,
        "account_number" => $this->an,
      ]
    );
    // Add transaction
    if ($accounts = $this->a->getAccounts(false, false, isset($_REQUEST["type"]) ? $_REQUEST["type"] : '1')) {
      foreach ($accounts as $a) {
        $this->dta_file->addExchange(
          [
            "name"           => $a['account_owner'],
            "bank_code"      => $a['bank_index'],
            "account_number" => $a['account_number'],
          ],
          number_format($a['sum'], 2, '.', ''),
          [
            config('accountView')->getNumberAccount($a['a_number'], ACCOUNT_NUMBER) . " " . $this->place_name,
            "von" . " " . $a['name'] . " " . $a['surname'],
          ]
        );
      }
      // Save file
      header("Content-Disposition: attachment; filename=accounts_export_" . date('Y_m_d') . ".dta");
      header("Content-Type: application/x-force-download; name=\"accounts_export_" . date('Y_m_d') . ".dta\"");
      echo $this->dta_file->getFileContent();
      die;
    }
  }

  function downloadAboAccountsDTA()
  {
    // Initialize new DTA file
    //$dta_file = new DTA(DTA_DEBIT);

    // Set file sender
    $this->dta_file->setAccountFileSender(
      [
        "name"           => $this->n,
        "bank_code"      => $this->bc,
        "account_number" => $this->an,
      ]
    );

    // Add transaction
    if ($accounts = $this->a->getAboAccounts()) {
      foreach ($accounts as $a) {
        $sum = 0;
        if (is_array($a['reservations'])) {
          foreach ($a['reservations'] as $p) {
            $sum += $p['price'];
          }
        }
        $this->dta_file->addExchange(
          [
            "name"           => $a['account_owner'],
            "bank_code"      => $a['bank_index'],
            "account_number" => $a['account_number'],
          ],
          number_format($sum, 2, '.', ''),
          [
            config('accountView')->getNumberAccount($a['a_number'], ABO_ACCOUNT_NUMBER) . " " . $this->place_name,
            "von" . " " . $a['name'] . " " . $a['surname'],
          ]
        );
      }

      // Save file
      header("Content-Disposition: attachment; filename=accounts_abo_export_" . date('Y_m_d') . ".dta");
      header("Content-Type: application/x-force-download; name=\"accounts_abo_export_" . date('Y_m_d') . ".dta\"");
      echo $this->dta_file->getFileContent();
      die;
    }
  }

  function downloadOtherAccountsDTA()
  {
    // Initialize global vars
    global $n, $bc, $an, $verwendungszweck;
    // Initialize new DTA file
    $dta_file = new DTA(DTA_DEBIT);
    // Set file sender
    $dta_file->setAccountFileSender(
      [
        "name"           => $n,
        "bank_code"      => $bc,
        "account_number" => $an,
      ]
    );
    // Add transaction
    if ($accounts = $this->a->getOtherAccounts()) {
      foreach ($accounts as $a) {
        $dta_file->addExchange(
          [
            "name"           => $a['account_owner'],
            "bank_code"      => $a['bank_index'],
            "account_number" => $a['account_number'],
          ],
          number_format($a['sum'], 2, '.', ''),
          [
            config('accountView')->getNumberAccount($a['a_number'], OTHER_ACCOUNT_NUMBER) . " - Sonstiges",
            $verwendungszweck,
          ]
        );
      }
      // Save file
      header("Content-Disposition: attachment; filename=accounts_other_export_" . date('Y_m_d') . ".dta");
      header("Content-Type: application/x-force-download; name=\"accounts_other_export_" . date('Y_m_d') . ".dta\"");
      echo $dta_file->getFileContent();
      die;
    }
  }

  function downloadPrepaymentAccountsDTA()
  {
    // Initialize new DTA file
    //$dta_file = new DTA(DTA_DEBIT);
    // Set file sender
    $this->dta_file->setAccountFileSender(
      [
        "name"           => $this->n,
        "bank_code"      => $this->bc,
        "account_number" => $this->an,
      ]
    );
    // Add transaction
    if ($accounts = $this->a->getPrepaymentAccounts()) {
      foreach ($accounts as $a) {
        $this->dta_file->addExchange(
          [
            "name"           => $a['account_owner'],
            "bank_code"      => $a['bank_index'],
            "account_number" => $a['account_number'],
          ],
          number_format($a['sum'], 2, '.', ''),
          [
            config('accountView')->getNumberAccount($a['a_number'], PREPAYMENT_ACCOUNT_NUMBER) . " " . $this->place_name,
            "von" . " " . $a['name'] . " " . $a['surname'],
          ]
        );
      }
      // Save file
      header("Content-Disposition: attachment; filename=accounts_export_" . date('Y_m_d') . ".dta");
      header("Content-Type: application/x-force-download; name=\"accounts_export_" . date('Y_m_d') . ".dta\"");
      echo $this->dta_file->getFileContent();
      die;
    }
  }

  public function editPdfTemplate()
  {
    $this->page_key   = 'accounts_pdf_template';
    $templateFileName = paths()->getAssetsDir('files\\main_template.pdf');
    $templateFile     = Service::autoloader()->getPathFile($templateFileName, 'pdf') ? $templateFileName : null;
    if (isset ($this->message)) {
      $output[] = '<span class="message">' . $this->message . '</span>';
    }

    if (isset ($this->error)) {
      $output[] = '<span class="error">' . $this->error . '</span>';
    }
    $view     = new View();
    $out      = $view->render('accounts/_pdf_template', [
      'model'            => ConfigModel::getByType('account'),
      'templateFile'     => pathAs($templateFile, 'url'),
      'templateFileName' => pathAs($templateFileName, 'url'),
    ]);
    $output[] = $out;

    return $output;
  }

  public function updatePdfTemplate()
  {
    $this->page_key = 'accounts_pdf_template';
    $load           = Service::request()->all();
    foreach (array_keys($load) as $item) {
      if (in_array($item, ['type', 'action'])) {
        unset($load[$item]);
      }
    }
    $load['account_view_mail_view'] = (int)Service::request()->_('account_view_mail_view', 0);
    $load['account_view_nds_view']  = (int)Service::request()->_('account_view_nds_view', 0);
    $load['account_view_bank_view'] = (int)Service::request()->_('account_view_bank_view', 0);
    $config                         = new ConfigModel();
    if ($config->load((object)['account' => $load])) {
      if ($config->save()) {
        $this->message = lang('message_element_base_update', 'message_success');
      }
    }

    return $this->editPdfTemplate();
  }

  public function sepaForm($type = '')
  {
    ob_start();
    ?>
    &nbsp; &nbsp; <?= lang('Creation date', 'accounts') ?>: <input type="text" name="sepa_date_start" id="sepa_date_start"
                                                                   value="<?= date('d.m.Y') ?>"/>
    <?php
    //+5 рабочих дней (кроме пят, сб, вс)
    $d     = 0;
    $d_end = 7;
    while ($d < $d_end) {
      $next_date = strtotime("+" . $d . " day");
      $d++;
      if (date('N', $next_date) == 6 || date('N', $next_date) == 7) {
        $d_end++;
      }
    }
    ?>
    &nbsp;  <?= lang('Execution date', 'accounts') ?>: <input type="text" name="sepa_date_finish" id="sepa_date_finish" value="<?= date(
    'd.m.Y',
    $next_date
  ) ?>"/>
    <input type="hidden" name="account_type" value="<?= $type ?>" id="account_type">
    <input type="hidden" name="type" value="<?= Service::request()->_('type', 1) ?>">
    <input type="button" name="download" value="<?= lang('SEPA-Export', 'accounts') ?>" class="button"/>
    <div style="float:right; padding:4px 0px 0px 8px;">
      <a href="sepa_help.php" target="_blank">
        <img
          src="<?= base_url(paths()->getAssetsDir('images/buttons/sepa_help.png')) ?>" alt=""/>
      </a>
    </div>
    <?php
    return ob_get_clean();
  }

}
