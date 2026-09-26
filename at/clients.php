<?php

use AC\app\helpers\LayoutHelper;
use AC\app\services\DataService;
use AC\core\modules\clients\entities\dto\ClientDto;
use AC\core\modules\discounts\entities\enums\TypeDiscount;
use AC\core\system\db\Query;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\PasswordHelper;
use AC\core\system\helpers\StringHelper;
use AC\core\engines\Engines;
use AC\core\system\modules\modComm\helpers\ModCommHelper;

class clients_admin
{
  /**
   * @var Engines
   */
  public $r;

  protected $modes;
  private string $insert_error;

  function __construct()
  {
    //инфа о доступных режимах
    $this->modes = [
      1 => [
        'clients_online',
        lang('Online clients', 'clients'),
        lang('explanatory_text_about_online_customers', 'clients'),
      ],
      2 => [
        'clients_offline',
        lang('Offline clients', 'clients'),
        lang('explanatory_text_about_offline_clients', 'clients'),
      ],
      3 => ['clients_bar', lang('Bar clients', 'clients'), '&nbsp;'],
    ];
  }

  function start()
  {
    $this->r = new Engines();

    switch (Service::request()->_('action')) {
      //добавление
      case 'insertClientForm':
        return $this->insertClientForm();
        break;
      case 'insertClient':
        return $this->insertClient();
        break;
      //неактивные
      case 'viewInActive':
        return $this->viewInActive();
        break;
      //общий список
      case 'editClientData':
        return $this->editClientData();
        break;
      case 'editClientCardData':
        return $this->editClientCardData();
        break;
      case 'changeClientData':
        return $this->changeClientData();
        break;
      case 'changeClientCardData':
        return $this->changeClientCardData();
        break;
      case 'removeClient':
        return $this->removeClient();
        break;
      case 'removeSelected':
        return $this->removeSelected();
        break;
      //bar клиенты
      case 'viewBarClients':
        return $this->viewBarClients();
      case 'removeBarClient':
        return $this->removeBarClient();
      case 'removeBarClients':
        return $this->removeBarClients();
      //export клиентов
      case 'viewExportFormClients':
        return $this->viewExportFormClients();
      case 'exportRequest':
        return $this->exportRequest();
      case 'importClients':
        return $this->importClients();
      //Предоплата
      case 'setPrepayment':
        return $this->setPrepayment();


      //default
      default:
        return $this->getClientsList();
    }
  }


  /* ДОБАВЛЕНИЕ */

  //форма добавления клиента
  function insertClientForm()
  {
    prepareClass('client_insert');
    $add = "<h1>" . lang('Add clients', 'clients') . "</h1>\n";

    if (isset ($this->insert_message)) {
      $output[] = '<span class="message">' . $this->insert_message . '</span>';
      $row = [];
    } elseif (isset ($this->insert_error)) {
      $add .= '<span class="error">' . $this->insert_error . '</span>';
      //массив только что введенных значений
      $row = client_insert::getClientFormEnteredValues();
    } else {
      $row = [];
    }
    //форма добавления
    $managementQuery = (int)Service::request()->_('vereinsverwaltung', 0) === 1 ? '&vereinsverwaltung=1' : '';
    $add .= client_insert::getClientForm('clients.php?action=insertClient' . $managementQuery, 0, $row);

    $output[] = $add;

    $this->page_key = $managementQuery ? 'membership_fees_management_setup' : 'clients_insert';

    return $output;
  }


  //добавить клиента
  function insertClient()
  {
    prepareClass('client_insert');

    if (isset ($_POST['birthday'])) {
      //подготавливем _POST[birthday]
      if ($_POST['birthday'] != '') {
        $a = explode('.', $_POST['birthday']);
        if (count($a) == 3) {
          $_POST['birthday'] = $a[2] . '-' . $a[1] . '-' . $a[0];
        }
      }

      if (client_insert::proceedInsertClient(1, $this->r, $message, $new_client_id) == true) {
        $this->insert_message = $message;
      } else {
        $this->insert_error = $message;
      }
    }

    return $this->insertClientForm();
  }


  /* НЕАКТИВНЫЕ */

  function viewInActive()
  {


    //выходной массив
    $output = [];

    //блок сообщений для списка клиентов
    $list_messages = '';
    if (isset ($this->list_message)) {
      $list_messages .= '<span class="message">' . $this->list_message . '</span>';
    } elseif (isset ($this->list_error)) {
      $list_messages .= '<span class="error">' . $this->list_error . '</span>';
    }

    if ($list_messages != '') {
      $output[] = $list_messages;
    }

    //предупреждение о связанных записях
    $output[] = lang('explanatory_text_about_deleting_a_client', 'clients');

    //ключ страницы
    $this->page_key = 'clients_inactive';

    //список клиентов
    $out = '';
    $out .= "<h1>" . lang('Not activated clients', 'clients') . "</h1>\n";
    if ($this->r->clients->getClientsData(null, 0, $clients, 'registered desc')) {
      $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
      $out .= '	<tr>';
      $out .= '<th>' . lang('Client') . '</th>';
      $out .= '<th>' . lang('Login_title') . '</th>';
      $out .= '<th>' . lang('Name') . '</th>';
      $out .= '<th>' . lang('E-mail') . '</th>';
      $out .= '<th>' . lang('Phone') . '</th>';
      $out .= '<th>' . lang('Mobile') . '</th>';
      $out .= '<th>' . lang('Registered on') . '</th>';
      $out .= '<th colspan="2">' . lang('Action') . '</th></tr>' . "\n";
      foreach ($clients as $client) {
        //перебираем клиентов
        $out .= '	<tr>';
        $out .= '<td class="dark">' . ($client['mode'] == 1 ? lang('Online') : lang('Offline')) . '</td>';
        $out .= '<td class="light"' . ($client['mode'] == 1 ? '>' . $client['login'] : ' align="center">-') . '</td>';
        $out .= '<td class="dark">' . LayoutHelper::renderClientInfoHref(
            $client['name'],
            $client['surname'],
            $client['client_id']
          ) . '</td>';
        $out .= '<td class="light"' . ($client['email'] ? '><a href="mailto:' . $client['email'] . '">' . $client['email'] . '</a>'
            : ' align="center">-') . '</td>';
        $out .= '<td class="dark">' . $client['phone'] . '</td>';
        $out .= '<td class="light">' . $client['phone_mobile'] . '</td>';
        $out .= '<td class="dark" align="center">' . date('d.m.Y', strtotime($client['registered'])) . '</td>';
        $out .= '<td class="light"><a href="clients.php?action=editClientData&client_id=' . $client['client_id'] . '&inActive=true" class="btnEdit">' . lang('button_update') . '</a></td>';
        $out .= '<td class="light"><a href="clients.php?action=removeClient&client_id=' . $client['client_id'] . '&inActive=true" onclick="return ifConfirm ()" class="btnRemove">' . lang('button_remove') . '</a></td>';
        $out .= '</tr>' . "\n";
      }
      $out .= "</table>\n";
    }
    $output[] = $out;

    return $output;
  }


  /* ОБЩИЙ СПИСОК */

  //удалить клиента
  function removeClient()
  {
    if (isset ($_GET['client_id'])) {
      $this->r->clients->removeClient((int)$_GET['client_id']);
      $this->list_message = lang('The client is deleted', 'message_success');
    } else {
      $this->list_error = '<b>' . lang('The client is deleted', 'message_success', 'message_error') . '</b>';
    }

    if (isset ($_GET['inActive'])) {
      return $this->viewInActive();
    } else {
      return $this->getClientsList(isset ($_GET['mode']) ? $_GET['mode'] : null);
    }
  }

  //удалить всех выделенных клиентов
  function removeSelected()
  {
    if (isset ($_POST['ids']) && is_array($_POST['ids']) && !empty ($_POST['ids'])) {
      foreach ($_POST['ids'] as $id) {
        $this->r->clients->removeClient((int)$id);
      }
    }

    return $this->getClientsList(3);
  }

  //форма редактирования данных клиента
  function editClientData()
  {
    if (isset ($_POST['client_id'])) {
      $client_id = (int)$_POST['client_id'];
    } elseif (isset ($_GET['client_id'])) {
      $client_id = (int)$_GET['client_id'];
    } else {
      return $this->getClientsList();
    }

    if ($this->r->clients->getClientData($client_id, $client_data)) {
      //выходной массив
      $out_array = [];

      //ключ страницы
      $this->page_key = $this->modes[$client_data['mode']][0];

      $out = "<h1>" . lang('Changing clients', 'clients') . "</h1>\n";

      //общий класс добавления
      prepareClass('client_insert');

      //сообщение об ошибке
      if (isset ($this->edit_error)) {
        $out .= "<span class=\"error\">" . $this->edit_error . "</span>\n";
        $client_data = array_merge($client_data, client_insert::getClientFormEnteredValues());
      }

      //форма редактирование
      $out_array[] = $out . client_insert::getClientForm(
          'clients.php?action=changeClientData&client_id=' . $client_id . (isset ($_GET['inActive']) ? '&inActive=true' : '') . (isset($_GET['alpha'])
            ? '&alpha=' . $_GET['alpha'] : ''),
          1,
          $client_data
        );

      //ссылка назад
      $out_array[] = '<span class="back"><a href="clients.php?mode=' . $client_data['mode'] . (isset($_GET['alpha']) ? '&alpha=' . $_GET['alpha']
          : '') . '">' . lang('Back') . '</a></span>';

      return $out_array;
    } else {
      $this->list_error = '<b>' . lang('Client not found', 'message_error') . '</b>';

      return $this->getClientsList();
    }
  }

  //форма редактирования данных карточки
  function editClientCardData()
  {
    if (isset ($_POST['client_id'])) {
      $client_id = (int)$_POST['client_id'];
    } elseif (isset ($_GET['client_id'])) {
      $client_id = (int)$_GET['client_id'];
    } else {
      return $this->getClientsList();
    }

    if ($this->r->clients->getClientData($client_id, $client_data)) {
      //выходной массив
      $out_array = [];

      //ключ страницы
      $this->page_key = $this->modes[$client_data['mode']][0];

      $out = "<h1>" . lang('Change code', 'clients') . "</h1>\n";

      $out .= '<form action="clients.php?action=changeClientCardData&client_id=' . $client_id . (isset ($_GET['inActive']) ? '&inActive=true'
          : '') . (isset($_GET['alpha']) ? '&alpha=' . $_GET['alpha'] : '') . '" method="post">' . "\n";
      $out .= '<input type="hidden" name="client_id" value="' . $client_id . '" />';

      $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
      $out .= '<tr>';
      $out .= '<th class="light" colspan="2">' . $client_data['name'] . ' ' . $client_data['surname'] . '</th>';
      $out .= '</tr>' . "\n";
      $out .= '<tr>';
      $out .= '<td class="light" colspan="2">' . lang('Please hold the card up to the reader!', 'clients') . '</td>';
      $out .= '</tr>' . "\n";
      $out .= '<tr>';
      $out .= '<td class="light">' . lang('Code') . '</td>';
      $out .= '<td class="dark"><input type="text" id="codeCartenField" name="codecard" value="" style="font-size:20px;"/></td>';
      $out .= '</tr>' . "\n";
      $out .= '<tr>';
      $out .= '<th class="light" colspan="2">&nbsp;</th>';
      $out .= '</tr>' . "\n";
      $out .= "</table>\n";
      $out .= "</form>\n";
      $out .= "<script>\n";
      $out .= "todo.onload(function(){document.getElementById('codeCartenField').focus()})\n";
      $out .= "</script>\n";
      $out_array[] = $out;

      //ссылка назад
      $out_array[] = '<span class="back"><a href="clients.php?mode=' . $client_data['mode'] . (isset($_GET['alpha']) ? '&alpha=' . $_GET['alpha']
          : '') . '">' . lang('Back') . '</a></span>';

      return $out_array;
    } else {
      $this->list_error = '<b>' . lang('Client not found', 'message_error') . '</b>';

      return $this->getClientsList();
    }
  }

  //изменение данных клиента
  function changeClientData()
  {
    if (isset ($_GET["client_id"])) {
      $client_id = (int)$_GET["client_id"];

      prepareClass('client_insert');
      if (client_insert::checkClientFormRecieving(1)) {
        //подготавливем _POST[birthday]
        if ($_POST['birthday'] != '') {
          $a = explode('.', $_POST['birthday']);
          if (count($a) == 3) {
            $birthday = $a[2] . '-' . $a[1] . '-' . $a[0];
          } else {
            $birthday = null;
          }
        } else {
          $birthday = null;
        }

        if ($_POST['bank_sepa_mandat'] != '') {
          $a = explode('.', $_POST['bank_sepa_mandat']);
          if (count($a) == 3) {
            $bank_sepa_mandat = $a[2] . '-' . $a[1] . '-' . $a[0];
          } else {
            $bank_sepa_mandat = null;
          }
        } else {
          $bank_sepa_mandat = null;
        }

        $area_type = 0;
        if (isset($_POST['area_type1'])) {
          $area_type = (int)$_POST['area_type1'];
        }

        if (isset($_POST['area_type2'])) {
          $area_type = ($area_type == 0 ? (int)$_POST['area_type2'] : 0);
        }
        if ($this->r->clients->changeClientData(
          $client_id,
          1,
          (int)$_POST['mode'],
          $area_type,
          (int)isset ($_POST['active']) + 1,
          (int)$_POST['club_state'],
          (int)$_POST['encash'],
          isset ($_POST['login']) ? $_POST['login'] : null,
          isset ($_POST['password']) ? $_POST['password'] : null,
          isset ($_POST['password_confirmation']) ? $_POST['password_confirmation'] : null,
          $_POST['number'],
          $_POST['name'],
          $_POST['surname'],
          $birthday,
          $_POST['phone'],
          $_POST['phone_mobile'],
          $_POST['fax'],
          $_POST['post_code'],
          $_POST['city'],
          $_POST['address'],
          $_POST['email'],
          [
            $_POST['bank_account_holder'],
            isset($_POST['bank_account_number']) ? $_POST['bank_account_number'] : null,
            isset($_POST['bank_identifier_code']) ? $_POST['bank_identifier_code'] : null,
            $_POST['bank_name'],
          ],
          [
            $_POST['bank_iban'],
            $_POST['bank_bic'],
            $bank_sepa_mandat,
            $_POST['bank_sepa_referenz'],
            $_POST['sepa_type'] ?? 0,
            (int)isset($_POST['sepa_standart']),
          ],
          $_POST['nds'],
          $_POST['discount'],
          $_POST['limit_day'],
          ((isset($_POST['stock_id']) && is_array($_POST['stock_id'])) ? $_POST['stock_id'] : false),
          ((isset($_POST['sprice_id']) && is_array($_POST['sprice_id'])) ? $_POST['sprice_id'] : false),
          $error_code,
          $data_check_results,
          $this->getUnavailableSportsList(Service::request()->_('available_sport', [])),
          isset($_POST['super']) ? $_POST['super'] : 0,
          isset($_POST['abo_delete']),
          isset($_POST['student']) ? $_POST['student'] : "0",
          isset($_POST['student_number']) ? $_POST['student_number'] : "",
          isset($_POST['firm']) ? $_POST['firm'] : "",
          isset($_POST['refund_for_ticket']),
          isset($_POST['refund_for_paypal']),
          $_POST['lang'] ?? null,
          $_POST['country'] ?? null,
          (int)isset($_POST['show_client_data'])
        )
        ) {
          //данные изменены
          $this->list_message = lang('The client data has been changed.', 'message_success');

          return isset ($_GET['inActive']) ? $this->viewInActive() : $this->getClientsList((int)$_POST['mode']);
        } else {
          //данные не изменены
          if ($error_code == 2) {
            $this->edit_error = client_insert::explainClientDataCheckResult($data_check_results);

            return $this->editClientData();
          } else {
            //клиент не найден
            return isset ($_GET['inActive']) ? $this->viewInActive() : $this->getClientsList((int)$_POST['mode']);
          }
        }
      } else {
        //не все данные переданы
        return isset ($_GET['inActive']) ? $this->viewInActive() : $this->getClientsList();
      }
    } else {
      //id не передан
      return isset ($_GET['inActive']) ? $this->viewInActive() : $this->getClientsList();
    }
  }

  function getUnavailableSportsList($_availableSports = [])
  {
    return module('clients')->useModel('clientsRegistration')->getUnavailableSportByTypeBySeason($_availableSports);
  }

  function changeClientCardData()
  {
    if (isset ($_GET["client_id"])) {
      $client_id = (int)$_GET["client_id"];

      if ($this->r->clients->changeClientCardData($client_id, $_POST['codecard'], $error_code)) {
        //данные изменены
        $this->list_message = lang('The client data has been changed.', 'message_success');

        return isset ($_GET['inActive']) ? $this->viewInActive() : $this->getClientsList(1);
      } else {
        //данные не изменены
        if ($error_code == 2) {
          $this->edit_error = client_insert::explainClientDataCheckResult($data_check_results);

          return $this->editClientData();
        } else {
          //клиент не найден
          return isset ($_GET['inActive']) ? $this->viewInActive() : $this->getClientsList(1);
        }
      }
    } else {
      //id не передан
      return isset ($_GET['inActive']) ? $this->viewInActive() : $this->getClientsList();
    }
  }

  //список клиентов
  function getClientsList($mode = null)
  {


    //выходной массив
    $output = [];

    //блок сообщений для списка клиентов
    $list_messages = '';

    if (isset ($this->list_message)) {
      $list_messages .= '<span class="message">' . $this->list_message . '</span>';
    } elseif (isset ($this->list_error)) {
      $list_messages .= '<span class="error">' . $this->list_error . '</span>';
    }

    if ($list_messages != '') {
      $output[] = $list_messages;
    }

    //предупреждение о связанных записях
    $output[] = lang('explanatory_text_about_deleting_a_client', 'clients');

    //определяем режим
    if ($mode === null) {
      $mode = isset ($_GET['mode']) && isset ($this->modes[$_GET['mode']]) ? $_GET['mode'] : 1;
    }

    //ключ страницы
    $this->page_key = $this->modes[$mode][0];

    //список клиентов
    $out = '';
    $out .= '<h1>' . $this->modes[$mode][1] . "</h1>\n" . $this->modes[$mode][2] . "\n";
    //Выводим алфавит
    if ($alpha_list = $this->r->clients->getFirstAlphaSurname($mode, null)) {
      $out .= '<p style="text-align:center; font-size:14px">';
      $s = '';
      if (isset($_GET['alpha'])) {
        $alpha_sel = $_GET['alpha'];
      }

      foreach ($alpha_list as $a) {
        if (!isset($alpha_sel)) {
          $alpha_sel = $a;
        }

        if ($alpha_sel == $a) {
          $out .= $s . '<strong>' . $a . '</strong>';
        } else {
          $out .= $s . '<a href="clients.php?mode=' . $mode . '&alpha=' . $a . '">' . $a . '</a>';
        }

        $s = ' | ';
      }
      $out .= '</p>';
    }

    //берем список счетов для предоплаты
    if (!$accounts = getEngine('accounts', false)?->getActivePrepaymentAccountsForClients()) {
      $accounts = [];
    }

    $out .= '<div class="pupopWindowPlace" id="pupopWindowPlace"></div>';
    $out .= '<div class="pupopWindow" id="pupopWindow"></div>';

    if ($this->r->clients->getClientsData($mode, null, $clients, 'surname, name', $alpha_sel)) {
      //клиенты найдены
      $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide hover-tr">' . "\n";
      $out .= '	<tr>';
      if ($mode == 1) {
        $out .= '<th>' . lang('Login_title') . '</th>';
        $out .= '<th>' . lang('Code card') . '</th>';
      }
      $out .= '<th>' . lang('Name') . '</th>';
      $out .= '<th>' . lang('E-mail') . '</th>';
      $out .= '<th>' . lang('Phone') . '</th>';
      $out .= '<th>' . lang('Mobile') . '</th>';
      $out .= '<th>' . lang('Status') . '</th>';
      $out .= '<th>' . lang('Active') . '</th>';
      $out .= '<th>' . lang('Payment method') . '</th>';
      $out .= '<th>' . lang('GH stand', 'clients') . ' ' . CURR_VALUTE . '</th>';
      $out .= '<th colspan="3">' . lang('GH invoices', 'clients') . '</th>';
      if (config('account')->useSEPA()) {
        $out .= '<th>' . lang('SEPA_title') . '</th>';
      }
      $out .= '<th>' . lang('Registered on') . '</th>';
      $out .= '<th colspan="3">' . lang('Action') . '</th></tr>' . "\n";

      $out .= "<script>
				    var popup = new popupAjaxWindow('pupopWindowPlace', 'pupopWindow');
				 </script>";

      foreach ($clients as $client) {
        //перебираем клиентов
        $out .= '	<tr>';

        if ($mode == 1) {
          $out .= '<td class="dark" title="' . $client['login'] . '">' . StringHelper::cropStr($client['login'], 15) . '</td>';
          $out .= '<td class="dark">' . $client['codecard'] . '</td>';
        }

        $out .= '<td class="light" nowrap title="' . $client['surname'] . ' ' . $client['name'] . '">' . LayoutHelper::renderClientInfoHref(
            $client['name'],
            $client['surname'],
            $client['client_id']
          ) . '</td>';
        $out .= '<td class="dark"' . ($client['email'] ? ' title="' . $client['email'] . '"><a href="mailto:' . $client['email'] . '">' . StringHelper::cropStr($client['email'],
              15) . '</a>'
            : ' align="center">-') . '</td>';
        $out .= '<td class="light">' . $client['phone'] . '</td>';
        $out .= '<td class="dark">' . $client['phone_mobile'] . '</td>';
        $out .= '<td class="light" style="white-space: nowrap">' . (isset($client['club_state']) ? ((($client['club_state'] == 1) ? 'NM'
              : ('V' . ($client['club_state'] - 1))) . '/ ') : '') . (isset($client['nds_rate']) ? $client['nds_rate'] : 0) . '%</td>';
        $out .= '<td class="dark" align="center">' . ($client['active'] == 1 ? lang('Yes') : lang('Not')) . '</td>';
        $out .= '<td class="light" align="center">' . ($client['encash'] == 1
            ? lang('Invoice payment')
            : ($client['encash'] == 2 ? lang('Credit balance')
              : lang('Cash payment'))) . '</td>';

        //деньги
        $out .= '<td class="dark" align="center">' . number_format(
            $client['prepayment_sum'],
            2,
            ',',
            ''
          ) . '</td>';
        //счета
        $out_close = $out_open = '';
        $count_ok = 0;

        if (isset($accounts[$client['client_id']])) {
          foreach ($accounts[$client['client_id']] as $account_id => $status) {
            if ($status == 1) {
              $count_ok++;
            } else {
              $out_open .= '<a href="#" onclick="return popup.createWindow(\'get_ajax_data.php?action=getPrepaymentAccount&account_id=' . $account_id . '&client_id=' . $client['client_id'] . '&alpha=' . $alpha_sel . '\')"><img src="' . base_url(paths()->getAssetsDir('images/buttons/prepayment.gif')) . '" /></a> ';
            }
          }
          $out_close .= $count_ok ? $count_ok . '<img src="' . base_url(paths()->getAssetsDir('images/buttons/prepayment_ok.gif')) . '" />' : '';
        }

        $out .= '<td class="light" align="center">' . $out_close . '</td>';
        $out .= '<td class="dark" align="center">' . $out_open . '</a></td>';
        $out .= '<td class="light" align="center"><a href="' . Service::structure()->getPageHrefByKey('clients_private_account') . '?client_id=' . $client['client_id'] . '" onclick="return showUserInfo (this)" target="_blank" class="btnInfo"></a></td>';

        unset($out_close);
        unset($out_open);
        if (config('account')->useSEPA()) {
          $color_bullet = 'red';
          if (strlen($client['name']) >= 0 && strlen($client['surname']) >= 0 && strlen($client['bank_iban']) > 0 && strlen($client['bank_bic']) > 0
            && strlen($client['bank_sepa_mandat'] ?? '') > 0
            && strlen($client['bank_sepa_referenz'] ?? '') > 0
          ) {
            $color_bullet = 'green';
          }
          $out .= '<td class="dark" align="center"><img src="' . base_url(
              paths()->getAssetsDir('images/bullet_' . $color_bullet . '.gif')
            ) . '"/></td>';
        }

        $out .= '<td class="light" align="center">' . date('d.m.Y', strtotime($client['registered'])) . '</td>';
        $out .= '<td class="dark"><a href="clients.php?action=editClientCardData&client_id=' . $client['client_id'] . '&alpha=' . $alpha_sel . '" class="btnCodeCard">' . lang('Code card') . '</a></td>';
        $out .= '<td class="dark"><a href="clients.php?action=editClientData&client_id=' . $client['client_id'] . '&alpha=' . $alpha_sel . '" class="btnEdit">' . lang('button_update') . '</a></td>';
        $out .= '<td class="dark"><a href="clients.php?action=removeClient&client_id=' . $client['client_id'] . '&mode=' . $mode . '&alpha=' . $alpha_sel . '" onclick="return ifConfirm(\'' . lang('Client Name remove',
            'message_notify',
            ['name' => $client['surname'] . ' ' . $client['name']]) . '\')" class="btnRemove">' . lang('button_remove') . '</a></td>';

        $out .= '</tr>' . "\n";
      }
      $out .= "</table>\n";
    }
    $output[] = $out;

    return $output;
  }

  function viewBarClients()
  {
    //выходной массив
    $output = [];

    //блок сообщений для списка клиентов
    $list_messages = '';

    if (isset ($this->list_message)) {
      $list_messages .= '<span class="message">' . $this->list_message . '</span>';
    } elseif (isset ($this->list_error)) {
      $list_messages .= '<span class="error">' . $this->list_error . '</span>';
    }

    if ($list_messages != '') {
      $output[] = $list_messages;
    }

    //предупреждение о связанных записях
    $output[] = lang('explanatory_text_about_deleting_a_client', 'clients');

    $mode = 3;

    //ключ страницы
    $this->page_key = $this->modes[$mode][0];

    //список клиентов
    $out = '';
    $out .= '<h1>' . $this->modes[$mode][1] . "</h1>\n" . $this->modes[$mode][2] . "\n";
    if ($this->r->clients->getBarClients($clients)) {
      //клиенты найдены
      $out .= '<form action="clients.php?action=removeBarClients" method="post" name="offlineClients">' . "\n";
      $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
      $out .= '	<tr>';
      $out .= '<th>' . lang('Name') . '</th>';
      $out .= '<th>' . lang('Reservation count', 'clients') . '</th>';
      $out .= '<th>' . lang('Registered on') . '</th>';
      $out .= '<th colspan="2">' . lang('Action') . '</th></tr>' . "\n";

      foreach ($clients as $client) {
        //перебираем клиентов
        $out .= '	<tr>';
        $out .= '<td class="light">' . StringHelper::shield($client[0] . ' ' . $client[1]) . '</td>';
        $out .= '<td class="dark" align="center">' . $client[2] . '</td>';
        $out .= '<td class="light" align="center">' . date('d.m.Y', strtotime($client[3])) . '</td>';
        $out .= '<td class="dark"><a href="clients.php?action=removeBarClient&name=' . $client[0] . '&surname=' . $client[1] . '" onclick="return ifConfirm ()" class="btnRemove">' . lang('button_remove') . 'n</a></td>';
        $out .= '<td class="dark np"><input type="checkbox" name="names[]" value="' . md5($client[0]) . ',' . md5(
            $client[1]
          ) . '"></td>';
        $out .= '</tr>' . "\n";
      }

      $out .= '	<tr><td class="dark" colspan="5" align="right"><a href="javascript:document.forms.offlineClients.submit()" onclick="return ifConfirm ()" class="btnRemove">' . lang('Delete all marked entries.',
          'clients') . '</a></td></tr>' . "\n";

      $out .= "</table>\n";
      $out .= "</form>\n";
    }
    $output[] = $out;

    return $output;
  }

  private function getFieldsClientsExport()
  {
    $sportByType = ModCommHelper::get('areas', 'relevantSportsByType', ['useSeason' => true], 'sportsByType', []);

    return [
      'login'                        => ['title' => lang('Login_title')],
      'club_state'                   => ['title' => lang('Membership status')],
      'name'                         => ['title' => lang('First name')],
      'surname'                      => ['title' => lang('Family name')],
      'phone'                        => ['title' => lang('Phone')],
      'phone_mobile'                 => ['title' => lang('Mobile')],
      'fax'                          => ['title' => lang('Fax')],
      'email'                        => ['title' => lang('E-mail')],
      'number'                       => ['title' => lang('Mg.no')],
      'post_code'                    => ['title' => lang('Zip')],
      'city'                         => ['title' => lang('City')],
      'address'                      => ['title' => lang('Address')],
      'birthday'                     => ['title' => lang('Birthday')],
      'nds'                          => ['title' => lang('Value added tax')],
      'account_owner'                => ['title' => lang('Bank account holder')],
      'bank_name'                    => ['title' => lang('Bank name')],
      'bank_iban'                    => ['title' => lang('IBAN')],
      'bank_bic'                     => ['title' => lang('BIC')],
      'bank_sepa_mandat'             => ['title' => lang('SEPA_mandate')],
      'bank_sepa_referenz'           => ['title' => lang('SEPA reference')],
      'registered'                   => ['title' => lang('Registration date')],
      'date_last_reservation'        => ['title' => lang('Date of the last booking')],
      'count_reservations'           => ['title' => lang('Number of bookings')],
      'date_last_played_reservation' => ['title' => lang('Date of last played booking')],
      'date_next_reservation'        => ['title' => lang('Date of next future booking')],
      'prepayment_sum'               => ['title' => lang('Guthaben')],
      'student'                      => ['title' => lang('parameter_registration_field_student', 'registration_fields')],
      'abo_delete'                   => ['title' => lang('parameter_user_can_remove_abo', 'config')],
      'codecard'                     => ['title' => lang('Code card')],
      'discount'                     => [
        'title'  => lang('Discount'),
        'fields' => [
          'code'          => lang('Code'),
          'booking_price' => lang('Individual bookings', 'config_discount'),
          'abo_price'     => lang('Abo discounts', 'config_discount'),
        ],
      ],
      'stock_id'                     => [
        'title'  => lang('Booking options'),
        'fields' => [
          'code'  => lang('Code'),
          'price' => lang('Price'),
        ],
      ],
      'sprice_id'                    => [
        'title'  => lang('Special price'),
        'fields' => [
          'code'  => lang('Code'),
          'price' => lang('Price'),
        ],
      ],
      'unavailable_sports'           => [
        'title'  => lang('Available sports'),
        'fields' => array_map(static fn($v) => $v->title_site_url, $sportByType),
      ],
    ];
  }

//Export
  function viewExportFormClients()
  {
    //выходной массив
    $output = [];
    //ключ страницы
    $managementQuery = (int)Service::request()->_('vereinsverwaltung', 0) === 1 ? '&vereinsverwaltung=1' : '';
    $this->page_key = $managementQuery ? 'membership_fees_management_export' : 'clients_export';
    if (isset ($this->insert_error)) {
      $output[] = '<span class="error">' . $this->insert_error . '</span>';
    }

    $out = '<form action="clients.php?action=exportRequest' . $managementQuery . '" method="post" >' . "\n";
    $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
    $out .= '<tr><td class="dark">' . lang('Fields') . '</td><td class="dark">' . "\n";
    $out .= '<select name="fields[]" multiple="multiple" size="10">';
    foreach ($this->getFieldsClientsExport() as $field => $fieldValue) {
      $out .= '<option value="' . $field . '"> ' . $fieldValue['title'] . '</option>';

    }
    $out .= '</select>';
    $out .= '</td></tr>' . "\n";
    $out .= '<tr><td class="light">' . lang('title_choice_type_sport') . '</td><td class="light">' . "\n";
    $out .= '<input type="radio" name="sbt" value="" checked> ' . lang('All') . '<br>';
    foreach (ModCommHelper::get('areas', 'relevantSportsByType', [], 'sportsByType', []) as $key => $a) {
      $out .= '<input type="radio" name="sbt" value="' . $key . '"> ' . $a->title_site_url . '<br>';
    }
    $out .= '</td></tr>' . "\n";
    $out .= '<tr><td colspan="2" align="center"><input type="submit" name="import" value = "' . lang('Export client data',
        'clients') . '" class="button"/></td></tr>' . "\n";
    $out .= '</table>' . "\n";
    $out .= "</form>\n";

    $output[] = $out;

    return $output;
  }

  function exportRequest()
  {
    if (($usedFields = Service::request()->_post('fields', [])) && count($usedFields)) {
      $fieldsByType = ['count_reservations', 'date_last_reservation', 'date_last_played_reservation', 'date_next_reservation'];
      $titles = [];
      $sbtKey = Service::request()->_post('sbt', '');
      [$type_id, $sport_id] = array_map('intval', array_pad(explode('_', $sbtKey), 2, 0));
      $selectFields = ['client_id'];
      $useFieldsByReservations = false;
      foreach ($this->getFieldsClientsExport() as $fieldKey => $field) {
        if (in_array($fieldKey, $usedFields, true)) {
          if (in_array($fieldKey, $fieldsByType, true)) {
            $useFieldsByReservations = true;
          }
          $titles[0][] = $field['title'];
          if (isset($field['fields'])) {
            $titles[1] = array_pad($titles[1] ?? [], (count($titles[0]) - 1), '');
            $titles[0] = array_pad($titles[0], (count($titles[0]) + count($field['fields']) - 1), '');
            foreach ($field['fields'] as $fieldValue) {
              $titles[1][] = $fieldValue;
            }
          }
          if (in_array($fieldKey, ClientDto::getProperties(), true)) {
            $selectFields[] = $fieldKey;
          }
        }
      }
      $clientsDataByType = $useFieldsByReservations ? $this->r->getClientsReservationsBySport($type_id, $sport_id) : [];
      $out[] = implode(';', $titles[0]);
      if(!empty($titles[1])) {
        $out[] = implode(';', $titles[1]);
      }
      $nds = DataService::nds();
      $stocks = DataService::stocks();
      $specprice = DataService::specPrices();
      $discount = DataService::discounts();
      $clubStateModel = DataService::clubStateModel();
      if ($this->r->clients->getClientsData(null, null, $clients,
        'c.surname, c.name', null, implode(', ', $selectFields)/*, [1000, 1000]*/)) {
        foreach ($clients as $item) {
          if (!$useFieldsByReservations || !$sbtKey || array_key_exists($item['client_id'], $clientsDataByType)) {
            $a = [];
            foreach ($usedFields as $field) {
              switch ($field) {
                case 'club_state':
//                  $a[] = ($item['club_state'] == 0 ? 'Nichtmitglied' : ($item['club_state'] == 1 ? 'TABB Mitglied' : 'SVB Mitglied'));
                  $a[] = $clubStateModel::getItemById($item['club_state'])->getTitle();
                  break;
                case 'nds':
                  $a[] = isset($nds[$item['nds']]) ? $nds[$item['nds']]->rate . ' %' : '';
                  break;
                case 'date_last_reservation':
                case 'date_last_played_reservation';
                case 'date_next_reservation':
                  $a[] = isset($clientsDataByType[$item['client_id']][$field]) ? date('d.m.Y H:i',
                    strtotime($clientsDataByType[$item['client_id']][$field])) : '';
                  break;
                case 'count_reservations':
                  $a[] = (int)isset($clientsDataByType[$item['client_id']]['count_reservations']) ? $clientsDataByType[$item['client_id']]['count_reservations'] : 0;
                  break;
                case 'birthday':
                case 'registered':
                case 'bank_sepa_mandat':
                  $a[] = $item[$field] && strtotime($item[$field]) > 0 ? date('d.m.Y' . ($field == 'registered' ? ' H:i' : ''), strtotime
                  ($item[$field])) :
                    '';
                  break;
                case 'bank_iban':
                  $a[] = StringHelper::mask($item['bank_iban'], 5, 2, 'X');
                  break;
                case 'student':
                case 'abo_delete':
                  $a[] = $item[$field] ? lang('Yes') : lang('No');
                  break;
                case 'discount':
                  $discountValue = !empty($item['discount']) && isset($discount[$item['discount']]) ? $discount[$item['discount']] : null;
                  $a[] = $discountValue?->getTitle() ?? '';
                  $a[] = $discountValue?->label(TypeDiscount::Client) ?? '';
                  $a[] = $discountValue?->label(TypeDiscount::Abo) ?? '';
                  break;
                case 'stock_id':
                case 'sprice_id':
                  $items = ['code' => [], 'price' => []];
                  $mainOpt = match ($field) {
                    'stock_id'  => $stocks,
                    'sprice_id' => $specprice,
                  };
                  foreach (Service::cast('array')->get($item[$field]) as $item_id) {
                    if (isset($mainOpt[$item_id])) {
                      $items['code'][] = $mainOpt[$item_id]?->code ?? '';
                      $items['price'][] = $mainOpt[$item_id]?->amount() ?? '';
                    }
                  }
                  $a[] = implode('|', $items['code']);
                  $a[] = implode('|', $items['price']);
                  break;
                case 'unavailable_sports':
                  $unavailable_sports = explode(';', $item['unavailable_sports']);
                  foreach (array_keys($this->getFieldsClientsExport()[$field]['fields']) as $key) {
                    $a[] = !in_array($key, $unavailable_sports) ? lang('Yes') : lang('No');;
                  }
                  break;
                default:
                  $a[] = html_entity_decode($item[$field] ?? '');
              }
            }
            $out[] = join(';', $a);
          }
        }
        header('Content-Disposition: attachment; filename=clients_export.csv');
        header("Content-Type: application/x-force-download; name=\"clients_export.csv\"; charset=utf-8");
        echo "\xEF\xBB\xBF" . implode("\n", $out);
        die;
      }
    } else {
      $this->insert_error = lang('input data error', 'message_error');
    }

    return $this->viewExportFormClients();
  }

  //несколько клиентов
  function removeBarClients()
  {
    if (isset ($_POST['names']) && !empty ($_POST['names'])) {
      foreach ($_POST['names'] as $name) {
        [$name_md5, $surname_md5] = explode(',', $name);
        $this->r->clients->removeBarClient($name_md5, $surname_md5, true);
      }
    }

    return $this->viewBarClients();
  }

  //удаление одного клиента
  function removeBarClient()
  {
    if (isset ($_GET['name']) && isset ($_GET['surname'])) {
      $this->r->clients->removeBarClient($_GET['name'], $_GET['surname']);
    }

    return $this->viewBarClients();
  }

  function importClients()
  {
    $handle = fopen("clients.csv", "r");
    $client_tmp = $clients = [];
    $_clients = $this->r->clients->getLoginByClientIds();
    foreach ($_clients as $_c) {
      $clients[] = $_c->login;
    }
    while (($data = fgetcsv($handle, 1000, ";", '"')) !== false) {
      $characterNumberName = 0;
      do {
        $characterNumberName++;
        $login = StringHelper::generateLoginByNameSurname($data[2], $data[1], $characterNumberName);
        if (!isset($client_tmp[$login])) {
          break;
        }
      } while (true);
      $client = [
        'login'         => $login,
        'password'      => empty(str_replace(' ', '', $data[11])) ? PasswordHelper::generatePassword(10) : str_replace(' ', '', $data[11]),
        'name'          => $data[2],
        'surname'       => $data[1],
        'birthday'      => date('Y-m-d', strtotime($data[7])),
        'number'        => '',
        'city'          => $data[5],
        'post_code'     => $data[4],
        'address'       => $data[3],
        'email'         => $data[6],
        'iban'          => str_replace(' ', '', $data[8]),
        'bic'           => str_replace(' ', '', $data[9]),
        'sepa_referens' => $data[10],
        'sepa_mandat'   => '2020-02-01',
        'phone'         => $data[12],

      ];
      $client_tmp[$client['login']]['count'] += 1;
      $client_tmp[$client['login']]['clients'][] = $client;
    }
    fclose($handle);
    $count = 0;
    echo '<table border="1" cellpadding="2" cellspacing="2">';
    echo '<tr><th>Familienname:</th><th>Vorname:</th><th>Benutzername:</th><th>Password:</th>  </tr>';
    foreach ($client_tmp as $login => $item) {
      foreach ($item['clients'] as $k => $client) {
        echo '<tr ><td>' . $client['surname'] . ' </td><td>' . $client['name'] . '</td><td> ' . $client['login'] . '</td><td>' . $client['password'] . "</td></tr>";
        if ($item['count'] > 1 && $k > 0) {
//          echo '<div style="color: red">Генерация логина создала повтор: ' . $client['login'] . ' ; '/** . $client['password'] . ' ; ' **/. $client['surname'] . ' ; ' . $client['name'] . ' ; ' . $client['email'] . "</div>";
//          echo '<tr ><td> ' . $client['login'] . '</td><td>' . $client['surname'] . ' </td><td>' . $client['name'] . '</td><td>' . $client['email'] . "</td></tr>";
        } else {
          if (in_array($client['login'], $clients)) {
            echo '<div style="color: red">Такой логин уже есть в системе :' . $client['login'] . '</div>';
          } else {
            echo '<div>';
            echo $client['login'] . ' ; ' . $client['password'] . ' ; ' . $client['name'] . ' ; ' . $client['surname'] . ' ';
            if (Query::sqlQuery(
              'insert into ' . Query::tableName('clients') . '
                set
                  mode = "1",
                  active = "1",
                  encash = "1",
                  club_state = "2" ,
                  nds = "4",
                  discount = NULL,
                  login = "' . addslashes($client['login']) . '",
                  password_md5 = "' . md5($client['password']) . '"' . ',
                  number = "' . $client['number'] . '",
                  name = "' . addslashes($client['name']) . '",
                  surname = "' . addslashes($client['surname']) . '",
                  birthday = "' . addslashes($client['birthday']) . '",
                  phone = "' . addslashes($client['phone']) . '",
                  phone_mobile = "",
                  fax = "",
                  city = "' . addslashes($client['city']) . '",
                  post_code = "' . addslashes($client['post_code']) . '",
                  address = "' . addslashes($client['address']) . '",
                  email = "' . addslashes($client['email']) . '",
                  registered = now(),
                  account_owner = "",
                  account_number = "",
                  bank_index = "",
                  bank_name = "",
                  bank_iban = "' . addslashes($client['iban']) . '",
                  bank_bic = "' . addslashes($client['bic']) . '",
                  bank_sepa_mandat = "' . addslashes($client['sepa_mandat']) . '",
                  bank_sepa_referenz = "' . addslashes($client['sepa_referens']) . '"'
              ,
              [],
              false
            )) {
              ++$count;
              echo ' ; <span style="color: green">ok<span>';
            } else {
              echo ' <span style="color: red">fuck(' . $client['login'] . ')<span>>';
            }
            echo '</div>';
          }
        }
      }
    }
    echo '</table>';
    echo '<div style="color: #0c54a0">В систему добавлено : ' . $count . ' новых пользователей</div>';
  }

  function setPrepayment()
  {
    $client_id = (int)Service::request()->_post('client_id');
    $account_id = (int)Service::request()->_post('account_id');
    $amount = NumberHelper::float(Service::request()->_post('prepayment_sum', 0));
    if ($client_id && $account_id && $amount) {
      $response = ModCommHelper::callSafe('clients', 'PrivateAccount/add' . ($amount > 0 ? 'Deposit' : 'Withdraw'),
        [
          'client_id'    => $client_id,
          'type_code'    => 'invoice_deposit',
          'amount'       => $amount,
          'related_data' => [
            'account_id' => $account_id,
          ],
          'related_id'   => $account_id,
        ]
      );
      if ($response->isSuccess()) {
        if (getEngine('accounts', false)?->closedPrepaymentAccount($_POST['account_id'])) {
          ModCommHelper::callSafe('clients', 'PrivateAccount/changeStatus',
            ['transactionId' => $response->getDataValue('transactionId'), 'status' => 'succeeded']);
          $this->list_message = lang('Herewith the amount is included in the credit!', 'message_success');
        } else {
          $this->list_error = lang('Attempt failed!', 'message_error');
        }
      } else {
        $this->list_error = lang('System error! Please contact the developer! # ' . $response->getMessage(), 'message_error');
      }
    } else {
      $this->list_error = lang('input data error', 'message_error');
    }

    return $this->getClientsList();
  }
}

$a = new clients_admin;

$_page['content'] = $a->start();
$_page['key'] = $a->page_key;
$_page['js'][] = ['maskedinput', 'common', false, 'cdn'];
$_page['js'][] = ['jquery-ui/jquery-ui.min', 'third', true, 'cdn'];
$_page['js'][] = ['jquery-ui/i18n/datepicker-' . config('lang')->getCurrentLang(), 'third', true, 'cdn'];
$_page['css'][] = ['jquery-ui/jquery-ui.min', 'third', true, 'cdn'];
$_page['js'][] = ['clients', 'admin', false, 'cdn'];
$_page['js'][] = 'popup';
